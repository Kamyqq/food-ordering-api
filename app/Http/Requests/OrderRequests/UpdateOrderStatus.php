<?php

namespace App\Http\Requests\OrderRequests;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatus extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');
        $statusInput = $this->input('status');

        $newStatusEnum = is_string($statusInput) ? OrderStatus::tryFrom($statusInput) : null;

        return $this->user()->can('updateStatus', [$order, $newStatusEnum]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:preparing,ready,delivering,delivered']
        ];
    }
}
