<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Unit;

use KaririCode\Exception\CarriesErrorCode;
use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ConfigurationException;
use KaririCode\Exception\ErrorCodeTable;
use KaririCode\Exception\ExceptionCode;
use KaririCode\Exception\InvalidCodeBlockException;
use KaririCode\Exception\InvalidErrorCodeTableException;
use KaririCode\Exception\Tests\Fixture\BelowBlockCode;
use KaririCode\Exception\Tests\Fixture\EmptyCode;
use KaririCode\Exception\Tests\Fixture\InvertedBlockCode;
use KaririCode\Exception\Tests\Fixture\InvoiceCode;
use KaririCode\Exception\Tests\Fixture\LowercasePrefixCode;
use KaririCode\Exception\Tests\Fixture\PlainEnum;
use KaririCode\Exception\Tests\Fixture\PrefixProbe;
use KaririCode\Exception\Tests\Fixture\ProbePrefixCode;
use KaririCode\Exception\Tests\Fixture\StrayCode;
use KaririCode\Exception\Tests\Fixture\StringBackedCode;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorCodeTable::class)]
#[CoversClass(InvalidErrorCodeTableException::class)]
#[UsesClass(CodeBlock::class)]
#[UsesClass(ConfigurationException::class)]
#[UsesClass(ExceptionCode::class)]
#[UsesClass(InvalidCodeBlockException::class)]
#[UsesTrait(CarriesErrorCode::class)]
final class ErrorCodeTableTest extends TestCase
{
    #[After]
    public function resetTheProbe(): void
    {
        PrefixProbe::$prefix = 'PP';
    }

    #[Test]
    public function describesAValidTable(): void
    {
        $table = new ErrorCodeTable(InvoiceCode::class);

        self::assertSame(InvoiceCode::class, $table->enum);
        self::assertSame('IV', $table->prefix);
        self::assertSame(7200, $table->block->first);
        self::assertSame(7299, $table->block->last);
    }

    #[Test]
    public function acceptsATableWithoutCasesYet(): void
    {
        self::assertSame('I18N', (new ErrorCodeTable(EmptyCode::class))->prefix);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validPrefixes(): iterable
    {
        yield 'two letters' => ['RL'];
        yield 'three letters' => ['CLI'];
        yield 'four characters with digits' => ['I18N'];
        yield 'a letter and a digit' => ['A1'];
    }

    #[Test]
    #[DataProvider('validPrefixes')]
    public function acceptsTheRegistryPrefixShapes(string $prefix): void
    {
        PrefixProbe::$prefix = $prefix;

        self::assertSame($prefix, (new ErrorCodeTable(ProbePrefixCode::class))->prefix);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPrefixes(): iterable
    {
        yield 'one letter' => ['R'];
        yield 'five characters' => ['ABCDE'];
        yield 'a leading digit' => ['1A'];
        yield 'lowercase' => ['rl'];
        yield 'an underscore' => ['R_L'];
        yield 'a trailing line feed' => ["RL\n"];
        yield 'a leading space' => [' RL'];
        yield 'empty' => [''];
    }

    #[Test]
    #[DataProvider('invalidPrefixes')]
    public function rejectsOtherPrefixShapes(string $prefix): void
    {
        PrefixProbe::$prefix = $prefix;

        $this->expectExceptionObject(InvalidErrorCodeTableException::invalidPrefix(ProbePrefixCode::class));

        new ErrorCodeTable(ProbePrefixCode::class);
    }

    #[Test]
    public function rejectsALowercasePrefixWithItsOwnCode(): void
    {
        try {
            new ErrorCodeTable(LowercasePrefixCode::class);
            self::fail('The table was accepted.');
        } catch (InvalidErrorCodeTableException $exception) {
            self::assertSame(1502, $exception->getCode());
            self::assertSame(
                '[EX_1502] The prefix of KaririCode\Exception\Tests\Fixture\LowercasePrefixCode must be a capital letter followed by 1 to 3 capital letters or digits.',
                $exception->getMessage(),
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notErrorCodes(): iterable
    {
        yield 'an enum without the interface' => [PlainEnum::class];
        yield 'a class' => [\ArrayObject::class];
        yield 'the interface itself' => [\KaririCode\Exception\ErrorCode::class];
        yield 'an unknown name' => ['App\\Missing\\Code'];
        yield 'free text' => ["not a class\n"];
    }

    #[Test]
    #[DataProvider('notErrorCodes')]
    public function rejectsAnythingButAnErrorCodeEnumWithoutRepeatingIt(string $name): void
    {
        try {
            new ErrorCodeTable($name);
            self::fail('The table was accepted.');
        } catch (InvalidErrorCodeTableException $exception) {
            self::assertSame(1501, $exception->getCode());
            self::assertSame(ExceptionCode::NotAnErrorCode, $exception->errorCode);
            self::assertSame(
                '[EX_1501] ErrorCodeTable takes the class name of an enum that implements KaririCode\Exception\ErrorCode.',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function rejectsAStringBackedTable(): void
    {
        try {
            new ErrorCodeTable(StringBackedCode::class);
            self::fail('The table was accepted.');
        } catch (InvalidErrorCodeTableException $exception) {
            self::assertSame(1503, $exception->getCode());
            self::assertSame(
                '[EX_1503] KaririCode\Exception\Tests\Fixture\StringBackedCode must be backed by int, because getCode() returns the case value.',
                $exception->getMessage(),
            );
        }
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function strayCases(): iterable
    {
        yield 'past the last code' => [StrayCode::class, 'Late'];
        yield 'before the first code' => [BelowBlockCode::class, 'Early'];
    }

    #[Test]
    #[DataProvider('strayCases')]
    public function rejectsACaseOutsideTheBlock(string $enum, string $case): void
    {
        try {
            new ErrorCodeTable($enum);
            self::fail('The table was accepted.');
        } catch (InvalidErrorCodeTableException $exception) {
            self::assertSame(1504, $exception->getCode());
            self::assertSame(
                sprintf('[EX_1504] Case %s::%s lies outside the block that %s declares.', $enum, $case, $enum),
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function rejectsAnInvalidBlock(): void
    {
        $this->expectException(InvalidCodeBlockException::class);
        $this->expectExceptionCode(1500);

        new ErrorCodeTable(InvertedBlockCode::class);
    }
}
