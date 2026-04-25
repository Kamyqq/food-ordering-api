<?php

use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->secret = 'whsec_test_secret';
    config(['services.stripe.webhook_secret' => $this->secret]);

    $this->order = Order::factory()->create([
        'status' => OrderStatus::PENDING,
        'stripe_payment_id' => null,
    ]);
});

function sendWebhookRequest($testCase, array $payloadData, string $secret)
{
    $payloadString = json_encode($payloadData);
    $timestamp = time();

    $signature = hash_hmac('sha256', "{$timestamp}.{$payloadString}", $secret);
    $stripeSignatureHeader = "t={$timestamp},v1={$signature}";

    return $testCase->call(
        'POST',
        route('webhook.stripe'),
        [], [], [],
        [
            'HTTP_STRIPE_SIGNATURE' => $stripeSignatureHeader,
            'CONTENT_TYPE' => 'application/json'
        ],
        $payloadString
    );
}

it('processes checkout.session.completed and updates the order', function () {
    Event::fake();

    $payloadData = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'client_reference_id' => $this->order->id,
                'payment_intent' => 'pi_123456789_test',
            ],
        ],
    ];

    sendWebhookRequest($this, $payloadData, $this->secret)
        ->assertStatus(200);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::PREPARING,
        'stripe_payment_id' => 'pi_123456789_test',
    ]);

    Event::assertDispatched(OrderPaid::class, function ($event) {
        return $event->order->id === $this->order->id;
    });
});

it('rejects webhook if stripe signature is invalid', function () {
    $payloadString = json_encode(['type' => 'checkout.session.completed']);

    $this->call(
        'POST', route('webhook.stripe'), [], [], [],
        ['HTTP_STRIPE_SIGNATURE' => 'zly_podpis_hakera', 'CONTENT_TYPE' => 'application/json'],
        $payloadString
    )->assertStatus(400);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::PENDING,
    ]);
});

it('ignores other stripe event types returning 200 but not modifying order', function () {
    $payloadData = [
        'type' => 'payment_intent.payment_failed',
        'data' => ['object' => ['id' => 'pi_error']]
    ];

    sendWebhookRequest($this, $payloadData, $this->secret)
        ->assertStatus(200);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::PENDING,
    ]);
});
