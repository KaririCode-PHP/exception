<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\NotFoundException;

final class InvoiceNotFoundException extends NotFoundException
{
    public static function forNumber(): self
    {
        return new self(InvoiceCode::InvoiceMissing, 'No invoice has that number.');
    }
}
