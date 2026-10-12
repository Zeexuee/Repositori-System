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
                'email' => 'diana@sekretariat.com',
                'name' => 'Diana (Staff)',
                'role' => 'staff',
            ],
            [
                'email' => 'dea@sekretariat.com',
                'name' => 'Dea (Staff)',
                'role' => 'staff',
            ],
            [
                'email' => 'akuntest@sekretariat.com',
                'name' => 'Akun Test (Staff)',
                'role' => 'staff',
            ],
            [
                'email' => 'master@sekretariat.com',
                'name' => 'Master User',
                'role' => 'master',
            ],
        ];

        foreach ($users as $userData) {
            // Password = [nama depan email] + 123?
            $username = explode('@', $userData['email'])[0];
            $password = $username . '123?';

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($password),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
