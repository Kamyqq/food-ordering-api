<?php

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\TestCase;
use function Pest\Laravel\{assertDatabaseHas, postJson};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->order = Order::factory()->create([
        'status' => OrderStatus::PREPARING,
        'stripe_payment_id' => 'pi_123456789',
    ]);
});

it('allows admin to cancel an order regardless of status', function () {
    $this->mock(PaymentService::class, fn (MockInterface $mock) => $mock->shouldReceive('refundPayment')->once());

    actingAsAdmin()->postJson(route('orders.cancel', $this->order))
        ->assertStatus(200);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::CANCELLED
    ]);
});

it('allows chef to cancel order ONLY if it is preparing or ready', function (OrderStatus $status) {
    $order = Order::factory()->create([
        'status' => $status
    ]);

    $this->mock(PaymentService::class, fn (MockInterface $mock) => $mock->shouldIgnoreMissing());

    actingAsChef()->postJson(route('orders.cancel', $order))
        ->assertStatus(200);
})->with([
    OrderStatus::PREPARING,
    OrderStatus::READY,
]);

it('prevents chef from cancelling order if it is in other status', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::PENDING
    ]);

    actingAsChef()->postJson(route('orders.cancel', $order))
        ->assertStatus(403);

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::PENDING
    ]);
});


it('dispatches OrderCancelled event upon successful cancellation', function () {
    Event::fake();
    $this->mock(PaymentService::class, fn (MockInterface $mock) => $mock->shouldIgnoreMissing());

    actingAsAdmin()->postJson(route('orders.cancel', $this->order))
        ->assertStatus(200);

    Event::assertDispatched(OrderCancelled::class, function ($event) {
        return $event->order->id === $this->order->id;
    });
});

it('returns 400 error if order is already cancelled', function () {
    $this->order->update(['status' => OrderStatus::CANCELLED]);

    actingAsAdmin()->postJson(route('orders.cancel', $this->order))
        ->assertStatus(400)
        ->assertJsonPath('message', 'This order is already cancelled.');
});

it('handles stripe refund failure gracefully', function () {
    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('refundPayment')
            ->once()
            ->andThrow(new \Exception('Card declined.'));
    });

    actingAsAdmin()->postJson(route('orders.cancel', $this->order))
        ->assertStatus(500)
        ->assertJsonPath('error', 'Stripe payment refund error:Card declined.');

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::PREPARING,
    ]);
});

it('prevents guest from cancelling order', function () {
    postJson(route('orders.cancel', $this->order))
        ->assertStatus(401);
});
