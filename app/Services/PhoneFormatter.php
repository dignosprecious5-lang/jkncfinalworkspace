<?php

namespace App\Services;

class PhoneFormatter
{
    /**
     * Standardizes a phone number for display.
     * Preferred Philippine mobile format: 09XXXXXXXXX (11 digits).
     *
     * Rules:
     * - If starts with +639 or 639 (12-13 digits total), transforms to 09XXXXXXXXX.
     * - If 10 digits starting with 9, transforms to 09XXXXXXXXX.
     * - If 11 digits starting with 09, keeps as 09XXXXXXXXX.
     * - If legitimate international number (e.g. +1..., +44...), preserves original.
     * - Returns fallback or null if empty.
     */
    public static function format(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);
        if ($trimmed === '') {
            return '';
        }

        // Clean out spaces, dashes, parentheses, dots
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $trimmed);

        // Case 1: Philippine country code +639, 639, +6309, 00639 (e.g. +639171234567, 639171234567, +6309171234567)
        if (preg_match('/^(\+63|63|0063)0?(9\d{9})$/', $cleaned, $matches)) {
            return '0' . $matches[2];
        }

        // Case 2: 10 digits starting with 9 (e.g. 9171234567)
        if (preg_match('/^9\d{9}$/', $cleaned)) {
            return '0' . $cleaned;
        }

        // Case 3: 11 digits starting with 09 (e.g. 09171234567)
        if (preg_match('/^09\d{9}$/', $cleaned)) {
            return $cleaned;
        }

        // Case 4: International number starting with + (e.g. +1 555 123 4567)
        if (str_starts_with($cleaned, '+')) {
            return $trimmed;
        }

        // Default: return cleaned or trimmed
        return $trimmed;
    }

    /**
     * Normalizes a phone number for saving in the database or form inputs.
     */
    public static function normalize(?string $phone): ?string
    {
        return self::format($phone);
    }

    /**
     * Checks if a number matches Philippine mobile pattern (11 digits starting with 09).
     */
    public static function isPhilippineMobile(?string $phone): bool
    {
        if (!$phone) {
            return false;
        }
        $formatted = self::format($phone);
        return (bool) preg_match('/^09\d{9}$/', (string) $formatted);
    }
}
