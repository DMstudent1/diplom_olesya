<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SufficientStock implements ValidationRule
{
    protected $productId;

    public function __construct($productId)
    {
        $this->productId = $productId;
    }
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $product = Product::where('id', $this->productId)->first();

        if (!$product) {
            $fail('Товар не найден.');
            return;
        }

        if ($product->count < $value) {
            $fail("Недостаточно товара. Доступно: {$product->count} шт.");
        }
    }
}
