<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'worker@mail.com',
        'password' => 'testPassword',
    ]);

    $this->userToken = $this->user->createToken('user-token')->plainTextToken;
});

it('logs out user successfully', function () {
    $this->withToken($this->userToken)->postJson(route('backend.logout'))
        ->assertStatus(200);

    assertDatabaseMissing('personal_access_tokens', [
        'name' => 'user-token',
    ]);
});

it('rejects logging out guest', function () {
    postJson(route('backend.logout'))
        ->assertStatus(401);

    assertDatabaseHas('personal_access_tokens', [
        'name' => 'user-token',
    ]);
});

