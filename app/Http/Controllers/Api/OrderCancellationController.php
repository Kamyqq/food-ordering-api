<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class OrderCancellationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function __invoke(Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        if ($order->status === OrderStatus::CANCELLED) {
            return response()->json(['message' => 'This order is already cancelled.'], 400);
        }

        try {
            if ($order->stripe_payment_id) {
                $this->paymentService->refundPayment($order->stripe_payment_id);
            }

            $order->update(['status' => OrderStatus::CANCELLED]);
            OrderCancelled::dispatch($order);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Stripe payment refund error:' . $e->getMessage(),
            ], 500);
        }
    }
}
