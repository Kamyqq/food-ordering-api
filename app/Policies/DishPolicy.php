<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Dish;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DishPolicy
{
    public function toggleAvailability(User $user, Dish $dish): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::CHEF]);
    }
}
