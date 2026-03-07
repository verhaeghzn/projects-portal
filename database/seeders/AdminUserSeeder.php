<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed an admin user for local/development. Safe to run multiple times (uses firstOrCreate).
     */
    public function run(): void
    {
        $email = config('app.admin_seed_email', 'admin@example.com');
        $password = config('app.admin_seed_password', 'password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('Administrator');

        $this->command->info("Admin user ready: {$email} / {$password}");
    }
}
