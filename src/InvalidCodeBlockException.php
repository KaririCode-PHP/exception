<?php

declare(strict_types=1);

namespace KaririCode\Exception;

/**
 * A component declared a code block outside 1000–9999 or with its bounds
 * reversed. The first caller of the component's block() is its conformance
 * test, so the mistake surfaces there.
 *
 * Preconditions: none. The message states the rule and does not repeat the
 * rejected bounds (E-047).
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example: `new CodeBlock(8399, 8300)` throws it, code 1500, message
 * "[EX_1500] A code block runs from first to last inside 1000-9999, with first <= last."
 *
 * References: KaririCode Spec V4.3.3 §8.4; ADR-002.
 *
 * @see https://github.com/KaririCode-PHP/exception/blob/main/docs/adr/ADR-002-code-block-registry.md
 */
final class InvalidCodeBlockException extends ConfigurationException
{
    public static function invalidBounds(): self
    {
        return new self(
            ExceptionCode::InvalidCodeBlock,
            'A code block runs from first to last inside 1000-9999, with first <= last.',
        );
    }
}
