<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendusDiscountCardImport extends Model
{
    public const STATUS_DOWNLOADED = 'downloaded';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_MANUALLY_CLOSED = 'manually_closed';

    protected $fillable = [
        'external_id',
        'external_code',
        'vendus_status',
        'date_used',
        'sync_status',
        'sync_attempts',
        'sync_error',
        'payload',
        'user_coupon_id',
        'downloaded_at',
        'queued_at',
        'processed_at',
        'manually_closed_at',
        'manually_closed_by',
        'manual_note',
    ];

    protected $casts = [
        'payload' => 'array',
        'date_used' => 'datetime',
        'downloaded_at' => 'datetime',
        'queued_at' => 'datetime',
        'processed_at' => 'datetime',
        'manually_closed_at' => 'datetime',
        'sync_attempts' => 'integer',
    ];

    public function userCoupon(): BelongsTo
    {
        return $this->belongsTo(UserCoupon::class);
    }

    public function manuallyClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manually_closed_by');
    }
}
