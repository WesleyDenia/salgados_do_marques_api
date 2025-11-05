<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'code',
        'image',
        'discount_percent',
        'starts_at',
        'ends_at',
        'is_global',
        'active',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_global' => 'boolean',
        'active'    => 'boolean',
        'discount_percent' => 'decimal:2',
    ];

    // 🔹 (Opcional) Relacionamento com o criador
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
