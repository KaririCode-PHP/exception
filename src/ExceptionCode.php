<?php

declare(strict_types=1);

namespace KaririCode\Exception;

/**
 * Codes of kariricode/exception itself: block 1500–1599, prefix EX, in the
 * registry. They flag mistakes in a component's code table, so they surface
 * in that component's conformance test long before production.
 *
 * Preconditions: values are stable API; a retired case keeps its number
 * out of circulation.
 *
 * Thread-safety: enum cases are immutable singletons.
 *
 * Side effects: none.
 *
 * Example: `ExceptionCode::CodeOutsideBlock->value` is 1504, and the message
 * of the exception that carries it opens with `[EX_1504]`.
 *
 * References: ADR-002; docs/CODE-BLOCKS.md.
 *
 * @see https://github.com/KaririCode-PHP/exception/blob/main/docs/CODE-BLOCKS.md
 */
enum ExceptionCode: int implements ErrorCode
{
    case InvalidCodeBlock = 1500;
    case NotAnErrorCode = 1501;
    case InvalidPrefix = 1502;
    case NotIntBacked = 1503;
    case CodeOutsideBlock = 1504;

    #[\Override]
    public static function prefix(): string
    {
        return 'EX';
    }

    #[\Override]
    public static function block(): CodeBlock
    {
        return new CodeBlock(1500, 1599);
    }
}
