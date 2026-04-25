<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);

        $token = $admin->createToken('admin-token')->plainTextToken;
        $this->command->info('Admin token: ' . $token);

        $admin = User::factory()->create([
            'name' => 'Chef',
            'email' => 'chef@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::CHEF,
        ]);

        $token = $admin->createToken('chef-token')->plainTextToken;
        $this->command->info('Chef token: ' . $token);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'delivery@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::DELIVERY,
        ]);

        $token = $admin->createToken('delivery-token')->plainTextToken;
        $this->command->info('Delivery token: ' . $token);
    }
}
