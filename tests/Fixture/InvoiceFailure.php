<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Contract\Exception\ExceptionInterface;
use KaririCode\Exception\CarriesErrorCode;

/** A component that keeps its own root class uses the trait directly. */
abstract class InvoiceFailure extends \RuntimeException implements ExceptionInterface
{
    use CarriesErrorCode;
}
