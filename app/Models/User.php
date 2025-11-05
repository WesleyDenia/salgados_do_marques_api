<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

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
        'birth_date' => 'date',
        'active' => 'boolean',
        'lgpd_consent_at' => 'datetime',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }
}
