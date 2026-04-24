<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpSyncTask extends Model
{
    public const OPERATION_SYNC_CUSTOMER = 'sync_customer';
    public const OPERATION_CREATE_DISCOUNT_CARD = 'create_discount_card';
    public const OPERATION_IMPORT_DISCOUNT_CARD = 'import_discount_card';
    public const OPERATION_SYNC_COUPON_USAGE = 'sync_coupon_usage';

    public const ENTITY_USER = 'user';
    public const ENTITY_USER_COUPON = 'user_coupon';
    public const ENTITY_VENDUS_DISCOUNT_CARD_IMPORT = 'vendus_discount_card_import';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_MANUAL_REVIEW = 'manual_review';

    public const TERMINAL_STATUSES = [
        self::STATUS_SYNCED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_MANUAL_REVIEW,
    ];

    protected $fillable = [
        'operation',
        'entity_type',
        'entity_id',
        'active_key',
        'status',
        'attempts',
        'external_id',
        'external_code',
        'last_error',
        'last_error_code',
        'queued_at',
        'started_at',
        'finished_at',
        'next_retry_at',
        'created_by',
        'resolved_by',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'attempts' => 'integer',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'next_retry_at' => 'datetime',
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

    public static function makeActiveKey(string $operation, string $entityType, int $entityId): string
    {
        return implode(':', [$operation, $entityType, $entityId]);
    }
}
