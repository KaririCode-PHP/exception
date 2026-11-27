<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Fixture;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

/** Codes of a fictional invoicing component, in the block the registry reserves for examples. */
enum InvoiceCode: int implements ErrorCode
{
    case AmountBelowZero = 7200;
    case InvoiceMissing = 7210;
    case TaxRateUnset = 7220;
    case MalformedPayload = 7230;
    case GatewayUnbuildable = 7240;
    case LedgerUnreachable = 7250;
    case LegacyFailure = 7299;

    public static function prefix(): string
    {
        return 'IV';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
