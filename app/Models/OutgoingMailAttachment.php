<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingMailAttachment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outgoing_mail_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'outgoing_mail_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the outgoing mail that owns the attachment.
     */
    public function outgoingMail(): BelongsTo
    {
        return $this->belongsTo(OutgoingMail::class, 'outgoing_mail_id');
    }
}
