<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CartRequest;
use App\Http\Requests\CartUpdateRequest;
use App\Models\Cart;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Log;

class CartController extends Controller
{
    private function getOrCreateCart()
    {
        $userId = Auth::id();

        return Cart::firstOrCreate(
            ['user_id' => $userId],
        );
    }

    public function index()
    {
        $cart = $this->getOrCreateCart();
        $products = $cart->products()->get();
        $total = $products->sum(function ($product) {
            return $product->price * $product->pivot->quantity;
        });
        return response()->json([
            'items' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'old_price' => $product->old_price,
                    'quantity' => $product->pivot->quantity,
                    'pivot_id' => $product->pivot->id,
                ];
            }),
            'total' => $total,
            'items_count' => $products->sum('pivot.quantity'),
            'unique_items' => $products->count()
        ]);
    }

    public function store(CartRequest $request)
    {
        $cart = $this->getOrCreateCart();
        $productId = $request->product_id;
        $quantity = $request->quantity;

        $cart->products()->attach($productId, [
            'quantity' => $quantity
        ]);
    }

    public function update(CartRequest $request)
    {
        $cart = $this->getOrCreateCart();
        $cart->products()->where('product_id', $request->product_id)->updateExistingPivot($request->product_id, [
            'quantity' => $request->quantity
        ]);
    }

    public function destroy()
    {
        $cart = $this->getOrCreateCart();

        $cart->products()->detach();
    }
    public function destroyFromId(Request $request)
    {
        $cart = $this->getOrCreateCart();
        $cart->products()->detach($request->product_id);
    }
}
