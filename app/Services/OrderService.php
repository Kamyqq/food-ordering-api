<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function processOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $orderedDishes = $data['items'];
            $totalPrice = 0;
            $itemsToInsert = [];

            $dishIds = array_unique(array_column($orderedDishes, 'dish_id'));
            $quantities = collect($orderedDishes)
                ->keyBy('dish_id')
                ->map(fn ($item) => $item['quantity']);

            $foundDishes = Dish::whereIn('id', $dishIds)->get();

            if ($foundDishes->count() !== count($dishIds)) {
                throw new \DomainException("One or more selected dishes are invalid or do not exist.");
            }

            foreach ($foundDishes as $dish) {
                if (!$dish->is_available) {
                    throw new \DomainException("Dish '{$dish->name}' is not available.");
                }

                $quantity = $quantities[$dish->id];

                $totalPrice += $dish->price * $quantity;
                $itemsToInsert[] = [
                    'dish_id' => $dish->id,
                    'quantity' => $quantity,
                    'price_at_purchase' => $dish->price,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            $order = Order::create([
                'client_mail' => $data['client_mail'],
                'client_phone' => $data['client_phone'],
                'client_address' => $data['client_address'],
                'total_price' => $totalPrice,
                'status' => OrderStatus::PENDING,
            ]);

            foreach ($itemsToInsert as &$item) {
                $item['order_id'] = $order->id;
            }

            OrderItem::insert($itemsToInsert);
            return $order;
        });
    }

    public function getOrdersForUser(User $user)
    {
        $query = Order::with('orderItems.dish')->oldest();

        if ($user->role === UserRole::ADMIN) {
            return $query->paginate(15);
        }

        if ($user->role === UserRole::CHEF) {
            return $query->whereIn('status', [OrderStatus::PREPARING, OrderStatus::READY])
                ->where('orders.created_at', '>=', now()->subDay())
                ->get();
        }

        if ($user->role === UserRole::DELIVERY) {
            return $query->whereIn('status', [OrderStatus::READY, OrderStatus::DELIVERING, OrderStatus::DELIVERED])
                ->where('orders.created_at', '>=', now()->subDay())
                ->get();
        }

        return collect();
    }
}
