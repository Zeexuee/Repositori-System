<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OutgoingMail;

/**
 * Generates the next outgoing mail number for today.
 *
 * The sequence is derived from the highest existing suffix, including
 * soft-deleted rows, so a deleted number is never reused. Reuse would violate
 * the unique index on outgoing_mails.mail_number.
 */
final class OutgoingMailNumber
{
    public static function next(): string
    {
        $prefix = 'SK-'.now()->format('Ymd').'-';

        $maxSequence = OutgoingMail::withTrashed()
            ->where('mail_number', 'like', $prefix.'%')
            ->pluck('mail_number')
            ->reduce(function (int $max, ?string $mailNumber) use ($prefix): int {
                $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

                if ($mailNumber !== null && preg_match($pattern, $mailNumber, $matches) === 1) {
                    return max($max, (int) $matches[1]);
                }

                return $max;
            }, 0);

        return $prefix.sprintf('%04d', $maxSequence + 1);
    }
}
