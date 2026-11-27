<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use KaririCode\Contract\Exception\Runtime;
use Throwable;

/**
 * Base of a component's Runtime failures: an infrastructure fault while
 * running, where a store, a peer or a dependency did not answer as
 * expected and the same call may succeed later. It extends SPL's
 * \RuntimeException, the parent kariricode/contract suggests; mind the
 * shared short name when importing.
 *
 * Preconditions: the caller neither causes nor prevents it, so a retry
 * policy may retry it and a circuit breaker may count it. Chain the
 * driver's exception as previous; the message names the dependency, never
 * a connection string or credential (E-047).
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final class LedgerUnavailableException extends RuntimeException
 *     {
 *         public static function unreachable(\Throwable $previous): self
 *         {
 *             return new self(InvoiceCode::LedgerUnreachable, 'The ledger store is unreachable.', $previous);
 *         }
 *     }
 *
 * The HTTP edge answers 503 with Retry-After when the delay is known, 500
 * otherwise.
 *
 * References: KaririCode Spec V4.3.3 §8.4; kariricode/contract
 * Exception\Runtime; ADR-001; RFC 9110 §15.6.4.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.6.4
 */
abstract class RuntimeException extends \RuntimeException implements Runtime
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
