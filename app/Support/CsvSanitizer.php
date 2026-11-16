<?php

namespace App\Support;

/**
 * Neutralizes spreadsheet formula injection in exported CSV cells.
 *
 * Sanitization happens only while the export is generated. Values stored in
 * the audit table are never rewritten, so the audit trail keeps the original
 * (possibly hostile) payload.
 */
final class CsvSanitizer
{
    /**
     * Characters that spreadsheet applications treat as the start of a formula.
     *
     * @var array<int, string>
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@'];

    /**
     * Characters spreadsheets may silently discard before looking for a formula.
     */
    private const LEADING_NOISE = " \t\n\r\0\x0B\x0C";

    public static function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $text = (string) $value;

        return self::shouldEscape($text) ? "'".$text : $text;
    }

    private static function shouldEscape(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        $significant = ltrim($text, self::LEADING_NOISE);

        if ($significant === '') {
            return false;
        }

        if (in_array($significant[0], self::FORMULA_TRIGGERS, true)) {
            return true;
        }

        // A cell starting with a tab or a line break can be re-parsed by Excel
        // as a new formula boundary, even when the next character is benign.
        return str_contains("\t\n\r", $text[0]);
    }
}
