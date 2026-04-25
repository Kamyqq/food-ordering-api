<?php

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{assertDatabaseHas, putJson};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->dish = Dish::factory()->create();

    $this->validData = [
        'name' => 'test',
        'description' => 'test data',
        'price' => 3900,
        'is_available' => true
    ];
});

it('updates dish if everything is valid', function () {
    actingAsAdmin()->putJson(route('dishes.update', $this->dish->id), $this->validData)
        ->assertStatus(200);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'name' => $this->validData['name'],
        'price' => $this->validData['price'],
    ]);
});

it('prevents guest from updating the dish', function () {
    putJson(route('dishes.update', $this->dish->id), $this->validData)
        ->assertStatus(401);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'name' => $this->dish->name,
        'description' => $this->dish->description,
    ]);
});

it('prevents non-admin user from updating the dish', function () {
    actingAsChef()->putJson(route('dishes.update', $this->dish->id), $this->validData)
        ->assertStatus(403);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'name' => $this->dish->name,
        'description' => $this->dish->description,
    ]);
});

it('prevents user from updating non-existing dish', function () {
    $nonExistingDish = 99999;

    actingAsAdmin()->putJson(route('dishes.update', $nonExistingDish), $this->validData)
        ->assertStatus(404);
});

it('rejects request if data is unprocessable', function ($field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue,
    ]);

    actingAsAdmin()->putJson(route('dishes.update', $this->dish->id), $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'name' => $this->dish->name,
        'description' => $this->dish->description,
    ]);
})->with(['name', 'description'])
->with('invalid text types');

it('rejects update if data violates specific business rules', function (string $field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue,
    ]);

    actingAsAdmin()->putJson(route('dishes.update', $this->dish->id), $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'name' => $this->dish->name,
        'description' => $this->dish->description,
    ]);
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
