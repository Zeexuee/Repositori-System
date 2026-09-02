<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetaFeedback extends Model
{
    use HasFactory;

    protected $table = 'beta_feedback';

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'description',
        'page_url',
        'user_agent',
        'ip_address',
        'attachment_path',
        'status',
        'admin_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'BUG' => 'Error / Bug Aplikasi',
            'UI_UX' => 'Tampilan / UX',
            'PERFORMANCE' => 'Kinerja / Lambat',
            'OTHER' => 'Lainnya',
            default => $this->category,
        };
    }

    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            'PENDING' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
            'IN_PROGRESS' => 'bg-sky-50 text-sky-800 border-sky-300 font-bold',
            'RESOLVED' => 'bg-emerald-50 text-emerald-800 border-emerald-300 font-bold',
            'CLOSED' => 'bg-slate-100 text-slate-700 border-slate-300 font-medium',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return 'Disimpan';
    }
}
