<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseCount};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->validData = [
        'name' => 'Dish',
        'description' => 'Dish description',
        'price' => '1000',
        'is_available' => true,
    ];
});

it('stores dish if data is valid', function () {
    actingAsAdmin()->postJson(route('dishes.store'), $this->validData)
        ->assertStatus(201)
        ->assertJsonPath('message', 'Dish created successfully.');

    assertDatabaseCount('dishes', 1);
});

it('prevents non-admin user from creating a dish', function () {
    actingAsChef()->postJson(route('dishes.store'), $this->validData)
        ->assertStatus(403);

    assertDatabaseCount('dishes', 0);
});

it('prevents guest from creating a dish', function () {
    postJson(route('dishes.store'), $this->validData)
        ->assertStatus(401);

    assertDatabaseCount('dishes', 0);
});

it('rejects request if data is unprocessable', function ($field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue,
    ]);

    actingAsAdmin()->postJson(route('dishes.store'), $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);

    assertDatabaseCount('dishes', 0);
})->with(['name', 'description'])
->with('invalid text types');

it('rejects request if data violates specific business rules', function (string $field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue,
    ]);

    actingAsAdmin()->postJson(route('dishes.store'), $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);

    assertDatabaseCount('dishes', 0);
})->with([
    'too long name' => ['name', str_repeat('a', 256)],
    'too long description' => ['description', str_repeat('a', 2001)],

    'empty price' => ['price', ''],
    'boolean as a price' => ['price', true],
    'negative number as a price' => ['price', -1],
    'array as a price' => ['price', []],

    'empty is_available' => ['is_available', ''],
    'int as is_available' => ['is_available', 2],
    'string as is_available' => ['is_available', 'test'],
    'array as is_available' => ['is_available', []],
]);
