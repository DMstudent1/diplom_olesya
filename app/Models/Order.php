<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'sum' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

// В модели Order
public function getProductsList()
{
    \Log::info('Method called!');
    
    $productsRaw = $this->getRawOriginal('products');
    
    if (!$productsRaw) {
        return collect();
    }
    
    $productIds = json_decode($productsRaw, true);
    
    if (!is_array($productIds)) {
        return collect();
    }
    
    $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
    
    $result = [];
    foreach ($productIds as $productId) {
        $product = $products[$productId] ?? null;
        if ($product) {
            $result[] = (object) [
                'product' => $product,
                'product_id' => $productId,
                'quantity' => 1,
                'price' => (float) $product->price,
                'total' => (float) $product->price
            ];
        }
    }
    
    return collect($result);
}
    
    // Общая сумма заказа
    public function getTotalSumAttribute()
    {
        return $this->products_list->sum('total');
    }
    
    // Общее количество товаров
    public function getTotalQuantityAttribute()
    {
        return $this->products_list->sum('quantity');
    }
}