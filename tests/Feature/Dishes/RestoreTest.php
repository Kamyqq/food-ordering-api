<?php

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{assertNotSoftDeleted, assertSoftDeleted, postJson};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->dish = Dish::factory()->create([]);
    $this->dish->delete();
});

it('allows admin to restore soft deleted dish', function () {
    actingAsAdmin()->postJson(route('dishes.restore', $this->dish->id))
        ->assertStatus(200);

    assertNotSoftDeleted('dishes', [
        'id' => $this->dish->id,
    ]);
});

it('prevents non-admin user from restoring soft deleted dish', function () {
    actingAsChef()->postJson(route('dishes.restore', $this->dish->id))
        ->assertStatus(403);

    assertSoftDeleted('dishes', [
        'id' => $this->dish->id,
    ]);
});

it('prevents guest from restoring soft deleted dish', function () {
    postJson(route('dishes.restore', $this->dish->id))
        ->assertStatus(401);

    assertSoftDeleted('dishes', [
        'id' => $this->dish->id,
    ]);
});

it('does not allow to restore non-existing dish', function () {
    $nonExistingDish = 999999;

    actingAsAdmin()->postJson(route('dishes.restore', $nonExistingDish))
        ->assertStatus(404);
});
