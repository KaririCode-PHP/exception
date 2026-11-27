<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Breaks the block rule: one case past the last code. */
enum StrayCode: int implements ErrorCode
{
    case Early = 7200;
    case Late = 7300;

    public static function prefix(): string
    {
        return 'ST';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
