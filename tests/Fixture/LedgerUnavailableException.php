<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\ErrorCode;
use KaririCode\Exception\RuntimeException;

final class LedgerUnavailableException extends RuntimeException
{
    public static function unreachable(\Throwable $previous): self
    {
        return new self(InvoiceCode::LedgerUnreachable, 'The ledger store is unreachable.', $previous);
    }

    /** Any code, to exercise CarriesErrorCode with other enums. */
    public static function coded(ErrorCode $code): self
    {
        return new self($code, 'Coded.');
    }
}
