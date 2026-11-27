<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\ConfigurationException;

final class TaxRateException extends ConfigurationException
{
    public static function unset(): self
    {
        return new self(InvoiceCode::TaxRateUnset, 'Set invoice.tax_rate before issuing invoices.');
    }
}
