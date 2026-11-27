<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Unit;

use KaririCode\Exception\CarriesErrorCode;
use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\RuntimeException;
use KaririCode\Exception\Tests\Fixture\InvoiceCode;
use KaririCode\Exception\Tests\Fixture\LedgerUnavailableException;
use KaririCode\Exception\Tests\Fixture\LegacyInvoiceException;
use KaririCode\Exception\Tests\Fixture\NegativeAmountException;
use KaririCode\Exception\Tests\Fixture\StringBackedCode;
use KaririCode\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversTrait(CarriesErrorCode::class)]
#[UsesClass(ValidationException::class)]
#[UsesClass(RuntimeException::class)]
#[UsesClass(CodeBlock::class)]
final class CarriesErrorCodeTest extends TestCase
{
    #[Test]
    public function tagsTheMessageWithPrefixAndCode(): void
    {
        $exception = NegativeAmountException::belowZero();

        self::assertSame('[IV_7200] An invoice amount cannot be negative.', $exception->getMessage());
        self::assertSame(7200, $exception->getCode());
        self::assertSame(InvoiceCode::AmountBelowZero, $exception->errorCode);
        self::assertNull($exception->getPrevious());
    }

    #[Test]
    public function chainsThePreviousException(): void
    {
        $driver = new \RuntimeException('connection refused');
        $exception = LedgerUnavailableException::unreachable($driver);

        self::assertSame($driver, $exception->getPrevious());
        self::assertSame('[IV_7250] The ledger store is unreachable.', $exception->getMessage());
        self::assertSame(7250, $exception->getCode());
    }

    #[Test]
    public function servesAComponentThatKeepsItsOwnRootClass(): void
    {
        $exception = LegacyInvoiceException::failed();

        self::assertSame('[IV_7299] The legacy invoice job failed.', $exception->getMessage());
        self::assertSame(7299, $exception->getCode());
        self::assertSame(InvoiceCode::LegacyFailure, $exception->errorCode);
    }

    #[Test]
    public function onlyTheBaseWritesTheErrorCode(): void
    {
        $exception = NegativeAmountException::belowZero();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify private(set) property');

        // The assignment is the point of the test: PHP itself must refuse it.
        $exception->errorCode = InvoiceCode::TaxRateUnset; // @phpstan-ignore assign.propertyPrivateSet
    }

    #[Test]
    public function survivesSerialization(): void
    {
        $copy = unserialize(serialize(NegativeAmountException::belowZero()));

        self::assertInstanceOf(NegativeAmountException::class, $copy);
        self::assertSame(InvoiceCode::AmountBelowZero, $copy->errorCode);
        self::assertSame('[IV_7200] An invoice amount cannot be negative.', $copy->getMessage());
    }

