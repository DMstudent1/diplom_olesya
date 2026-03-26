<?php

namespace App\Http\Requests;

use App\Rules\PhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone' => ['required', 'string', new PhoneRule(table: 'users')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно для заполнения',
            'name.string' => 'Имя должно быть строкой',
            'name.max' => 'Имя не должно превышать 255 символов',

            'email.required' => 'Email обязателен для заполнения',
            'email.email' => 'Введите корректный email адрес',
            'email.unique' => 'Пользователь с таким email уже существует',

            'password.required' => 'Пароль обязателен для заполнения',
            'password.min' => 'Пароль должен содержать минимум 8 символов',
            'password.confirmed' => 'Подтверждение пароля не совпадает',

            'phone.required' => 'Номер телефона обязателен',
            'phone.string' => 'Номер телефона должен быть строкой',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);

            if (strlen($cleanPhone) === 11) {
                if (substr($cleanPhone, 0, 1) === '8') {
                    $cleanPhone = '+7' . substr($cleanPhone, 1);
                } elseif (substr($cleanPhone, 0, 1) === '7') {
                    $cleanPhone = '+' . $cleanPhone;
                }
            } elseif (strlen($cleanPhone) === 10) {
                $cleanPhone = '+7' . $cleanPhone;
            }

            $this->merge([
                'phone' => $cleanPhone
            ]);
        }
    }
}
