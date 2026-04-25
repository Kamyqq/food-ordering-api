<?php

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{assertSoftDeleted, assertNotSoftDeleted, deleteJson, assertDatabaseMissing};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->dish = Dish::factory()->create();
});

it('deletes dish if user has permissions to do so.', function () {
    actingAsAdmin()->deleteJson(route('dishes.destroy', $this->dish->id))
        ->assertStatus(200);

    assertSoftDeleted($this->dish);
});

it('prevents user without permissions from deleting the dish', function () {
    actingAsChef()->deleteJson(route('dishes.destroy', $this->dish->id))
        ->assertStatus(403);

    assertNotSoftDeleted('dishes', [
        'id' => $this->dish->id,
    ]);
});

it('prevents guest from deleting the dish', function () {
    deleteJson(route('dishes.destroy', $this->dish->id))
        ->assertStatus(401);

    assertNotSoftDeleted('dishes', [
        'id' => $this->dish->id,
    ]);
});

it('prevents deleting non-existing dish', function () {
    $nonExistingDish = 99999;

    actingAsAdmin()->deleteJson(route('dishes.destroy', $nonExistingDish))
        ->assertStatus(404);

    assertDatabaseMissing('dishes', [
        'id' => $nonExistingDish
    ]);
});
