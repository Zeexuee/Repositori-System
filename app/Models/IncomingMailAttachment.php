<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingMailAttachment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'incoming_mail_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'incoming_mail_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the incoming mail that owns the attachment.
     */
    public function incomingMail(): BelongsTo
    {
        return $this->belongsTo(IncomingMail::class, 'incoming_mail_id');
    }
}
