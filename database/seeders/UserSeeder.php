<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Membuat akun administrator awal dari variabel lingkungan.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@spud1bangda.com');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator SUPD I'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
