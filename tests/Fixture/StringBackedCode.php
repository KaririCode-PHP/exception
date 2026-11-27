<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Breaks the backing rule: getCode() needs an int. */
enum StringBackedCode: string implements ErrorCode
{
    case Only = '7200';

    public static function prefix(): string
    {
        return 'SB';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
