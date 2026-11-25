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

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => env('ADMIN_NAME', 'Administrator SUPD I'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        // Akun yang baru dibuat wajib mengganti kata sandi bawaan saat
        // login pertama. Akun yang sudah ada tidak ikut dipaksa.
        if (! $user->exists) {
            $user->must_change_password = true;
        }

        $user->save();
    }
}
