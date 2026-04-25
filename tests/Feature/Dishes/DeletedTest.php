<?php

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\getJson;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->activeDish = Dish::factory()->create([]);

    $this->deletedDish = Dish::factory()->create([]);
    $this->deletedDish->delete();
});

it('shows ONLY soft deleted dishes if user has permissions', function () {
    actingAsAdmin()->getJson(route('dishes.deleted'))
        ->assertStatus(200)
        ->assertJsonFragment([
            'id' => $this->deletedDish->id,
        ])
        ->assertJsonMissing([
            'id' => $this->activeDish->id,
        ])
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'is_available',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
});

it('rejects showing soft deleted dishes to non-admin users', function () {
    actingAsChef()->getJson(route('dishes.deleted'))
        ->assertStatus(403);
});

it('rejects showing soft deleted dishes to guest users', function () {
    getJson(route('dishes.deleted'))
        ->assertStatus(401);
});
