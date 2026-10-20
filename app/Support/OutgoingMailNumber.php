<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OutgoingMail;
use Illuminate\Support\Facades\DB;

/**
 * Generates the next outgoing mail number for today.
 *
 * The counter lives in the outgoing_mail_sequences table and is locked inside
 * a transaction, so concurrent requests cannot receive the same number.
 * Existing numbers are also taken into account (including soft-deleted rows
 * and manually entered numbers) to avoid collisions with the unique index on
 * outgoing_mails.mail_number.
 */
final class OutgoingMailNumber
{
    private const SEQUENCE_TABLE = 'outgoing_mail_sequences';

    public static function next(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'SK-'.$date.'-';

        return DB::transaction(function () use ($date, $prefix): string {
            DB::table(self::SEQUENCE_TABLE)->insertOrIgnore([
                'sequence_date' => $date,
                'last_number' => self::highestExistingSequence($prefix),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lastNumber = (int) DB::table(self::SEQUENCE_TABLE)
                ->where('sequence_date', $date)
                ->lockForUpdate()
                ->value('last_number');

            // Manual mail numbers can occupy a higher sequence than the counter.
            $nextNumber = max($lastNumber, self::highestExistingSequence($prefix)) + 1;

            DB::table(self::SEQUENCE_TABLE)
                ->where('sequence_date', $date)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return $prefix.sprintf('%04d', $nextNumber);
        });
    }

    private static function highestExistingSequence(string $prefix): int
    {
        return OutgoingMail::withTrashed()
            ->where('mail_number', 'like', $prefix.'%')
            ->pluck('mail_number')
            ->reduce(function (int $max, ?string $mailNumber) use ($prefix): int {
                $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

                if ($mailNumber !== null && preg_match($pattern, $mailNumber, $matches) === 1) {
                    return max($max, (int) $matches[1]);
                }

                return $max;
            }, 0);
    }
}
