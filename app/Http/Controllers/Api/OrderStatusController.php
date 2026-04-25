<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequests\UpdateOrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class OrderStatusController extends Controller
{
    use AuthorizesRequests;

    public function editStatus(UpdateOrderStatus $request, Order $order): JsonResponse
    {
        $newStatus = $request->input('status');

        $order->update([
            'status' => $newStatus
        ]);

        return response()->json($order);
    }
}
