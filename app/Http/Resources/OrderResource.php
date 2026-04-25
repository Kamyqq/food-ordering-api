<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use function Laravel\Prompts\number;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userRole = $request->user()?->role;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_price' => number_format($this->total_price / 100, 2, '.', ''),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'client_address' => $this->when(
                in_array($userRole, [UserRole::ADMIN, UserRole::DELIVERY]),
                $this->client_address
            ),
            'client_phone' => $this->when(
                in_array($userRole, [UserRole::ADMIN, UserRole::DELIVERY]),
                $this->client_phone
            ),
            'client_email' => $this->when(
                $userRole === UserRole::ADMIN,
                $this->client_mail
            ),
            'created_at' => $this->when(
                $userRole === UserRole::ADMIN,
                $this->created_at?->translatedFormat('d F Y, H:i')
            ),
            'updated_at' => $this->when(
                $userRole === UserRole::ADMIN,
                $this->updated_at?->translatedFormat('d F Y, H:i')
            ),
        ];
    }
}
