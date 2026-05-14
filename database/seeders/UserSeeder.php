<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed tabel users dengan user awal.
     *
     * Jalankan dengan: php artisan db:seed --class=UserSeeder
     */
    public function run(): void
    {
        $users = [
            [
                'name'             => 'Sari',
                'email'            => 'sari@gmail.com',
                'password'         => Hash::make('+62 857-7666-4943'),
                'recovery_phrase'  => 'secret-recovery-phrase-sara',
                'is_admin'         => true,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }

        $this->command->info('✅ ' . count($users) . ' user berhasil di-seed.');
    }
}
