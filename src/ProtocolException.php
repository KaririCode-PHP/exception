<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use KaririCode\Contract\Exception\Protocol;
use Throwable;
use UnexpectedValueException;

/**
 * Base of a component's Protocol failures: data arrived in a shape its
 * protocol does not allow, such as malformed JSON, an unknown frame or a
 * header that breaks its grammar. It extends UnexpectedValueException, the
 * parent kariricode/contract suggests.
 *
 * Preconditions: the boundary that parses the wire format raises it, before
 * the data becomes a domain type (Spec V4.3.3 §20.1); input that parses but
 * breaks a domain rule is Validation. The message names the rule of the
 * format and never quotes the payload (E-047).
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final class MalformedPayloadException extends ProtocolException
 *     {
 *         public static function notJson(\JsonException $previous): self
 *         {
 *             return new self(InvoiceCode::MalformedPayload, 'The invoice payload is not valid JSON.', $previous);
 *         }
 *     }
 *
 * The HTTP edge answers 400.
 *
 * References: KaririCode Spec V4.3.3 §8.4 and §20.1; kariricode/contract
 * Exception\Protocol; ADR-001; RFC 9110 §15.5.1.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.5.1
 */
abstract class ProtocolException extends UnexpectedValueException implements Protocol
{
    use CarriesErrorCode {
        CarriesErrorCode::__construct as private carry;
    }

    /** Declared here so analysers see $errorCode set for every subclass. */
    public function __construct(ErrorCode $errorCode, string $message, ?Throwable $previous = null)
    {
        $this->carry($errorCode, $message, $previous);
    }
}
