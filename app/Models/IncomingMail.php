<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MailRecipient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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
        'batch_id',
        'receipt_number',
        'mail_number',
        'subject',
        'sender',
        'recipient',
        'received_date',
        'outgoing_date',
        'file_path',
        'document_photo_path',
        'receipt_signature_path',
        'progress',
        'status',
        'disposition_note',
        'notes',
        'recipient_name',
    ];

    /**
     * Boot model event listeners.
     */
    protected static function booted(): void
    {
        static::creating(function (IncomingMail $mail): void {
            if (empty($mail->progress)) {
                $mail->progress = 'RECEIVE';
            }
        });

        static::saving(function (IncomingMail $mail): void {
            if ($mail->recipient !== null) {
                $mail->recipient = MailRecipient::format($mail->recipient);
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_date' => 'datetime',
            'outgoing_date' => 'datetime',
        ];
    }

    /**
     * Get the dispositions for the incoming mail.
     */
    public function dispositions(): HasMany
    {
        return $this->hasMany(MailDisposition::class, 'incoming_mail_id');
    }

    /**
     * Get the recipients recorded for the disposition workflow.
     */
    public function dispositionRecipients(): HasMany
    {
        return $this->hasMany(IncomingMailDispositionRecipient::class)
            ->orderBy('sort_order');
    }

    /**
     * Get the recipients recorded for the return workflow.
     */
    public function returnRecipients(): HasMany
    {
        return $this->hasMany(IncomingMailReturnRecipient::class)
            ->orderBy('sort_order');
    }

    /**
     * Get the revision notes recorded for this document.
     */
    public function revisionNotes(): HasMany
    {
        return $this->hasMany(IncomingMailRevisionNote::class)->latest();
    }

    /**
     * Get all incoming mails in the same batch.
     */
    public function batchItems(): HasMany
    {
        return $this->hasMany(IncomingMail::class, 'batch_id', 'batch_id');
    }

    /**
     * Get the attachments/documents for the incoming mail.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(IncomingMailAttachment::class, 'incoming_mail_id');
    }

    /**
     * Get formatted attachments for the incoming mail (including legacy files).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function formattedAttachments(): Collection
    {
        $this->loadMissing('attachments');

        $existingFilePaths = $this->attachments->pluck('file_path')->all();
        $legacyAttachments = collect();

        if ($this->document_photo_path && ! in_array($this->document_photo_path, $existingFilePaths, true)) {
            $legacyAttachments->push([
                'id' => 'legacy-photo',
                'file_name' => basename($this->document_photo_path),
                'file_size' => Storage::disk('local')->exists($this->document_photo_path) ? Storage::disk('local')->size($this->document_photo_path) : null,
                'mime_type' => 'image/jpeg',
                'is_image' => true,
                'is_legacy' => true,
                'preview_url' => route('document.download', ['path' => $this->document_photo_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $this->document_photo_path]),
                'created_at' => $this->created_at?->format('d/m/Y H:i'),
            ]);
        }

        if ($this->file_path && ! in_array($this->file_path, $existingFilePaths, true) && $this->file_path !== $this->document_photo_path) {
            $legacyAttachments->push([
                'id' => 'legacy-file',
                'file_name' => basename($this->file_path),
                'file_size' => Storage::disk('local')->exists($this->file_path) ? Storage::disk('local')->size($this->file_path) : null,
                'mime_type' => 'application/pdf',
                'is_image' => false,
                'is_legacy' => true,
                'preview_url' => route('document.download', ['path' => $this->file_path, 'inline' => 1]),
                'download_url' => route('document.download', ['path' => $this->file_path]),
                'created_at' => $this->created_at?->format('d/m/Y H:i'),
            ]);
        }

        $attachments = $this->attachments->map(function (IncomingMailAttachment $att) {
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
