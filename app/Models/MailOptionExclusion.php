<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Opsi riwayat combobox (pengirim / penerima / status) yang telah dihapus
 * pengguna sehingga tidak lagi ditampilkan sebagai saran.
 */
class MailOptionExclusion extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'value',
        'value_key',
        'created_by',
    ];
}

