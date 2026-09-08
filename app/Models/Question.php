<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_type',
        'question',
        'secret_number',
        'collection_label',
        'eyebrow',
        'reward_when_correct',
        'reward_when_wrong',
        'active',
        'display_order',
    ];

    protected $casts = [
        'secret_number' => 'integer',
        'reward_when_correct' => 'integer',
        'reward_when_wrong' => 'integer',
        'active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }
}
