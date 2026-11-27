<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Breaks the prefix rule: lowercase letters. */
enum LowercasePrefixCode: int implements ErrorCode
{
    case Only = 7200;

    public static function prefix(): string
    {
        return 'iv';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
