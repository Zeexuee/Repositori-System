<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingMail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'incoming_mails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mail_number',
        'subject',
        'sender',
        'recipient',
        'received_date',
        'outgoing_date',
        'file_path',
        'document_photo_path',
        'receipt_signature_path',
        'status',
        'disposition_note',
        'notes',
        'recipient_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'outgoing_date' => 'date',
        ];
    }

    /**
     * Get the dispositions for the incoming mail.
     */
    public function dispositions(): HasMany
    {
        return $this->hasMany(MailDisposition::class, 'incoming_mail_id');
    }
}
