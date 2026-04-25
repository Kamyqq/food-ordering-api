<?php

use App\Models\User;
use App\Enums\UserRole;

function actingAsAdmin()
{
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    return test()->actingAs($admin);
}

function actingAsChef()
{
    $chef = User::factory()->create([
        'role' => UserRole::CHEF,
    ]);

    return test()->actingAs($chef);
}

function actingAsDelivery()
{
    $delivery = User::factory()->create([
        'role' => UserRole::DELIVERY,
    ]);

    return test()->actingAs($delivery);
}
