<?php

declare(strict_types=1);

namespace KaririCode\Exception;

/**
 * A component's ErrorCode enum, checked against the registry rules:
 * int-backed, a prefix of the registry's shape, and every case inside the
 * declared block. Build one in the component's conformance test; the
 * construction is the check, so a table that exists is valid.
 *
 * Preconditions: the constructor throws InvalidErrorCodeTableException
 * (EX_1501–EX_1504) or InvalidCodeBlockException (EX_1500) at the first
 * rule broken. Duplicate values need no check: PHP refuses an enum with two
 * cases of the same value. Overlaps with other components are checked
 * against docs/CODE-BLOCKS.md, not here.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none beyond calling the enum's prefix(), block() and
 * cases().
 *
 * Example:
 *
 *     $table = new ErrorCodeTable(InvoiceCode::class);
 *     self::assertSame('IV', $table->prefix);
 *     self::assertTrue($table->block->overlaps(new CodeBlock(7200, 7299)));
 *
 * References: KaririCode Spec V4.3.3 §8.4 and §19.1 (conformance layer);
 * ADR-001; ADR-002.
 *
 * @see https://github.com/KaririCode-PHP/exception/blob/main/docs/adr/ADR-002-code-block-registry.md
 */
final readonly class ErrorCodeTable
{
    /** @var class-string<ErrorCode> */
    public string $enum;

    public string $prefix;

    public CodeBlock $block;

    /**
     * @throws InvalidErrorCodeTableException when a registry rule is broken
     * @throws InvalidCodeBlockException      when block() is invalid
     */
    public function __construct(string $enum)
    {
        // Only an enum can implement ErrorCode, which extends BackedEnum.
        if (!is_subclass_of($enum, ErrorCode::class)) {
            throw InvalidErrorCodeTableException::notAnErrorCode();
        }

        $prefix = $enum::prefix();

        if (preg_match('/\A[A-Z][A-Z0-9]{1,3}\z/', $prefix) !== 1) {
            throw InvalidErrorCodeTableException::invalidPrefix($enum);
        }

        $block = $enum::block();

        foreach ($enum::cases() as $case) {
            if (!is_int($case->value)) {
                throw InvalidErrorCodeTableException::notIntBacked($enum);
            }

            if (!$block->contains($case->value)) {
                throw InvalidErrorCodeTableException::codeOutsideBlock($enum, $case->name);
            }
        }

        $this->enum = $enum;
        $this->prefix = $prefix;
        $this->block = $block;
    }
}
