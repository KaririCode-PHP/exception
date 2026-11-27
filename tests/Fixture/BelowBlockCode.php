<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Breaks the block rule: one case before the first code. */
enum BelowBlockCode: int implements ErrorCode
{
    case Early = 7199;

    public static function prefix(): string
    {
        return 'BB';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
