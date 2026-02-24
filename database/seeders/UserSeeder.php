<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@edwh.local'],
            [
                'name' => 'Analytics Admin',
                'email' => 'admin@edwh.local',
                'password' => Hash::make('Admin@1234'),
                'role' => 'admin',
            ]
        );

        // Viewer user
        User::updateOrCreate(
            ['email' => 'viewer@edwh.local'],
            [
                'name' => 'Dashboard Viewer',
                'email' => 'viewer@edwh.local',
                'password' => Hash::make('Viewer@1234'),
                'role' => 'viewer',
            ]
        );

        $this->command->info('Users seeded: admin@edwh.local (Admin@1234) | viewer@edwh.local (Viewer@1234)');
    }
}
