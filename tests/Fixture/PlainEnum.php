<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

/** An enum that does not implement ErrorCode. */
enum PlainEnum: int
{
    case Only = 7200;
}
