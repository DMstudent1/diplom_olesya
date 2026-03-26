<?php

namespace App\Http\Requests;

use App\Rules\SufficientStock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => ['required', 'integer', 'min:1', 'max:999', new SufficientStock($this->input('product_id'))],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Выберите товар',
            'product_id.exists' => 'Товар не найден',
            'quantity.required' => 'Укажите количество товара',
            'quantity.integer' => 'Количество должно быть целым числом',
            'quantity.min' => 'Минимальное количество - 1 шт.',
            'quantity.max' => 'Максимальное количество - 999 шт.',
        ];
    }
}
