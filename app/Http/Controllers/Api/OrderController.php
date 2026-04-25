<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class OrderController extends Controller
{
    public function __construct(
        public OrderService $orderService,
        public PaymentService $paymentService
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data) {
                $order = $this->orderService->processOrder($data);
                $paymentUrl = $this->paymentService->createCheckoutSession($order);

                return [
                    'order_id' => $order->id,
                    'payment_url' => $paymentUrl,
                ];
            });

            return response()->json([
                'message' => 'Order accepted successfully.',
                'order_id' => $result['order_id'],
                'payment_url' => $result['payment_url'],
            ], 201);

        } catch (ApiErrorException $exception) {
            return response()->json([
                'error' => 'Payment gateway error. Please try again later.',
            ], 502);

        } catch (\InvalidArgumentException | \DomainException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 400);

        } catch (\Exception $exception) {
            Log::error('Critical order error: ' . $exception->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.'
            ], 500);
        }
    }

    public function index(Request $request): ResourceCollection
    {
        $user = $request->user();
        $orders = $this->orderService->getOrdersForUser($user);

        return OrderResource::collection($orders);
    }
}
