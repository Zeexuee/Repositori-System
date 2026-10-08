<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingMailRevisionNote extends Model
{
    use HasUuids;

    /**
     * Note recorded when a document enters the REVISI status.
     */
    public const TYPE_REQUESTED = 'REQUESTED';

    /**
     * Note recorded when a document leaves the REVISI status.
     */
    public const TYPE_RESOLVED = 'RESOLVED';

    /**
     * Keep millisecond precision so the history order stays deterministic.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = [
        'outgoing_mail_id',
        'type',
        'note',
        'created_by',
    ];

    public function outgoingMail(): BelongsTo
    {
        return $this->belongsTo(OutgoingMail::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRequest(): bool
    {
        return $this->type === self::TYPE_REQUESTED;
    }
}
