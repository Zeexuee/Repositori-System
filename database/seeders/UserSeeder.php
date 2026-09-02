<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@admin.com',
                'name' => 'Admin Direktur',
                'password' => '123456',
                'role' => 'Direksi',
            ],
            [
                'email' => 'staf@sekretariat.corp',
                'name' => 'Staf Sekretariat',
                'password' => 'password',
                'role' => 'Staf',
            ],
            [
                'email' => 'direksi@sekretariat.corp',
                'name' => 'Bapak Direktur Utama',
                'password' => 'password',
                'role' => 'Direksi',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
