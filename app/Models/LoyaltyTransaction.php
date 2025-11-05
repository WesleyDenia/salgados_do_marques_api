<?php

// app/Models/LoyaltyTransaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyTransaction extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','type','points','reason','meta'];
    protected $casts = ['meta'=>'array'];

    public function user(){ return $this->belongsTo(User::class); }
}
