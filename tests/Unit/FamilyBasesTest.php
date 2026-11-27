<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Unit;

use KaririCode\Contract\Exception\Configuration;
use KaririCode\Contract\Exception\ContainerFailure;
use KaririCode\Contract\Exception\ExceptionInterface;
use KaririCode\Contract\Exception\NotFound;
use KaririCode\Contract\Exception\Protocol;
use KaririCode\Contract\Exception\Runtime;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Exception\CarriesErrorCode;
use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ConfigurationException;
use KaririCode\Exception\ContainerException;
use KaririCode\Exception\NotFoundException;
use KaririCode\Exception\ProtocolException;
use KaririCode\Exception\RuntimeException;
use KaririCode\Exception\Tests\Fixture\GatewayUnbuildableException;
use KaririCode\Exception\Tests\Fixture\InvoiceCode;
use KaririCode\Exception\Tests\Fixture\InvoiceNotFoundException;
use KaririCode\Exception\Tests\Fixture\LedgerUnavailableException;
use KaririCode\Exception\Tests\Fixture\MalformedPayloadException;
use KaririCode\Exception\Tests\Fixture\NegativeAmountException;
use KaririCode\Exception\Tests\Fixture\TaxRateException;
use KaririCode\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ValidationException::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(ConfigurationException::class)]
#[CoversClass(ProtocolException::class)]
#[CoversClass(ContainerException::class)]
#[CoversClass(RuntimeException::class)]
#[UsesTrait(CarriesErrorCode::class)]
#[UsesClass(CodeBlock::class)]
final class FamilyBasesTest extends TestCase
{
    /**
     * Each base extends the SPL class that kariricode/contract's docblock
     * example gives for its family, and implements that family only.
     *
     * @return iterable<string, array{class-string, class-string, class-string}>
     */
    public static function bases(): iterable
    {
        yield 'Validation' => [ValidationException::class, \DomainException::class, Validation::class];
        yield 'NotFound' => [NotFoundException::class, \OutOfBoundsException::class, NotFound::class];
        yield 'Configuration' => [ConfigurationException::class, \InvalidArgumentException::class, Configuration::class];
        yield 'Protocol' => [ProtocolException::class, \UnexpectedValueException::class, Protocol::class];
        yield 'ContainerFailure' => [ContainerException::class, \LogicException::class, ContainerFailure::class];
        yield 'Runtime' => [RuntimeException::class, \RuntimeException::class, Runtime::class];
    }

    /**
     * @param class-string $base
     * @param class-string $parent
     * @param class-string $family
     */
    #[Test]
    #[DataProvider('bases')]
    public function extendsTheSplParentAndImplementsOneFamily(string $base, string $parent, string $family): void
    {
        $type = new ReflectionClass($base);
        $families = [Validation::class, NotFound::class, Configuration::class, Protocol::class, ContainerFailure::class, Runtime::class];

        self::assertTrue($type->isAbstract(), $base);
        self::assertSame($parent, $type->getParentClass() === false ? null : $type->getParentClass()->getName());
        self::assertSame([$family], array_values(array_intersect($families, $type->getInterfaceNames())));
        self::assertTrue($type->implementsInterface(ExceptionInterface::class));
        self::assertSame([CarriesErrorCode::class], $type->getTraitNames());
    }

    /**
     * @return iterable<string, array{\Throwable, class-string, string, int}>
     */
    public static function concreteExceptions(): iterable
    {
        $previous = new \JsonException('Syntax error');

        yield 'Validation' => [NegativeAmountException::belowZero(), ValidationException::class, '[IV_7200] An invoice amount cannot be negative.', 7200];
        yield 'NotFound' => [InvoiceNotFoundException::forNumber(), NotFoundException::class, '[IV_7210] No invoice has that number.', 7210];
        yield 'Configuration' => [TaxRateException::unset(), ConfigurationException::class, '[IV_7220] Set invoice.tax_rate before issuing invoices.', 7220];
        yield 'Protocol' => [MalformedPayloadException::notJson($previous), ProtocolException::class, '[IV_7230] The invoice payload is not valid JSON.', 7230];
        yield 'ContainerFailure' => [GatewayUnbuildableException::factoryFailed($previous), ContainerException::class, '[IV_7240] The payment gateway factory failed.', 7240];
        yield 'Runtime' => [LedgerUnavailableException::unreachable($previous), RuntimeException::class, '[IV_7250] The ledger store is unreachable.', 7250];
    }

    /**
     * @param class-string $base
     */
    #[Test]
    #[DataProvider('concreteExceptions')]
    public function aComponentExceptionCarriesItsCode(\Throwable $exception, string $base, string $message, int $code): void
    {
        self::assertInstanceOf($base, $exception);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($message, $exception->getMessage());
        self::assertSame($code, $exception->getCode());
        self::assertSame(InvoiceCode::from($code), (new \ReflectionProperty($exception, 'errorCode'))->getValue($exception));
    }
}
