<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\ContainerException;

final class GatewayUnbuildableException extends ContainerException
{
    public static function factoryFailed(\Throwable $previous): self
    {
        return new self(InvoiceCode::GatewayUnbuildable, 'The payment gateway factory failed.', $previous);
    }
}
