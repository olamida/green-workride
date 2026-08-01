<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => config('workride.admin.email', 'admin@workride.ng')],
            [
                'name' => 'WorkRide Admin',
                'password' => config('workride.admin.password', 'admin1234'),
                'role' => UserRole::Admin,
                'verification_level' => VerificationLevel::DriverVerified,
                'is_banned' => false,
            ],
        );

        Wallet::firstOrCreate(['user_id' => $admin->id]);

        $this->command?->info('Admin user ready: '.$admin->email.' / admin1234 (change after first login).');
    }
}
