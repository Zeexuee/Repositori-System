<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingMailDispositionRecipient extends Model
{
    use HasUuids;

    protected $table = 'incoming_mail_disposition_recipients';

    protected $fillable = [
        'incoming_mail_id',
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

    public function incomingMail(): BelongsTo
    {
        return $this->belongsTo(IncomingMail::class, 'incoming_mail_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
