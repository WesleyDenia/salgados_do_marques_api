<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationToken extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','token','platform','last_used_at'];

    protected $casts = [
        'last_used_at'=>'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