    #[Test]
    public function aStringBackedCodeKeepsItsTagButCodeZero(): void
    {
        $exception = LedgerUnavailableException::coded(StringBackedCode::Only);

        self::assertSame('[SB_7200] Coded.', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafeCharacters(): iterable
    {
        yield 'line feed' => ["a\nb", 'a\nb'];
        yield 'carriage return' => ["a\rb", 'a\rb'];
        yield 'tab' => ["a\tb", 'a\tb'];
        yield 'NUL' => ["a\0b", 'a\u0000b'];
        yield 'escape' => ["a\x1Bb", 'a\u001bb'];
        yield 'unit separator' => ["a\x1Fb", 'a\u001fb'];
        yield 'DEL' => ["a\x7Fb", 'a\u007fb'];
        yield 'C1 first' => ["a\u{80}b", 'a\u0080b'];
        yield 'next line' => ["a\u{85}b", 'a\u0085b'];
        yield 'C1 last' => ["a\u{9F}b", 'a\u009fb'];
        yield 'arabic letter mark' => ["a\u{61C}b", 'a\u061cb'];
        yield 'left-to-right mark' => ["a\u{200E}b", 'a\u200eb'];
        yield 'right-to-left mark' => ["a\u{200F}b", 'a\u200fb'];
        yield 'line separator' => ["a\u{2028}b", 'a\u2028b'];
        yield 'paragraph separator' => ["a\u{2029}b", 'a\u2029b'];
        yield 'left-to-right embedding' => ["a\u{202A}b", 'a\u202ab'];
        yield 'right-to-left override' => ["a\u{202E}b", 'a\u202eb'];
        yield 'left-to-right isolate' => ["a\u{2066}b", 'a\u2066b'];
        yield 'pop directional isolate' => ["a\u{2069}b", 'a\u2069b'];
        yield 'invalid UTF-8' => ["a\xC3\x28b", "a\u{FFFD}(b"];
        yield 'a forged log line' => ["x\n[IV_7200] fake", 'x\n[IV_7200] fake'];
    }

    #[Test]
    #[DataProvider('unsafeCharacters')]
    public function neutralisesTextThatCouldForgeALogLine(string $text, string $expected): void
    {
        self::assertSame('[IV_7200] ' . $expected, NegativeAmountException::withText($text)->getMessage());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function safeText(): iterable
    {
        yield 'plain' => ['Quota must be at least 1.'];
        yield 'class names keep their backslashes' => ['App\\Domain\\Invoice'];
        yield 'quotes and slashes' => ['"a" / \'b\''];
        yield 'accented letters' => ['Configuração inválida: ação'];
        yield 'emoji and CJK' => ['日本語 ✓ 😀'];
        yield 'neighbours of the escaped ranges' => ["\u{A0}\u{61B}\u{61D}\u{200D}\u{2027}\u{202F}\u{2065}\u{206A}"];
        yield 'empty' => [''];
    }

    #[Test]
    #[DataProvider('safeText')]
    public function leavesSafeTextAlone(string $text): void
    {
        self::assertSame('[IV_7200] ' . $text, NegativeAmountException::withText($text)->getMessage());
    }

    #[Test]
    public function quotesAnIdentifierAsAJsonLiteral(): void
    {
        self::assertSame('"api"', NegativeAmountException::quoted('api'));
        self::assertSame('"api\n"', NegativeAmountException::quoted("api\n"));
        self::assertSame('"say \"hi\""', NegativeAmountException::quoted('say "hi"'));
        self::assertSame('"App\\\\Store"', NegativeAmountException::quoted('App\\Store'));
        self::assertSame('"a/b"', NegativeAmountException::quoted('a/b'));
        self::assertSame('"ação"', NegativeAmountException::quoted('ação'));
        self::assertSame("\"a\u{FFFD}(\"", NegativeAmountException::quoted("a\xC3\x28"));
        self::assertSame('""', NegativeAmountException::quoted(''));
    }

    #[Test]
    public function quoteCutsLongIdentifiersAfter128Characters(): void
    {
        $exact = str_repeat('a', 128);
        $long = str_repeat('a', 129);
        $multibyte = str_repeat('ç', 128);

        self::assertSame('"' . $exact . '"', NegativeAmountException::quoted($exact));
        self::assertSame('"' . $exact . '…"', NegativeAmountException::quoted($long));
        self::assertSame('"' . $multibyte . '"', NegativeAmountException::quoted($multibyte));
        self::assertSame('"' . $multibyte . '…"', NegativeAmountException::quoted($multibyte . 'ç'));
        self::assertSame('"' . str_repeat('a', 127) . '\n…"', NegativeAmountException::quoted(str_repeat('a', 127) . "\n\n"));
    }

    #[Test]
    public function aQuotedIdentifierStaysSafeInsideTheMessage(): void
    {
        $name = NegativeAmountException::quoted("store\u{202E}1\n");

        self::assertSame('[IV_7200] Store "store\u202e1\n" is unknown.', NegativeAmountException::withText("Store {$name} is unknown.")->getMessage());
    }
}
