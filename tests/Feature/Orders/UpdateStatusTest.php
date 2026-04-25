<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->order = Order::factory()->create([
        'status' => OrderStatus::PREPARING,
    ]);
});

it('allows admin to update status to any valid status', function () {
    actingAsAdmin()->patchJson(route('orders.status.update', $this->order->id), ['status' => OrderStatus::READY,])
        ->assertStatus(200);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::READY,
    ]);
});

it('allows chef to change status ONLY from preparing to ready', function () {
    actingAsChef()->patchJson(route('orders.status.update', $this->order->id), ['status' => OrderStatus::READY,])
        ->assertStatus(200);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::READY,
    ]);
});

it('prevents chef from changing status to delivering', function () {
    actingAsChef()->patchJson(route('orders.status.update', $this->order->id), ['status' => OrderStatus::DELIVERING,])
        ->assertStatus(403);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::PREPARING,
    ]);
});

it('allows delivery driver to progress the order correctly', function () {
    $readyOrder = Order::factory()->create([
        'status' => OrderStatus::READY,
    ]);

    actingAsDelivery()->patchJson(route('orders.status.update', $readyOrder->id), ['status' => OrderStatus::DELIVERING,])
        ->assertStatus(200);

    actingAsDelivery()->patchJson(route('orders.status.update', $readyOrder->id), ['status' => OrderStatus::DELIVERED,])
        ->assertStatus(200);

    assertDatabaseHas('orders', [
        'id' => $readyOrder->id,
        'status' => OrderStatus::DELIVERED,
    ]);
});

it('prevents guests from modifying order statuses', function () {
    patchJson(route('orders.status.update', $this->order->id), [
        'status' => OrderStatus::READY,
    ])->assertStatus(401);
});

it('returns 422 for invalid if unprocessable status', function ($invalidStatus) {
    actingAsAdmin()->patchJson(route('orders.status.update', $this->order->id), ['status' => $invalidStatus,])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    assertDatabaseHas('orders', [
        'id' => $this->order->id,
        'status' => OrderStatus::PREPARING,
    ]);
})->with([
    'empty status' => '',
    'null status' => null,
    'unrecognized string' => 'test_string',
    'polish word instead of english' => 'gotowe',
    'number instead of string' => 999,
    'array instead of string' => [[]],
    'boolean status' => true,
]);
