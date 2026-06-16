<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed an admin user for local/development. Safe to run multiple times (uses firstOrCreate).
     */
    public function run(): void
    {
        $email = config('app.admin_seed_email', 'admin@example.com');
        $password = config('app.admin_seed_password', 'password');
        $name = 'Admin';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'slug' => $this->nextAvailableSlug($name),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (blank($user->slug)) {
            $user->slug = $this->nextAvailableSlug($user->name ?: $name, $user->id);
            $user->save();
        }

        $user->assignRole('Administrator');

        $this->command->info("Admin user ready: {$email} / {$password}");
    }

    private function nextAvailableSlug(string $name, ?int $exceptUserId = null): string
    {
        $base = Str::slug($name) ?: 'admin';
        $slug = $base;
        $counter = 1;

        while ($this->slugTaken($slug, $exceptUserId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?int $exceptUserId): bool
    {
        $query = User::query()->where('slug', $slug);

        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->exists();
    }
}
