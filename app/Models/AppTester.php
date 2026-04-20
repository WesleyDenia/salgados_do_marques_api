<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppTester extends Model
{
    use HasFactory;

    public const STATUS_REGISTERED = 'Registrado';
    public const STATUS_ACCOUNT_CREATED = 'Conta criada';
    public const STATUS_TESTING = 'Testando';
    public const STATUSES = [
        self::STATUS_REGISTERED,
        self::STATUS_ACCOUNT_CREATED,
        self::STATUS_TESTING,
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'operating_system',
        'status',
        'is_android_eligible',
        'consent_at',
        'source_path',
        'invite_sent_at',
        'notes',
    ];

    protected $casts = [
        'is_android_eligible' => 'boolean',
        'consent_at' => 'datetime',
        'invite_sent_at' => 'datetime',
    ];
}
