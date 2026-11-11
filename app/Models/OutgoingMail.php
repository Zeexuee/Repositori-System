<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MailRecipient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
        'progress',
        'status',
        'disposition_note',
        'created_at',
    ];

    /**
     * Boot model event listeners.
     */
    protected static function booted(): void
    {
        static::creating(function (OutgoingMail $mail): void {
            if (empty($mail->progress)) {
                $mail->progress = 'READY_TO_DELIVERY';
            }
        });

        static::saving(function (OutgoingMail $mail) {
            if ($mail->recipient !== null) {
                $mail->recipient = MailRecipient::format($mail->recipient) ?? '';
            }

            if (! empty($mail->progress) && ! empty($mail->subject)) {
                $progressTag = '['.Str::upper($mail->progress).']';
                if (preg_match('/^\[(READY_TO_DELIVERY|READY TO DELIVERY|WAITING_RECEIVE|RECEIVE|RECEIVED|REVISI|WAITING_RETURN|RETURN|RETURNED|PROGRES|PROGRESS|IN_PROGRESS|APPROVED|PENDING|SIGNED)\]\s*/i', $mail->subject)) {
                    $mail->subject = (string) preg_replace('/^\[(READY_TO_DELIVERY|READY TO DELIVERY|WAITING_RECEIVE|RECEIVE|RECEIVED|REVISI|WAITING_RETURN|RETURN|RETURNED|PROGRES|PROGRESS|IN_PROGRESS|APPROVED|PENDING|SIGNED)\]\s*/i', $progressTag.' ', $mail->subject);
                }
            }
        });
    }

    /**
     * Get the parsed list of individual recipients.
     *
     * @return list<string>
     */
    public function getRecipientsListAttribute(): array
    {
        return MailRecipient::parse($this->recipient);
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

    /**
     * Get formatted attachments for the outgoing mail (including legacy files).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function formattedAttachments(): Collection
    {
        $this->loadMissing('attachments');

        $existingFilePaths = $this->attachments->pluck('file_path')->all();
        $legacyAttachments = collect();

        if ($this->file_path && ! in_array($this->file_path, $existingFilePaths, true)) {
            $isImg = (bool) preg_match('/\.(jpe?g|png|webp)$/i', $this->file_path);

            $legacyAttachments->push([
                'id' => 'legacy-file',
                'file_name' => basename($this->file_path),
                'file_size' => Storage::disk('local')->exists($this->file_path) ? Storage::disk('local')->size($this->file_path) : null,
                'mime_type' => $isImg ? 'image/jpeg' : 'application/pdf',
                'is_image' => $isImg,
                'is_legacy' => true,
                'preview_url' => route('document.download', ['path' => $this->file_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $this->file_path]),
                'created_at' => $this->created_at?->format('d/m/Y H:i'),
            ]);
        }

        $attachments = $this->attachments->map(function (OutgoingMailAttachment $att) {
            $isImg = str_starts_with((string) $att->mime_type, 'image/');

            return [
                'id' => (string) $att->id,
                'file_name' => $att->file_name ?? basename($att->file_path),
                'file_size' => $att->file_size,
                'mime_type' => $att->mime_type,
                'is_image' => $isImg,
                'is_legacy' => false,
                'preview_url' => route('document.download', ['path' => $att->file_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $att->file_path]),
                'created_at' => $att->created_at?->format('d/m/Y H:i'),
            ];
        });

        return $legacyAttachments->merge($attachments)->values();
    }
}
