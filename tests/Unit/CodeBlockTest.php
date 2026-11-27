<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Unit;

use KaririCode\Exception\CarriesErrorCode;
use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ConfigurationException;
use KaririCode\Exception\ExceptionCode;
use KaririCode\Exception\InvalidCodeBlockException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeBlock::class)]
#[CoversClass(InvalidCodeBlockException::class)]
#[UsesClass(ConfigurationException::class)]
#[UsesClass(ExceptionCode::class)]
#[UsesTrait(CarriesErrorCode::class)]
final class CodeBlockTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function validBounds(): iterable
    {
        yield 'a block of one hundred' => [8300, 8399];
        yield 'the first possible code' => [1000, 1099];
        yield 'the last possible code' => [9900, 9999];
        yield 'the whole range' => [1000, 9999];
        yield 'a single code' => [1500, 1500];
    }

    #[Test]
    #[DataProvider('validBounds')]
    public function keepsValidBounds(int $first, int $last): void
    {
        $block = new CodeBlock($first, $last);

        self::assertSame($first, $block->first);
        self::assertSame($last, $block->last);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidBounds(): iterable
    {
        yield 'first below 1000' => [999, 1099];
        yield 'last above 9999' => [9900, 10000];
        yield 'reversed' => [8399, 8300];
        yield 'reversed by one' => [8300, 8299];
        yield 'negative' => [-1, 1099];
        yield 'both out of range' => [999, 10000];
    }

    #[Test]
    #[DataProvider('invalidBounds')]
    public function rejectsInvalidBoundsWithoutRepeatingThem(int $first, int $last): void
    {
        try {
            new CodeBlock($first, $last);
            self::fail('A CodeBlock with invalid bounds was built.');
        } catch (InvalidCodeBlockException $exception) {
            self::assertSame(1500, $exception->getCode());
            self::assertSame(ExceptionCode::InvalidCodeBlock, $exception->errorCode);
            self::assertSame(
                '[EX_1500] A code block runs from first to last inside 1000-9999, with first <= last.',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function containsItsBoundsAndWhatLiesBetween(): void
    {
        $block = new CodeBlock(8300, 8399);

        self::assertTrue($block->contains(8300));
        self::assertTrue($block->contains(8350));
        self::assertTrue($block->contains(8399));
        self::assertFalse($block->contains(8299));
        self::assertFalse($block->contains(8400));
    }

    /**
     * @return iterable<string, array{int, int, int, int, bool}>
     */
    public static function pairs(): iterable
    {
        yield 'identical' => [8300, 8399, 8300, 8399, true];
        yield 'nested' => [8300, 8399, 8310, 8319, true];
        yield 'sharing the last code' => [8300, 8399, 8399, 8499, true];
        yield 'sharing the first code' => [8300, 8399, 8200, 8300, true];
        yield 'adjacent above' => [8300, 8399, 8400, 8499, false];
        yield 'adjacent below' => [8300, 8399, 8200, 8299, false];
        yield 'far apart' => [1000, 1499, 9000, 9099, false];
        yield 'single codes, equal' => [1500, 1500, 1500, 1500, true];
        yield 'single codes, apart' => [1500, 1500, 1501, 1501, false];
    }

    #[Test]
    #[DataProvider('pairs')]
    public function overlapsIsSymmetric(int $a, int $b, int $c, int $d, bool $expected): void
    {
        $left = new CodeBlock($a, $b);
        $right = new CodeBlock($c, $d);

        self::assertSame($expected, $left->overlaps($right));
        self::assertSame($expected, $right->overlaps($left));
    }

    /**
     * Property-based check (Spec V4.3.3 §19.3): over generated blocks,
     * two blocks overlap exactly when some code lies in both, and the
     * relation is symmetric. The seed keeps the run reproducible.
     *
     * @return iterable<string, array{int, int, int, int}>
     */
    public static function generatedPairs(): iterable
    {
        mt_srand(20261127);

        for ($i = 0; $i < 200; ++$i) {
            $a = mt_rand(1000, 1060);
            $b = mt_rand($a, 1060);
            $c = mt_rand(1000, 1060);
            $d = mt_rand($c, 1060);

            yield "pair {$i}: {$a}-{$b} / {$c}-{$d}" => [$a, $b, $c, $d];
        }

        mt_srand();
    }

    #[Test]
    #[DataProvider('generatedPairs')]
    public function overlapsMeansACodeInBoth(int $a, int $b, int $c, int $d): void
    {
        $left = new CodeBlock($a, $b);
        $right = new CodeBlock($c, $d);
        $shared = false;

        for ($code = 1000; $code <= 1060; ++$code) {
            $shared = $shared || ($left->contains($code) && $right->contains($code));
        }

        self::assertSame($shared, $left->overlaps($right));
        self::assertSame($left->overlaps($right), $right->overlaps($left));
    }

    #[Test]
    public function exposesTheRangeOfFourDigitCodes(): void
    {
        self::assertSame(1000, CodeBlock::FLOOR);
        self::assertSame(9999, CodeBlock::CEILING);
    }
}
