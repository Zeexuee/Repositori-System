<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingMailRevision extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'incoming_mail_revisions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'incoming_mail_id',
        'user_id',
        'revision_number',
        'previous_status',
        'new_status',
        'notes',
        'changed_fields',
        'previous_file_path',
        'file_path',
        'previous_document_photo_path',
        'document_photo_path',
        'previous_signature_path',
        'signature_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'changed_fields' => 'array',
        ];
    }

    /**
     * Get the incoming mail that owns the revision.
     */
    public function incomingMail(): BelongsTo
    {
        return $this->belongsTo(IncomingMail::class, 'incoming_mail_id');
    }

    /**
     * Get the user who made the revision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
