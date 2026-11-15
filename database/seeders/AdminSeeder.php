<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    // Membuat (atau memperbarui) satu akun admin dari ADMIN_EMAIL dan ADMIN_PASSWORD di .env.
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL')));
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            $this->command?->warn('ADMIN_EMAIL atau ADMIN_PASSWORD belum diisi di .env, akun admin dilewati.');

            return;
        }

        $admin = User::firstOrNew(['email' => $email]);

        // forceFill karena role dan is_verified sengaja tidak masuk $fillable.
        $admin->forceFill([
            'name' => 'Admin',
            'role' => 'admin',
            'password' => $password,
            'is_verified' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}