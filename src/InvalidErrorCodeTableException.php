<?php

declare(strict_types=1);

namespace KaririCode\Exception;

/**
 * A component's ErrorCode enum breaks a rule of the registry: it is not an
 * ErrorCode enum at all, its prefix has the wrong shape, it is backed by
 * strings, or one of its cases lies outside its block. ErrorCodeTable
 * throws it from the component's conformance test.
 *
 * Preconditions: the enum's class name may appear in the message: PHP has
 * validated it as an identifier. The rejected prefix or value never does
 * (E-047); notAnErrorCode() repeats nothing, because its input was not
 * validated.
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example: `new ErrorCodeTable(StrayCode::class)` throws codeOutsideBlock(),
 * code 1504, message "[EX_1504] Case App\StrayCode::Late lies outside the
 * block that App\StrayCode declares."
 *
 * References: KaririCode Spec V4.3.3 §8.4; ADR-002.
 *
 * @see https://github.com/KaririCode-PHP/exception/blob/main/docs/adr/ADR-002-code-block-registry.md
 */
final class InvalidErrorCodeTableException extends ConfigurationException
{
    public static function notAnErrorCode(): self
    {
        return new self(
            ExceptionCode::NotAnErrorCode,
            'ErrorCodeTable takes the class name of an enum that implements KaririCode\Exception\ErrorCode.',
        );
    }

    /** @param class-string<ErrorCode> $enum */
    public static function invalidPrefix(string $enum): self
    {
        return new self(
            ExceptionCode::InvalidPrefix,
            sprintf('The prefix of %s must be a capital letter followed by 1 to 3 capital letters or digits.', $enum),
        );
    }

    /** @param class-string<ErrorCode> $enum */
    public static function notIntBacked(string $enum): self
    {
        return new self(
            ExceptionCode::NotIntBacked,
            sprintf('%s must be backed by int, because getCode() returns the case value.', $enum),
        );
    }

    /** @param class-string<ErrorCode> $enum */
    public static function codeOutsideBlock(string $enum, string $case): self
    {
        return new self(
            ExceptionCode::CodeOutsideBlock,
            sprintf('Case %s::%s lies outside the block that %s declares.', $enum, $case, $enum),
        );
    }
}
