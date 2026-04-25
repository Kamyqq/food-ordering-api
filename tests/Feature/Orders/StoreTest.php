<?php

use App\Models\Dish;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->dish1 = Dish::factory()->create([
        'price' => 2000,
        'is_available' => true,
    ]);

    $this->dish2 = Dish::factory()->create([
        'price' => 3000,
        'is_available' => true,
    ]);

    $this->validPayload = [
        'client_mail' => 'customer@mail.com',
        'client_phone' => '+48 123 456 789',
        'client_address' => 'Random place 13',
        'items' => [
            ['dish_id' => $this->dish1->id, 'quantity' => 2],
            ['dish_id' => $this->dish2->id, 'quantity' => 1],
        ]
    ];
});

it('creates an order and returns payment url on success', function () {
    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn('https://checkout.stripe.com/test-url-123');
    });

    $response = postJson(route('orders.store'), $this->validPayload)
        ->assertStatus(201)
        ->assertJsonStructure([
            'order_id',
            'payment_url',
        ])
        ->assertJsonPath('payment_url', 'https://checkout.stripe.com/test-url-123');

    $orderId = $response->json('order_id');

    assertDatabaseHas('orders', [
        'id' => $orderId,
        'total_price' => 7000,
    ]);
});

it('rolls back database transaction if payment service fails', function () {
    $this->mock(PaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andThrow(new \Stripe\Exception\ApiConnectionException('Stripe API is down.'));
    });

    postJson(route('orders.store'), $this->validPayload)
        ->assertStatus(502)
        ->assertJsonPath('error', 'Payment gateway error. Please try again later.');

    assertDatabaseCount('orders', 0);
});

it('returns 400 if user tries to order an unavailable dish', function () {
    $unavailableDish = Dish::factory()->create([
        'name' => 'test dish',
        'price' => 2500,
        'is_available' => false,
    ]);

    $invalidPayload = array_merge($this->validPayload, [
        'items' => [
            ['dish_id' => $unavailableDish->id, 'quantity' => 1],
        ]
    ]);

    postJson(route('orders.store'), $invalidPayload)
        ->assertStatus(400)
        ->assertJsonPath('error', "Dish 'test dish' is not available.");

    assertDatabaseCount('orders', 0);
});

it('rejects order creation if data is invalid', function ($field, mixed $invalidValue) {
    $invalidPayload = $this->validPayload;
    data_set($invalidPayload, $field, $invalidValue);

    postJson(route('orders.store'), $invalidPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);

    assertDatabaseCount('orders', 0);
})->with([
    'empty email' => ['client_mail', ''],
    'invalid email format' => ['client_mail', 'not-an-email'],
    'int as and email' => ['client_mail', 2],
    'array as and email' => ['client_mail', []],

    'empty phone' => ['client_phone', ''],
    'array as phone' => ['client_phone', []],
    'invalid phone letters' => ['client_phone', 'qwerty'],

    'empty address' => ['client_address', ''],
    'array as address' => ['client_address', []],
    'int as address' => ['client_address', 2],

    'missing items completely' => ['items', null],
    'empty items array' => ['items', []],

    'dish that does not exist in DB' => ['items.0.dish_id', 99999],
    'negative quantity' => ['items.0.quantity', -1],
    'zero quantity' => ['items.0.quantity', 0],
    'string as quantity' => ['items.0.quantity', 'abc'],
    'array as quantity' => ['items.0.quantity', []],
]);

