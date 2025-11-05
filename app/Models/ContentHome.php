<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentHome extends Model
{
    protected $fillable = [
        'display_order',
        'title',
        'image_url',
        'text_body',
        'type',
        'layout',
        'component_name',
        'component_props',
        'cta_label',
        'cta_url',
        'background_color',
        'is_active',
        'publish_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'publish_at' => 'datetime',
        'display_order' => 'integer',
        'component_props' => 'array',
    ];
}
