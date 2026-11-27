<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use KaririCode\Contract\Exception\ContainerFailure;
use LogicException;
use Throwable;

/**
 * Base of a component's ContainerFailure failures: the dependency
 * injection container could not build a service that is bound, through a
 * circular dependency, an unresolvable parameter or a factory that threw.
 * It extends LogicException, the parent kariricode/contract suggests.
 *
 * Preconditions: an identifier that was never bound is NotFound, not this.
 * A container that also implements PSR-11 adds
 * Psr\Container\ContainerExceptionInterface to its subclass, so PSR-11
 * consumers keep working; this package does not depend on psr/container.
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final class GatewayUnbuildableException extends ContainerException
 *     {
 *         public static function factoryFailed(\Throwable $previous): self
 *         {
 *             return new self(InvoiceCode::GatewayUnbuildable, 'The payment gateway factory failed.', $previous);
 *         }
 *     }
 *
 * The HTTP edge answers 500.
 *
 * References: KaririCode Spec V4.3.3 §8.4; kariricode/contract
 * Exception\ContainerFailure; ADR-001; PHP-FIG PSR-11 §1.3.
 *
 * @see https://www.php-fig.org/psr/psr-11/
 */
abstract class ContainerException extends LogicException implements ContainerFailure
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
