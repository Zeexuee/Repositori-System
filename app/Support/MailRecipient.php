<?php

declare(strict_types=1);

namespace App\Support;

final class MailRecipient
{
    /**
     * Parse raw recipient value (array, JSON array string, or comma-separated string)
     * into a normalized list of unique non-empty recipient names.
     *
     * @return list<string>
     */
    public static function parse(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $rawItems = [];

        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) || is_numeric($item)) {
                    $rawItems = array_merge($rawItems, self::splitString((string) $item));
                }
            }
        } elseif (is_string($value) || is_numeric($value)) {
            $str = trim((string) $value);
            if (str_starts_with($str, '[')) {
                $decoded = json_decode($str, true);
                if (is_array($decoded)) {
                    return self::parse($decoded);
                }
            }
            $rawItems = self::splitString($str);
        }

        $seen = [];
        $result = [];

        foreach ($rawItems as $item) {
            $trimmed = trim($item);
            if ($trimmed === '') {
                continue;
            }
            $key = mb_strtolower($trimmed);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $trimmed;
            }
        }

        return $result;
    }

    /**
     * Normalize recipient input into a comma-separated string for storage, or null when empty.
     */
    public static function format(mixed $value): ?string
    {
        $items = self::parse($value);

        if ($items === []) {
            return null;
        }

        return implode(', ', $items);
    }

    /**
     * Extract a sorted list of distinct individual recipient names from a collection of stored values.
     *
     * @param  iterable<mixed>  $rawValues
     * @return list<string>
     */
    public static function extractDistinct(iterable $rawValues): array
    {
        $seen = [];
        $distinct = [];

        foreach ($rawValues as $raw) {
            foreach (self::parse($raw) as $item) {
                $key = mb_strtolower($item);
                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $distinct[] = $item;
                }
            }
        }

        sort($distinct, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($distinct);
    }

    /**
     * @return list<string>
     */
    private static function splitString(string $input): array
    {
        $parts = preg_split('/[,;\r\n]+/', $input);

        return is_array($parts) ? array_values($parts) : [$input];
    }
}

