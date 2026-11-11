<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\IncomingMail;
use App\Models\MailOptionExclusion;
use App\Models\OutgoingMail;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal daftar saran combobox registrasi on-the-fly.
 *
 * Daftar dibangun dari nilai unik yang sudah tersimpan di surat masuk / keluar,
 * lalu dikurangi opsi yang sudah dihapus pengguna (tabel mail_option_exclusions)
 * sehingga opsi yang dihapus tidak muncul kembali setelah halaman dimuat ulang.
 */
class MailOptionList
{
    public const SENDERS = 'senders';

    public const RECIPIENTS = 'recipients';

    public const STATUSES = 'statuses';

    /**
     * @var list<string>
     */
    public const TYPES = [self::SENDERS, self::RECIPIENTS, self::STATUSES];

    /**
     * Kunci normalisasi (case-insensitive) untuk membandingkan opsi.
     */
    public static function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * @return Collection<int, string>
     */
    public static function senders(): Collection
    {
        $raw = IncomingMail::whereNotNull('sender')
            ->where('sender', '!=', '')
            ->distinct()
            ->pluck('sender');

        $seen = [];
        $result = [];
        foreach ($raw as $value) {
            $clean = trim((string) $value);
            $key = self::key($clean);
            if ($clean === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $clean;
        }

        return self::withoutExcluded(self::SENDERS, $result);
    }

    /**
     * @return Collection<int, string>
     */
    public static function recipients(): Collection
    {
        $raw = IncomingMail::whereNotNull('recipient')->where('recipient', '!=', '')->distinct()->pluck('recipient')
            ->merge(OutgoingMail::whereNotNull('recipient')->where('recipient', '!=', '')->distinct()->pluck('recipient'));

        return self::withoutExcluded(self::RECIPIENTS, MailRecipient::extractDistinct($raw));
    }

    /**
     * @return Collection<int, string>
     */
    public static function statuses(): Collection
    {
        $raw = IncomingMail::whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status')
            ->merge(OutgoingMail::whereNotNull('status')->where('status', '!=', '')->distinct()->pluck('status'));

        return self::withoutExcluded(self::STATUSES, MailCustomStatus::extractDistinct($raw));
    }

    /**
     * Sembunyikan opsi dari daftar saran.
     */
    public static function exclude(string $type, string $value, ?int $userId = null): void
    {
        $clean = trim($value);
        if ($clean === '' || ! in_array($type, self::TYPES, true)) {
            return;
        }

        MailOptionExclusion::updateOrCreate(
            ['type' => $type, 'value_key' => self::key($clean)],
            ['value' => $clean, 'created_by' => $userId]
        );
    }

    /**
     * Tampilkan kembali opsi yang sebelumnya disembunyikan (registrasi ulang).
     */
    public static function restore(string $type, string $value): void
    {
        $clean = trim($value);
        if ($clean === '' || ! in_array($type, self::TYPES, true)) {
            return;
        }

        MailOptionExclusion::where('type', $type)
            ->where('value_key', self::key($clean))
            ->delete();
    }

    /**
     * @param  iterable<int, string>  $values
     * @return Collection<int, string>
     */
    private static function withoutExcluded(string $type, iterable $values): Collection
    {
        $excluded = MailOptionExclusion::where('type', $type)
            ->pluck('value_key')
            ->flip();

        return collect($values)
            ->filter(fn ($value) => ! $excluded->has(self::key((string) $value)))
            ->values();
    }
}

