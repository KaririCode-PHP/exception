<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Unit;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCodeTable;
use KaririCode\Exception\ExceptionCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExceptionCode::class)]
#[UsesClass(CodeBlock::class)]
#[UsesClass(ErrorCodeTable::class)]
final class ExceptionCodeTest extends TestCase
{
    #[Test]
    public function passesItsOwnTableCheck(): void
    {
        $table = new ErrorCodeTable(ExceptionCode::class);

        self::assertSame('EX', $table->prefix);
        self::assertSame(1500, $table->block->first);
        self::assertSame(1599, $table->block->last);
    }

    #[Test]
    public function codesAreStable(): void
    {
        $codes = [];

        foreach (ExceptionCode::cases() as $case) {
            $codes[$case->name] = $case->value;
        }

        self::assertSame([
            'InvalidCodeBlock' => 1500,
            'NotAnErrorCode' => 1501,
            'InvalidPrefix' => 1502,
            'NotIntBacked' => 1503,
            'CodeOutsideBlock' => 1504,
        ], $codes);
    }
}
