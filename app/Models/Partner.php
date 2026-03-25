<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function campaigns()
    {
        return $this->hasMany(PartnerCampaign::class);
    }
}
