<?php

declare(strict_types=1);

namespace KaririCode\Exception;

/**
 * The run of four-digit error codes the ecosystem registry
 * (docs/CODE-BLOCKS.md) assigns to one component, such as 8300–8399 for the
 * rate limiter. An edge or a log query identifies a failure by its contract
 * family plus getCode() (Spec V4.3.3 §8.4), which holds only while the
 * blocks of different components stay disjoint; overlaps() is that check.
 *
 * Preconditions: FLOOR <= first <= last <= CEILING. Anything else throws
 * InvalidCodeBlockException (EX_1500) from the constructor, so every
 * CodeBlock that exists is valid.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example: `(new CodeBlock(8300, 8399))->contains(8310)` is true, and so is
 * `->overlaps(new CodeBlock(8350, 8449))`.
 *
 * References: KaririCode Spec V4.3.3 §6 (value objects) and §8.4; ADR-002.
 *
 * @see https://github.com/KaririCode-PHP/exception/blob/main/docs/CODE-BLOCKS.md
 */
final readonly class CodeBlock
{
    /** Smallest code of any block: codes have four digits. */
    public const int FLOOR = 1000;

    /** Largest code of any block. */
    public const int CEILING = 9999;

    /**
     * @throws InvalidCodeBlockException when the bounds are reversed or leave FLOOR–CEILING
     */
    public function __construct(
        public int $first,
        public int $last,
    ) {
        if ($first < self::FLOOR || $last > self::CEILING || $first > $last) {
            throw InvalidCodeBlockException::invalidBounds();
        }
    }

    public function contains(int $code): bool
    {
        return $code >= $this->first && $code <= $this->last;
    }

    public function overlaps(self $other): bool
    {
        return $this->first <= $other->last && $other->first <= $this->last;
    }
}
