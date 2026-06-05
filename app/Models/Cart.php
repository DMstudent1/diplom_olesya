<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'carts_products')
            ->withPivot('quantity', 'id');
    }

    public static function getOrCreate($userId)
    {
        return static::firstOrCreate(['user_id' => $userId]);
    }
}
