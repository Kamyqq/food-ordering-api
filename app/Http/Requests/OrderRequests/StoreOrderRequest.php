<?php

namespace App\Http\Requests\OrderRequests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_mail' => ['required', 'string', 'email'],
            'client_phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'client_address' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dish_id' => ['required', 'integer', 'exists:dishes,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
