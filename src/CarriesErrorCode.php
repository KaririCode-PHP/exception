<?php

declare(strict_types=1);

namespace KaririCode\Exception;

use Throwable;

/**
 * What every exception built on this package carries: a case of the
 * component's ErrorCode enum, the matching getCode(), and a message that is
 * safe to write to a log. The six family bases use it. A component that
 * keeps its own abstract root class, one that extends an SPL exception, can
 * use it there instead of extending a base.
 *
 * The message reads `[PREFIX_code] text`. Control characters, Unicode line
 * separators and bidirectional controls become `\uXXXX`-style escapes and
 * invalid UTF-8 becomes U+FFFD, so text that slipped into a message can
 * neither forge a log line (CWE-117) nor break a JSON log encoder.
 *
 * Preconditions: the text states the rule that was broken and never repeats
 * the rejected value (errata E-047); an identifier that helps the operator,
 * such as a store or circuit name, goes through quote(). The class using
 * the trait extends an SPL exception whose constructor takes (message,
 * code, previous). Use the trait or a family base in one hierarchy, never
 * both. A root class that static analysers check strictly declares its own
 * constructor and calls the trait's under an alias, as the family bases do:
 * Psalm 6 does not follow a constructor that a parent takes from a trait.
 *
 * Thread-safety: an exception belongs to the call that threw it; the trait
 * keeps no static state.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     abstract class InvoiceException extends \RuntimeException implements ExceptionInterface
 *     {
 *         use CarriesErrorCode;
 *     }
 *
 * References: KaririCode Spec V4.3.3 §8.4, §23.6 and errata E-047; ADR-001;
 * CWE-117, Improper Output Neutralization for Logs.
 *
 * @see https://cwe.mitre.org/data/definitions/117.html
 */
trait CarriesErrorCode
{
    /** The component's code for this failure; getCode() returns its value. */
    public private(set) ErrorCode $errorCode;

    public function __construct(ErrorCode $errorCode, string $message, ?Throwable $previous = null)
    {
        $code = $errorCode->value;
        $this->errorCode = $errorCode;

        parent::__construct(
            self::neutralise(sprintf('[%s_%s] %s', $errorCode::prefix(), $code, $message)),
            is_int($code) ? $code : 0,
            $previous,
        );
    }

    /**
     * An identifier as a JSON string literal, cut after 128 characters:
     * `api` followed by a line feed becomes `"api\n"`. Never pass a secret
     * or personal data (E-047).
     */
    protected static function quote(string $identifier): string
    {
        $text = self::scrub($identifier);

        if (preg_match('/\A(.{128}).+/su', $text, $match) === 1) {
            $text = $match[1] . '…';
        }

        return json_encode($text, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Escapes C0 and C1 controls, DEL, U+2028/U+2029 and the bidirectional controls. */
    private static function neutralise(string $text): string
    {
        return preg_replace_callback(
            '/[\x{0}-\x{1F}\x{7F}-\x{9F}\x{61C}\x{200E}\x{200F}\x{2028}-\x{202E}\x{2066}-\x{2069}]/u',
            static fn(array $match): string => self::escape($match[0]),
            self::scrub($text),
        ) ?? '';
    }

    /** `\n`, `\t` and the other short JSON escapes, `\uXXXX` for the rest. */
    private static function escape(string $character): string
    {
        $escaped = substr(json_encode($character, JSON_THROW_ON_ERROR), 1, -1);

        return $escaped === $character ? sprintf('\u%04x', ord($character)) : $escaped;
    }

    /**
     * Replaces invalid UTF-8 with U+FFFD; valid text comes back unchanged,
     * because the decode reverses exactly the three entities the encode
     * wrote (quotes are left alone on both sides).
     */
    private static function scrub(string $text): string
    {
        return htmlspecialchars_decode(htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_NOQUOTES);
    }
}
