<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use function Pest\Laravel\getJson;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->oldOrder = Order::factory()->create([
        'status' => OrderStatus::READY,
        'created_at' => now()->subDays(2),
    ]);

    $this->pendingOrder = Order::factory()->create([
        'status' => OrderStatus::PENDING,
    ]);

    $this->preparingOrder = Order::factory()->create([
        'status' => OrderStatus::PREPARING,
    ]);

    $this->readyOrder = Order::factory()->create([
        'status' => OrderStatus::READY,
    ]);
});

it('allows admin to view all orders paginated', function () {
    actingAsAdmin()->getJson(route('orders.ordered'))
        ->assertStatus(200)
        ->assertJsonCount(4, 'data')
        ->assertJsonStructure([
            'data', 'links', 'meta'
        ]);
});

it('allows chef to view only preparing and ready orders from the last 24 hours', function () {
    $response = actingAsChef()->getJson(route('orders.ordered'))
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');

    $visibleOrderIds = collect($response->json('data'))->pluck('id')->toArray();

    expect($visibleOrderIds)
        ->toContain($this->preparingOrder->id)
        ->toContain($this->readyOrder->id)
        ->not()->toContain($this->pendingOrder->id)
        ->not()->toContain($this->oldOrder->id);
});

it('allows delivery driver to view only ready and delivering orders from the last 24 hours', function () {
    $response = actingAsDelivery()->getJson(route('orders.ordered'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');

    $visibleOrderIds = collect($response->json('data'))->pluck('id')->toArray();

    expect($visibleOrderIds)
        ->toContain($this->readyOrder->id)
        ->not->toContain($this->preparingOrder->id)
        ->not->toContain($this->oldOrder->id);
});

it('prevents guests from accessing the orders list', function () {
    getJson(route('orders.ordered'))
        ->assertStatus(401);
});

it('allows admin to view all orders paginated and full resource structure', function () {
    actingAsAdmin()->getJson(route('orders.ordered'))
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('data')
        ->has('data.0', fn (AssertableJson $json) =>
        $json->hasAll([
            'id', 'status', 'total_price', 'items',
            'client_address', 'client_phone', 'client_email',
            'created_at', 'updated_at'
        ])
        )
            ->etc()
        );
});

it('allows delivery driver to view correct orders and driver-specific structure', function () {
    actingAsDelivery()->getJson(route('orders.ordered'))
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('data.0', fn (AssertableJson $json) =>
        $json->hasAll(['id', 'status', 'total_price', 'items', 'client_address', 'client_phone'])
            ->missingAll(['client_email', 'created_at', 'updated_at'])
        )
        );
});

it('allows chef to view correct orders and chef-specific structure', function () {
    actingAsChef()->getJson(route('orders.ordered'))
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('data.0', fn (AssertableJson $json) =>
        $json->hasAll(['id', 'status', 'total_price', 'items'])
            ->missingAll([
                'client_address', 'client_phone', 'client_email',
                'created_at', 'updated_at'
            ])
        )
        );
});
