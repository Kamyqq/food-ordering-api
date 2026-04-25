<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;

class PaymentService
{
    public function createCheckoutSession(Order $order)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card', 'blik'],
            'mode' => 'payment',
            'client_reference_id' => $order->id ,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'pln',
                        'unit_amount' => $order->total_price,
                        'product_data' => [
                            'name' => 'Pizza place order #' . $order->id,
                            'description' => 'Payment for delicious food'
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => config('app.frontend_url') . '/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.frontend_url') . '/cancel',
        ]);

        return $session->url;
    }

    public function refundPayment(string $paymentIntentId): void
    {
        $stripe = new StripeClient(config('services.stripe.secret'));
        $stripe->refunds->create(['payment_intent' => $paymentIntentId]);
    }
}
