<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrbanCampaignCouponClaim extends Model
{
    use HasFactory;

    public const STATUS_PENDING_ERP = 'pending_erp';

    public const STATUS_SYNCING_ERP = 'syncing_erp';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_FAILED_ERP = 'failed_erp';

    protected $fillable = [
        'phone',
        'coupon_type',
        'coupon_config_id',
        'qr_code_id',
        'question_id',
        'response_id',
        'is_correct',
        'discount_type',
        'amount',
        'external_id',
        'code',
        'status',
        'erp_sync_error',
        'erp_synced_at',
        'erp_sync_attempts',
    ];

    protected $casts = [
        'coupon_config_id' => 'integer',
        'qr_code_id' => 'integer',
        'question_id' => 'integer',
        'response_id' => 'integer',
        'is_correct' => 'boolean',
        'amount' => 'decimal:2',
        'erp_synced_at' => 'datetime',
        'erp_sync_attempts' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(UrbanCampaignCouponConfig::class, 'coupon_config_id');
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(QuestionResponse::class);
    }

    public function hasSyncedCode(): bool
    {
        return $this->status === self::STATUS_SYNCED && filled($this->code);
    }
}
