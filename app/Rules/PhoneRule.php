<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;
use Log;

class PhoneRule implements ValidationRule
{
    public function __construct(private ?string $table = 'users', private ?int $id = null)
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || !preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{10,11}$/', $value)) {
            $fail('Введите корректный номер телефона');
            return;
        }
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $value);
        
        if (substr($cleanPhone, 0, 1) === '8') {
            $cleanPhone = '7' . substr($cleanPhone, 1);
        }
        if ($this->table && $value) {
            $exists = DB::table($this->table)
                ->where('phone', $cleanPhone)
                ->when($this->id, fn($q) => $q->where('id', '!=', $this->id))
                ->exists();
            if ($exists) {
                $fail('Данный телефон уже используется');
            }
        }
    }
}