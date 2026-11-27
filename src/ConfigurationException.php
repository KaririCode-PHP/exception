<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use InvalidArgumentException;
use KaririCode\Contract\Exception\Configuration;
use Throwable;

/**
 * Base of a component's Configuration failures: the component was set up
 * with values it cannot work with, such as a missing setting, an invalid
 * option or a binding that cannot be satisfied. Operators fix it, callers
 * cannot. It extends InvalidArgumentException, the parent
 * kariricode/contract suggests.
 *
 * Preconditions: detect it as early as possible (constructor, provider
 * register()) and fail fast (Spec V4.3.3 §8.5). The message names the
 * setting and the rule, never its value, which may be a secret.
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final class TaxRateException extends ConfigurationException
 *     {
 *         public static function unset(): self
 *         {
 *             return new self(InvoiceCode::TaxRateUnset, 'Set invoice.tax_rate before issuing invoices.');
 *         }
 *     }
 *
 * The HTTP edge answers 500 and keeps the detail in the logs.
 *
 * References: KaririCode Spec V4.3.3 §8.4 and §8.5; kariricode/contract
 * Exception\Configuration; ADR-001; RFC 9110 §15.6.1.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.6.1
 */
abstract class ConfigurationException extends InvalidArgumentException implements Configuration
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
