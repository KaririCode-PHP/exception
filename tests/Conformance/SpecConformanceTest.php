<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Conformance;

use KaririCode\Contract\Exception\Configuration;
use KaririCode\Contract\Exception\ContainerFailure;
use KaririCode\Contract\Exception\ExceptionInterface;
use KaririCode\Contract\Exception\NotFound;
use KaririCode\Contract\Exception\Protocol;
use KaririCode\Contract\Exception\Runtime;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Exception\CarriesErrorCode;
use KaririCode\Exception\ConfigurationException;
use KaririCode\Exception\ContainerException;
use KaririCode\Exception\ErrorCode;
use KaririCode\Exception\NotFoundException;
use KaririCode\Exception\ProtocolException;
use KaririCode\Exception\RuntimeException;
use KaririCode\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * KaririCode Spec V4.3.3 conformance of src/ (§29.2): every rule runs over
 * every type and must find nothing. There is no list of tolerated
 * deviations: 2.0.0 starts clean.
 */
#[CoversNothing]
final class SpecConformanceTest extends TestCase
{
    private const string SOURCE = __DIR__ . '/../../src';

    private const string ROOT = __DIR__ . '/../..';

    private const array FAMILIES = [
        Configuration::class,
        ContainerFailure::class,
        NotFound::class,
        Protocol::class,
        Runtime::class,
        Validation::class,
    ];

    private const array FAMILY_BASES = [
        ConfigurationException::class,
        ContainerException::class,
        NotFoundException::class,
        ProtocolException::class,
        RuntimeException::class,
        ValidationException::class,
    ];

    #[Test]
    public function eachFileDeclaresStrictTypesAndOneTypeNamedAfterIt(): void
    {
        $found = [];

        foreach (self::files() as $file) {
            $source = (string) file_get_contents($file);
            $declared = preg_match_all('/^(?:final |abstract |readonly )*(?:class|interface|trait|enum) (\w+)/m', $source, $names);

            if (!str_contains($source, "declare(strict_types=1);\n\nnamespace KaririCode\\Exception;")) {
                $found[] = basename($file) . ': strict types and namespace';
            }

            if ($declared !== 1 || $names[1][0] !== basename($file, '.php')) {
                $found[] = basename($file) . ': one type named after the file';
            }
        }

        self::assertSame([], $found);
        self::assertCount(13, self::types(), 'src/ holds the 13 types of SPEC-001 §2');
    }

    #[Test]
    public function classesAreFinalUnlessTheyAreTheSixFamilyBases(): void
    {
        $abstract = [];

        foreach (self::types() as $type) {
            if ($type->isInterface() || $type->isTrait() || $type->isEnum()) {
                continue;
            }

            if ($type->isAbstract()) {
                $abstract[] = $type->getName();
            } else {
                self::assertTrue($type->isFinal(), $type->getName() . ' is final');
            }
        }

        $expected = self::FAMILY_BASES;
        sort($abstract);
        sort($expected);
        self::assertSame($expected, $abstract);
    }

