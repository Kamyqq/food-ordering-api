<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function updateStatus(User $user, Order $order, ?OrderStatus $newStatus): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::CHEF) {
            return $order->status === OrderStatus::PREPARING && $newStatus === OrderStatus::READY;
        }

        if ($user->role === UserRole::DELIVERY) {
            if ($order->status === OrderStatus::READY && $newStatus === OrderStatus::DELIVERING) {
                return true;
            }

            if ($order->status === OrderStatus::DELIVERING && $newStatus === OrderStatus::DELIVERED) {
                return true;
            }
        }

        return false;
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::CHEF && in_array($order->status, [OrderStatus::PREPARING, OrderStatus::READY])) {
            return true;
        }

        return false;
    }
}
