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
                'email' => 'staf@sekretariat.corp',
                'name' => 'Staf Sekretariat',
                'role' => 'Staf',
            ],
            [
                'email' => 'direksi@sekretariat.corp',
                'name' => 'Bapak Direktur Utama',
                'role' => 'Direksi',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
