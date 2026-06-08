<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'user_id',
        'products',
        'delivery_point',
        'delivery_uuid',
        'status',
        'sum',
        'payment_id'
    ];

    protected $casts = [
        'products' => 'array',
        'sum' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Получение товаров с пагинацией
    public function getProductsListAttribute()
    {
        if (!is_array($this->products)) {
            return collect();
        }

        $productIds = collect($this->products)->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return collect($this->products)->map(function ($item) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            return (object) [
                'product' => $product,
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? ($product->price ?? 0),
                'total' => ($item['quantity'] ?? 1) * ($item['price'] ?? ($product->price ?? 0))
            ];
        });
    }

}