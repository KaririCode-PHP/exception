<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Reads its prefix from PrefixProbe, so one enum probes every prefix shape. */
enum ProbePrefixCode: int implements ErrorCode
{
    case Only = 7200;

    public static function prefix(): string
    {
        return PrefixProbe::$prefix;
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
