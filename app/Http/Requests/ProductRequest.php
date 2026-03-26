<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id ?? null;
        return [
            'name' => 'required|string|max:255|unique:products,name,' . $productId,
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0|gt:price',
            'count' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'characteristics' => 'nullable|json',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название товара обязательно для заполнения.',
            'name.unique' => 'Товар с таким названием уже существует.',
            'name.max' => 'Название товара не должно превышать 255 символов.',
            
            'category_id.required' => 'Категория товара обязательна для выбора.',
            'category_id.integer' => 'ID категории должен быть целым числом.',
            'category_id.exists' => 'Выбранная категория не существует.',
            
            'price.required' => 'Цена товара обязательна для заполнения.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Цена не может быть отрицательной.',
            
            'old_price.numeric' => 'Старая цена должна быть числом.',
            'old_price.min' => 'Старая цена не может быть отрицательной.',
            'old_price.gt' => 'Старая цена должна быть больше текущей цены.',
            
            'count.required' => 'Количество товара обязательно для заполнения.',
            'count.integer' => 'Количество должно быть целым числом.',
            'count.min' => 'Количество не может быть отрицательным.',
            
            'slug.required' => 'URL-алиас (slug) обязателен для заполнения.',
            'slug.unique' => 'Товар с таким URL-алиасом уже существует.',
            'slug.max' => 'URL-алиас не должен превышать 255 символов.',
            
            'description.max' => 'Описание не должно превышать 1000 символов.',
            
            'characteristics.json' => 'Характеристики должны быть в формате JSON.',
        ];
    }
}
