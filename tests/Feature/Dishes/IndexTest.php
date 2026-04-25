<?php

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{getJson};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Dish::factory()->count(3)->create();
});

it('shows all dishes', function () {
    $deletedDish  = Dish::factory()->create();
    $deletedDish->delete();

    getJson(route('dishes.index'))
        ->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonMissing([
            'id' => $deletedDish->id,
        ])
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'is_available',
                ]
            ]
        ]);
});
