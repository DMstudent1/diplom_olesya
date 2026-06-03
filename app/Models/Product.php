<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    
    protected $fillable = [
        'name',
        'category_id',
        'count',
        'description',
        'old_price',
        'price',
        'characteristics'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'carts_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
