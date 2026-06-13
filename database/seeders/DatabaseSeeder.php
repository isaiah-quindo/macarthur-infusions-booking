<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AvailabilityRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'macarthurinfusions@outlook.com.au'],
            [
                'name' => 'Macarthur Infusions',
                'password' => Hash::make(env('ADMIN_INITIAL_PASSWORD', 'change-me-on-first-login')),
                'role' => UserRole::Admin,
            ],
        );

        // Default clinic hours: Mon–Fri 9:00–17:00. Editable in /admin/availability.
        if (AvailabilityRule::count() === 0) {
            foreach ([1, 2, 3, 4, 5] as $day) {
                AvailabilityRule::create([
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ]);
            }
        }

        $this->call(ServiceSeeder::class);
    }
}
