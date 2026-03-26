<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? null;
        return [
            'name' => 'required|string|max:255|unique:categories,name' . $categoryId,
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value && $value == $categoryId) {
                        $fail('Категория не может быть родительской сама для себя.');
                    }
                },
            ],
            'slug' => 'required|string|max:255|unique:categories,slug' . $categoryId,
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название категории обязательно для заполнения.',
            'name.unique' => 'Категория с таким названием уже существует.',
            'name.max' => 'Название категории не должно превышать 255 символов.',

            'slug.required' => 'URL-алиас (slug) обязателен для заполнения.',
            'slug.unique' => 'Категория с таким URL-алиасом уже существует.',
            'slug.max' => 'URL-алиас не должен превышать 255 символов.',

            'parent_id.exists' => 'Выбранная родительская категория не существует.',
            'parent_id.integer' => 'ID родительской категории должен быть целым числом.',

            'description.max' => 'Описание не должно превышать 1000 символов.',
        ];
    }
}
