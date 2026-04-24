<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'nif',
        'password',
        'phone',
        'birth_date',
        'role',
        'active',
        'street',
        'city',
        'postal_code',
        'theme',
        'external_id',
        'erp_sync_status',
        'erp_sync_attempts',
        'erp_sync_error',
        'erp_sync_attempted_at',
        'erp_synced_at',
        'last_login',
        'loyalty_synced',
        'loyalty_synced_at',
        'lgpd_consent_at',
        'lgpd_consent_version',
        'lgpd_consent_hash',
        'lgpd_consent_channel',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'birth_date' => 'date',
        'active' => 'boolean',
        'erp_sync_attempts' => 'integer',
        'erp_sync_attempted_at' => 'datetime',
        'erp_synced_at' => 'datetime',
        'lgpd_consent_at' => 'datetime',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function userCoupons(): HasMany
    {
        return $this->hasMany(UserCoupon::class);
    }

    public function loyaltyAccount(): HasOne
    {
        return $this->hasOne(LoyaltyAccount::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
