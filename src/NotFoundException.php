<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use KaririCode\Contract\Exception\NotFound;
use OutOfBoundsException;
use Throwable;

/**
 * Base of a component's NotFound failures: a lookup by identity found
 * nothing where the caller required a result, such as get() of an absent
 * entry or a route that does not exist. It extends OutOfBoundsException,
 * the parent kariricode/contract suggests.
 *
 * Preconditions: pair the throwing get() with a find() that returns null
 * for callers that expect absence (errata E-036); an empty search is an
 * empty collection, never this exception. The message names the kind of
 * thing that is missing, not the identifier the caller sent (E-047).
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final class InvoiceNotFoundException extends NotFoundException
 *     {
 *         public static function forNumber(): self
 *         {
 *             return new self(InvoiceCode::InvoiceMissing, 'No invoice has that number.');
 *         }
 *     }
 *
 * The HTTP edge answers 404.
 *
 * References: KaririCode Spec V4.3.3 §8.4 and §9.3 (E-036);
 * kariricode/contract Exception\NotFound; ADR-001; RFC 9110 §15.5.5.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.5.5
 */
abstract class NotFoundException extends OutOfBoundsException implements NotFound
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
