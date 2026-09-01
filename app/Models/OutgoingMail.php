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
        'dispositions_data',
        'file_path',
        'created_by',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispositions_data' => 'array',
        ];
    }

    /**
     * Boot model event listeners.
     */
    protected static function booted(): void
    {
        static::saving(function (OutgoingMail $mail) {
            if (! empty($mail->status) && ! empty($mail->subject)) {
                $statusTag = '[' . Str::upper($mail->status) . ']';
                if (preg_match('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|WAITING)\]\s*/i', $mail->subject)) {
                    $mail->subject = (string) preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|WAITING)\]\s*/i', $statusTag . ' ', $mail->subject);
                }
            }
        });

        static::saved(function (OutgoingMail $mail) {
            if (! empty($mail->subject) && ! empty($mail->status)) {
                $cleanSubject = trim((string) preg_replace('/^\[(PROGRES|PROGRESS|IN_PROGRESS|RETURN|RETURNED|RECEIVE|RECEIVED|APPROVED|PENDING|WAITING)\]\s*/i', '', $mail->subject));

                if (! empty($cleanSubject)) {
                    $incomingMails = IncomingMail::where('subject', $cleanSubject)
                        ->orWhere('subject', 'like', '%' . $cleanSubject . '%')
                        ->orWhere('mail_number', $mail->mail_number)
                        ->get();

                    foreach ($incomingMails as $incoming) {
                        $updateData = ['status' => $mail->status];
                        if (in_array($mail->status, ['RETURN', 'RETURNED'], true) && empty($incoming->outgoing_date)) {
                            $updateData['outgoing_date'] = now()->toDateString();
                        }
                        $incoming->updateQuietly($updateData);
                    }
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
}
