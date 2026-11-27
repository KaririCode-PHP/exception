<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use BackedEnum;

/**
 * The error codes of one component, declared once as an int-backed enum
 * whose cases sit inside the block the component holds in the ecosystem
 * registry (docs/CODE-BLOCKS.md). An exception built on this package
 * carries one case: getCode() returns its value and the message opens with
 * `[PREFIX_value]`, so logs stay greppable across components.
 *
 * Preconditions: back the enum with int; keep every case inside block();
 * prefix() returns a capital letter followed by one to three capital
 * letters or digits, unique in the registry. ErrorCodeTable checks all
 * three in the component's conformance test. Codes are stable API: retire a
 * case instead of reusing its value.
 *
 * Thread-safety: enum cases are immutable singletons.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     enum InvoiceCode: int implements ErrorCode
 *     {
 *         case AmountBelowZero = 7200;
 *         case LedgerUnreachable = 7250;
 *
 *         public static function prefix(): string { return 'IV'; }
 *
 *         public static function block(): CodeBlock { return new CodeBlock(7200, 7299); }
 *     }
 *
 * References: KaririCode Spec V4.3.3 §8.4 (code sub-ranges) and §18 (enums
 * over magic numbers); ADR-001; ADR-002.
 *
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 */
interface ErrorCode extends BackedEnum
{
    /** Opens every message of the component: `IV` gives `[IV_7200]`. */
    public static function prefix(): string;

    /** The block of codes registered for the component; every case falls inside it. */
    public static function block(): CodeBlock;
}
