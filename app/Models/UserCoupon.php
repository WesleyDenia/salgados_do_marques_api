<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserCoupon extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_ERP = 'pending_erp';
    public const STATUS_SYNCING_ERP = 'syncing_erp';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED_ERP = 'failed_erp';
    public const STATUS_MANUAL_REVIEW = 'manual_review';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DONE = 'done';

    public const ACTIVE_REUSABLE_STATUSES = [
        self::STATUS_PENDING_ERP,
        self::STATUS_SYNCING_ERP,
        self::STATUS_FAILED_ERP,
        self::STATUS_MANUAL_REVIEW,
        self::STATUS_SYNCED,
    ];

    public const PRIVATE_ERP_STATUSES = [
        self::STATUS_PENDING_ERP,
        self::STATUS_SYNCING_ERP,
        self::STATUS_SYNCED,
        self::STATUS_FAILED_ERP,
        self::STATUS_MANUAL_REVIEW,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'coupon_id',
        'type',
        'loyalty_reward_id',
        'partner_campaign_id',
        'origin_key',
        'external_id',
        'external_code',
        'usage_limit',
        'usage_count',
        'expires_at',
        'active',
        'status',
        'erp_sync_error',
        'erp_synced_at',
        'erp_sync_attempts',
        'redeem_applied_at',
        'redeem_transaction_id',
    ];

    protected $casts = [
        'usage_limit'=>'integer',
        'usage_count'=>'integer',
        'expires_at'=>'datetime',
        'active'=>'boolean',
        'loyalty_reward_id' => 'integer',
        'partner_campaign_id' => 'integer',
        'erp_synced_at' => 'datetime',
        'erp_sync_attempts' => 'integer',
        'redeem_applied_at' => 'datetime',
        'redeem_transaction_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function loyaltyReward()
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function partnerCampaign()
    {
        return $this->belongsTo(PartnerCampaign::class);
    }

    public function erpSyncTasks(): HasMany
    {
        return $this->hasMany(ErpSyncTask::class, 'entity_id')
            ->where('entity_type', ErpSyncTask::ENTITY_USER_COUPON);
    }

    public function latestErpTask(): HasOne
    {
        return $this->hasOne(ErpSyncTask::class, 'entity_id')
            ->where('entity_type', ErpSyncTask::ENTITY_USER_COUPON)
            ->latestOfMany();
    }

    public function hasUsableExternalCode(): bool
    {
        return $this->status === self::STATUS_SYNCED
            && filled($this->external_code)
            && $this->active;
    }
}
