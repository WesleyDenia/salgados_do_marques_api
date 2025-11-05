<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['external_id','name','description','display_order','active'];
    protected $casts = ['active'=>'boolean','display_order'=>'integer'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
