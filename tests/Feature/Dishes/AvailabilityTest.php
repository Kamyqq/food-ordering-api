<?php

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{assertDatabaseHas, assertDatabaseMissing, postJson};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->dish = Dish::factory()->create([
        'is_available' => true,
    ]);
});

it('changes availability if user has permissions', function () {
    actingAsChef()->postJson(route('dishes.availability', $this->dish->id))
        ->assertStatus(200);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'is_available' => false,
    ]);

    actingAsChef()->postJson(route('dishes.availability', $this->dish->id))
        ->assertStatus(200);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'is_available' => true,
    ]);
});

it('prevents user without permissions from changing the availability', function () {
    actingAsDelivery()->postJson(route('dishes.availability', $this->dish->id))
        ->assertStatus(403);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'is_available' => true,
    ]);
});

it('prevents guest from changing the availability', function () {
    postJson(route('dishes.availability', $this->dish->id))
        ->assertStatus(401);

    assertDatabaseHas('dishes', [
        'id' => $this->dish->id,
        'is_available' => true,
    ]);
});

it('prevents from changing availability of non-existing dish', function () {
    $nonExistingDish = 99999;

    actingAsAdmin()->postJson(route('dishes.availability', $nonExistingDish))
        ->assertStatus(404);

    assertDatabaseMissing('dishes', [
        'id' => $nonExistingDish
    ]);
});
