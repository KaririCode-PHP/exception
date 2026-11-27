<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** No case yet, and the longest prefix shape: valid. */
enum EmptyCode: int implements ErrorCode
{
    public static function prefix(): string
    {
        return 'I18N';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
