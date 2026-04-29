<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppQueueItem extends Model
{
    protected $table = 'whatsapp_queue_items';

    public const TYPE_RECEIVED = 'received';
    public const TYPE_OTP = 'otp';
    public const TYPE_ORDER_PLACED = 'order_placed';

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_MANUALLY_CLOSED = 'manually_closed';

    public const TERMINAL_STATUSES = [
        self::STATUS_SENT,
        self::STATUS_FAILED,
        self::STATUS_MANUALLY_CLOSED,
    ];

    protected $fillable = [
        'type',
        'direction',
        'entity_type',
        'entity_id',
        'recipient_name',
        'phone',
        'external_message_id',
        'message',
        'payload',
        'status',
        'attempts',
        'last_error',
        'last_error_code',
        'queued_at',
        'started_at',
        'finished_at',
        'sent_at',
        'received_at',
        'manual_note',
        'manually_closed_at',
        'created_by',
        'resolved_by',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'payload' => 'array',
        'attempts' => 'integer',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'manually_closed_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
