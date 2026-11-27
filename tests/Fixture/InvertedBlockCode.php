<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Declares a block with its bounds reversed. */
enum InvertedBlockCode: int implements ErrorCode
{
    case Only = 7200;

    public static function prefix(): string
    {
        return 'IB';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7299, 7200);
    }
}
