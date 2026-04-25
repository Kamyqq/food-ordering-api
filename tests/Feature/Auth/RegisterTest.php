<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{assertDatabaseHas, postJson, assertDatabaseCount};

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->validData = [
        'name' => 'Worker',
        'email' => 'worker@mail.com',
        'role' => UserRole::CHEF,
        'password' => 'testPassword',
        'password_confirmation' => 'testPassword',
    ];
});

it('registers user if given data is valid', function () {
    actingAsAdmin()->postJson(route('backend.register'), $this->validData)
        ->assertStatus(201);

    assertDatabaseHas('users', [
        'email' => $this->validData['email'],
        'name' => $this->validData['name'],
    ]);
});

it('rejects account creation if given data is invalid', function ($field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue
    ]);

    $expectedErrorKey = $field === 'password_confirmation' ? 'password' : $field;

    actingAsAdmin()->postJson(route('backend.register'), $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$expectedErrorKey]);


    assertDatabaseCount('users', 1);
})->with(['name', 'email', 'role', 'password', 'password_confirmation'])
    ->with('invalid text types');

it('rejects specific validation business rules', function (string $field, mixed $invalidValue) {
    $invalidData = array_merge($this->validData, [
        $field => $invalidValue
    ]);

    actingAsAdmin()->postJson(route('backend.register'), $invalidData)
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field]);
})->with([
    'name is too long' => ['name', str_repeat('a', 256)],
    'email has bad format' => ['email', 'this-is-not-email'],
    'role does not exist' => ['role', 'fake_role'],
]);

it('prevents account creation if given email is already taken', function () {
    User::factory()->create([
        'email' => 'taken@mail.com'
    ]);

    $takenEmail = [
        'name' => 'Worker',
        'email' => 'taken@mail.com',
        'role' => UserRole::CHEF,
        'password' => 'testPassword',
        'password_confirmation' => 'testPassword',
    ];

    actingAsAdmin()->postJson(route('backend.register'), $takenEmail)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    assertDatabaseCount('users', 2);
});

it('prevents non-admins from creating accounts', function () {
    actingAsChef()->postJson(route('backend.register'), $this->validData)
        ->assertStatus(403);

    assertDatabaseCount('users', 1);
});

it('prevents guests from creating account', function () {
    postJson(route('backend.register'), $this->validData)
        ->assertStatus(401);

    assertDatabaseCount('users', 0);
});
