<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppTester extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'operating_system',
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
