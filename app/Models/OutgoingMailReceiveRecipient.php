<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingMailReceiveRecipient extends Model
{
    use HasUuids;

    protected $fillable = [
        'outgoing_mail_id',
        'recipient_name',
        'signer_name',
        'signature_path',
        'signed_at',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function outgoingMail(): BelongsTo
    {
        return $this->belongsTo(OutgoingMail::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getHasSignatureAttribute(): bool
    {
        return !empty($this->signature_path);
    }
}
