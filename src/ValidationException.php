<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use DomainException;
use KaririCode\Contract\Exception\Validation;
use Throwable;

/**
 * Base of a component's Validation failures: input the caller supplied
 * breaks a rule the caller controls, and corrected input succeeds. It
 * extends DomainException, the parent kariricode/contract suggests, so
 * code that catches SPL exceptions still recognises it.
 *
 * Preconditions: a subclass passes a case of its component's ErrorCode and
 * a message that states the rule without the rejected value (E-047). A
 * component that collects several errors returns a result object instead
 * and throws at most once, at the edge (Spec V4.3.3 §8.1).
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final class NegativeAmountException extends ValidationException
 *     {
 *         public static function belowZero(): self
 *         {
 *             return new self(InvoiceCode::AmountBelowZero, 'An invoice amount cannot be negative.');
 *         }
 *     }
 *
 * The HTTP edge answers 422.
 *
 * References: KaririCode Spec V4.3.3 §8.1 and §8.4; kariricode/contract
 * Exception\Validation; ADR-001; RFC 9110 §15.5.21.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.5.21
 */
abstract class ValidationException extends DomainException implements Validation
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
