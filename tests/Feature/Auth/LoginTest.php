<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    User::factory()->create([
        'email' => 'worker@mail.com',
        'password' => 'testPassword',
    ]);

    $this->validData = [
        'email' => 'worker@mail.com',
        'password' => 'testPassword',
    ];
});

it('logs in the user if data is valid', function () {
    $response = postJson(route('backend.login'), $this->validData)
        ->assertStatus(200)
        ->assertJsonStructure([
            'token'
        ]);

    $token = $response->json()['token'];
    expect($token)->not()->toBeEmpty()
        ->toBeString();
});

it('returns unauthorized if password is invalid', function () {
    $invalidPassword = [
        'email' => 'worker@mail.com',
        'password' => 'invalidPassword',
    ];

    postJson(route('backend.login'), $invalidPassword)
        ->assertStatus(401);
});

it('returns unauthorized if user does not exists', function () {
    $nonExistingUser = [
        'email' => 'invalid@mail.com',
        'password' => 'invalidPassword',
    ];

    postJson(route('backend.login'), $nonExistingUser)
        ->assertStatus(401);
});

it('rejects request if data is unprocessable', function ($field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue
    ]);

    postJson('/api/backend/login', $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);
})->with(['email', 'password'])
    ->with('invalid text types');

it('throttles login attempts after 5 requests', function () {
    $invalidPassword = [
        'email' => 'worker@mail.com',
        'password' => 'invalidPassword',
    ];

    for ($i = 0; $i < 5; $i++) {
        postJson(route('backend.login'), $invalidPassword)
            ->assertStatus(401);
    }

    postJson(route('backend.login'), $invalidPassword)
        ->assertStatus(429);
});
