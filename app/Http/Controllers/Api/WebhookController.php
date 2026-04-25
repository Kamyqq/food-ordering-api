<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException|SignatureVerificationException $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->client_reference_id;

            $order = Order::find($orderId);
            if ($order) {
                $order->status = OrderStatus::PREPARING;
                $order->stripe_payment_id = $session->payment_intent;
                $order->save();

                Log::info('Payment received for order: ' . $order->id);

                OrderPaid::dispatch($order);
            }
        }

        return response()->json([
            'status' => 'success'
        ], 200);
    }
}
