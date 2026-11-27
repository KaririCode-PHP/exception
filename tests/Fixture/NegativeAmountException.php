<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\ValidationException;

final class NegativeAmountException extends ValidationException
{
    public static function belowZero(): self
    {
        return new self(InvoiceCode::AmountBelowZero, 'An invoice amount cannot be negative.');
    }

    /** Exposes the message rules of CarriesErrorCode to the tests. */
    public static function withText(string $text, ?\Throwable $previous = null): self
    {
        return new self(InvoiceCode::AmountBelowZero, $text, $previous);
    }

    /** Exposes CarriesErrorCode::quote() to the tests. */
    public static function quoted(string $identifier): string
    {
        return self::quote($identifier);
    }
}
