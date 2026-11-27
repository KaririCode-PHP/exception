<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\ProtocolException;

final class MalformedPayloadException extends ProtocolException
{
    public static function notJson(\JsonException $previous): self
    {
        return new self(InvoiceCode::MalformedPayload, 'The invoice payload is not valid JSON.', $previous);
    }
}
