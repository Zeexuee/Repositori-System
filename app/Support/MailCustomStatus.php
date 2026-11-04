<?php

declare(strict_types=1);

namespace App\Support;

class MailCustomStatus
{
    /**
     * Default status suggestions presented in the status combobox.
     *
     * @var list<string>
     */
    public const DEFAULTS = ['Tembusan', 'Undangan'];

    /**
     * Extract and normalize distinct statuses, prepending default statuses.
     *
     * @param iterable<mixed> $existingValues
     * @return list<string>
     */
    public static function extractDistinct(iterable $existingValues = []): array
    {
        $seen = [];
        $result = [];

        foreach (self::DEFAULTS as $default) {
            $key = mb_strtolower(trim($default));
            if ($key !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $default;
            }
        }

        foreach ($existingValues as $value) {
            $clean = trim((string) $value);
            if ($clean === '') {
                continue;
            }
            $key = mb_strtolower($clean);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $clean;
            }
        }

        return $result;
    }
}