    #[Test]
    public function namesFollowSection16AndAvoidVagueNouns(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            $name = $type->getShortName();

            if (preg_match('/Interface$|(?:Manager|Helper|Utils?)(?=[A-Z]|$)/', $name) === 1) {
                $found[] = $name . ': forbidden suffix';
            }

            if ($type->isSubclassOf(\Throwable::class) && !str_ends_with($name, 'Exception')) {
                $found[] = $name . ': exceptions end in Exception (§16.3)';
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function noZeroArgumentGettersSerializersOrMagicMethods(): void
    {
        $found = [];

        foreach (self::ownMethods() as $name => $method) {
            $short = $method->getName();

            if (preg_match('/^get(?:[A-Z_]|$)/', $short) === 1 && $method->getNumberOfParameters() === 0) {
                $found[] = $name . ': zero-argument getter (§18)';
            }

            if (in_array(strtolower($short), ['toarray', 'fromarray', 'jsonserialize'], true)) {
                $found[] = $name . ': serializes itself (§18)';
            }

            if (str_starts_with($short, '__') && $short !== '__construct') {
                $found[] = $name . ': magic method (§29.2)';
            }
        }

        foreach (self::types() as $type) {
            if ($type->implementsInterface(\JsonSerializable::class)) {
                $found[] = $type->getName() . ': JsonSerializable (§18)';
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function stateIsImmutableAndNeverStatic(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            foreach ($type->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $type->getName() || $property->getDeclaringClass()->isInternal()) {
                    continue;
                }

                if ($property->isStatic()) {
                    $found[] = $type->getName() . '::$' . $property->getName() . ': static state (§29.2)';
                }

                if ($property->isPublic() && !self::isWriteProtected($property)) {
                    $found[] = $type->getName() . '::$' . $property->getName() . ': public and writable';
                }

                if ($property->isReadOnly() && $property->hasHooks()) {
                    $found[] = $type->getName() . '::$' . $property->getName() . ': hook on a readonly property (E-040)';
                }
            }
        }

        foreach (self::traitProperties() as $name => $property) {
            if ($property->isStatic() || !self::isWriteProtected($property)) {
                $found[] = $name . ': trait state is per instance and written by its class only';
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function constantsAreTyped(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            foreach ($type->getReflectionConstants() as $constant) {
                if ($constant->getDeclaringClass()->getName() === $type->getName() && !$constant->isEnumCase() && !$constant->hasType()) {
                    $found[] = $type->getName() . '::' . $constant->getName();
                }
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function inheritanceStaysWithinTwoLevelsOfUserClasses(): void
    {
        foreach (self::types() as $type) {
            $depth = 0;

            for ($parent = $type->getParentClass(); $parent !== false; $parent = $parent->getParentClass()) {
                if (!$parent->isInternal()) {
                    ++$depth;
                }
            }

            self::assertLessThanOrEqual(1, $depth, $type->getName() . ': a component class ends at depth 2 (§18)');
        }
    }

    #[Test]
    public function everyExceptionHasExactlyOneFamilyAndCarriesACode(): void
    {
        foreach (self::types() as $type) {
            if (!$type->isSubclassOf(\Throwable::class)) {
                continue;
            }

            self::assertTrue($type->implementsInterface(ExceptionInterface::class), $type->getName());
            self::assertCount(1, array_intersect(self::FAMILIES, $type->getInterfaceNames()), $type->getName() . ': one family');
            self::assertTrue(self::usesCarriesErrorCode($type->getName()), $type->getName() . ': carries an ErrorCode');
        }
    }

    #[Test]
    public function concreteExceptionsAreBuiltByNamedConstructors(): void
    {
        foreach (self::types() as $type) {
            if (!$type->isSubclassOf(\Throwable::class) || $type->isAbstract()) {
                continue;
            }

            $factories = 0;

            foreach ($type->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $type->getName()) {
                    continue;
                }

                self::assertTrue($method->isStatic(), $type->getName() . '::' . $method->getName() . ' is a named constructor');
                // PHP 8.5 reports a self return type by the resolved class name.
                self::assertContains((string) $method->getReturnType(), ['self', $type->getName()]);
                ++$factories;
            }

            self::assertGreaterThan(0, $factories, $type->getName());
        }
    }

    #[Test]
    public function docblocksMeetTheFiveCriteria(): void
    {
        $markers = [
            'preconditions (C2)' => 'Preconditions',
            'thread-safety (C2)' => 'Thread-safety:',
            'side effects (C3)' => 'Side effects:',
            'example (C4)' => 'Example',
            'references (C5)' => 'References:',
            'link (C5)' => '@see https://',
        ];
        $missing = [];

        foreach (self::types() as $type) {
            $docblock = (string) $type->getDocComment();

            foreach ($markers as $criterion => $marker) {
                if (!str_contains($docblock, $marker)) {
                    $missing[] = $type->getShortName() . ': ' . $criterion;
                }
            }

            if (preg_match('/@(?:package|author|license|copyright|version)\b/', $docblock) === 1) {
                $missing[] = $type->getShortName() . ': authorship tag';
            }

            if (preg_match('/\A\/\*\*\s+\*\s+Represents\b/', $docblock) === 1) {
                $missing[] = $type->getShortName() . ': C1 states the domain role, not "Represents"';
            }
        }

        self::assertSame([], $missing);
    }

    #[Test]
    public function theCodeEnumsImplementErrorCode(): void
    {
        foreach (self::types() as $type) {
            if ($type->isEnum()) {
                self::assertTrue($type->implementsInterface(ErrorCode::class), $type->getName());
            }
        }
    }

    #[Test]
    public function theRuntimeDependsOnPhpAndTheContractOnly(): void
    {
        /** @var array{require: array<string, string>, extra: array{arfa?: array{version?: string, level?: int}, asvs?: array{version?: string, level?: int}}} $composer */
        $composer = json_decode((string) file_get_contents(self::ROOT . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['php' => '^8.4', 'kariricode/contract' => '^2.11'], $composer['require'], 'Spec V4.3.3 §31: coupling through kariricode/contract only');
        self::assertSame('1.3', $composer['extra']['arfa']['version'] ?? null, '§29.3 G4: arfa.version');
        self::assertSame(1, $composer['extra']['arfa']['level'] ?? null, 'G4: arfa.level');
        self::assertSame('5.0', $composer['extra']['asvs']['version'] ?? null, 'G4: asvs.version');
        self::assertContains($composer['extra']['asvs']['level'] ?? null, [1, 2, 3], 'G4: asvs.level');
    }

    #[Test]
    public function sourceNeverUsesAnotherKaririCodePackage(): void
    {
        $found = [];

        foreach (self::files() as $file) {
            preg_match_all('/^use (KaririCode\\\\[A-Za-z]+)\\\\/m', (string) file_get_contents($file), $imports);

            foreach ($imports[1] as $namespace) {
                if (!in_array($namespace, ['KaririCode\\Contract', 'KaririCode\\Exception'], true)) {
                    $found[] = basename($file) . ': ' . $namespace;
                }
            }
        }

        self::assertSame([], $found, 'no circular dependency is possible: §31');
    }

    /**
     * @return list<string>
     */
    private static function files(): array
    {
        $files = glob(self::SOURCE . '/*.php');
        self::assertIsArray($files);
        sort($files);

        return $files;
    }

    /**
     * @return list<ReflectionClass<object>>
     */
    private static function types(): array
    {
        return array_map(
            static function (string $file): ReflectionClass {
                $name = 'KaririCode\\Exception\\' . basename($file, '.php');
                self::assertTrue(class_exists($name) || interface_exists($name) || trait_exists($name) || enum_exists($name), $name);

                /** @var class-string $name */
                return new ReflectionClass($name);
            },
            self::files(),
        );
    }

    /**
     * @return array<string, ReflectionMethod>
     */
    private static function ownMethods(): array
    {
        $methods = [];

        foreach (self::types() as $type) {
            foreach ($type->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() === $type->getName() && !$method->isInternal()) {
                    $methods[$type->getName() . '::' . $method->getName()] = $method;
                }
            }
        }

        return $methods;
    }

    /**
     * @return array<string, \ReflectionProperty>
     */
    private static function traitProperties(): array
    {
        $properties = [];

        foreach ((new ReflectionClass(CarriesErrorCode::class))->getProperties() as $property) {
            $properties['CarriesErrorCode::$' . $property->getName()] = $property;
        }

        return $properties;
    }

    /** readonly, private(set) or protected(set): §18 accepts all three. */
    private static function isWriteProtected(\ReflectionProperty $property): bool
    {
        return $property->isReadOnly() || $property->isPrivateSet() || $property->isProtectedSet();
    }

    /**
     * @param class-string $class
     */
    private static function usesCarriesErrorCode(string $class): bool
    {
        foreach ([$class, ...(class_parents($class) ?: [])] as $type) {
            if (in_array(CarriesErrorCode::class, class_uses($type) ?: [], true)) {
                return true;
            }
        }

        return false;
    }
}
