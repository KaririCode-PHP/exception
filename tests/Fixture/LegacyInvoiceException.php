<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Contract\Exception\Runtime;

final class LegacyInvoiceException extends InvoiceFailure implements Runtime
{
    public static function failed(): self
    {
        return new self(InvoiceCode::LegacyFailure, 'The legacy invoice job failed.');
    }
}
