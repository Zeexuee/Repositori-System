<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OutgoingMail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outgoing_mails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mail_number',
        'subject',
        'recipient',
        'file_path',
        'created_by',
        'status',
        'disposition_note',
    ];

    /**
     * Boot model event listeners.
     */
    protected static function booted(): void
    {
        static::saving(function (OutgoingMail $mail) {
            if (! empty($mail->status) && ! empty($mail->subject)) {
                $statusTag = '['.Str::upper($mail->status).']';
                if (preg_match('/^\[(READY_TO_DELIVERY|READY TO DELIVERY|WAITING_RECEIVE|RECEIVE|RECEIVED|REVISI|WAITING_RETURN|RETURN|RETURNED|PROGRES|PROGRESS|IN_PROGRESS|APPROVED|PENDING|SIGNED)\]\s*/i', $mail->subject)) {
                    $mail->subject = (string) preg_replace('/^\[(READY_TO_DELIVERY|READY TO DELIVERY|WAITING_RECEIVE|RECEIVE|RECEIVED|REVISI|WAITING_RETURN|RETURN|RETURNED|PROGRES|PROGRESS|IN_PROGRESS|APPROVED|PENDING|SIGNED)\]\s*/i', $statusTag.' ', $mail->subject);
                }
            }
        });
    }

    /**
     * Get the user who created the outgoing mail.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the file change history for the outgoing mail.
     */
    public function fileHistories(): HasMany
    {
        return $this->hasMany(OutgoingMailFileHistory::class, 'outgoing_mail_id')->latest();
    }

    /**
     * Get the return recipients (signature workflow) for this outgoing mail.
     */
    public function returnRecipients(): HasMany
    {
        return $this->hasMany(OutgoingMailReturnRecipient::class, 'outgoing_mail_id');
    }

    /**
     * Get the revision notes for this outgoing mail.
     */
    public function revisionNotes(): HasMany
    {
        return $this->hasMany(OutgoingMailRevisionNote::class, 'outgoing_mail_id')->latest();
    }

    /**
     * Get the attachments/documents for this outgoing mail.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(OutgoingMailAttachment::class, 'outgoing_mail_id');
    }
}
