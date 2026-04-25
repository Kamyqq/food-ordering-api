<?php

namespace App\Http\Requests\DishRequests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:3', 'max:2000'],
            'price' => ['required', 'numeric', 'integer', 'min:1'],
            'is_available' => ['required', 'boolean'],
        ];
    }
}
