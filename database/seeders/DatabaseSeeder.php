<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,      // Isi daftar fitur/modul dulu
            UserSeeder::class,             // Buat user-user awal
            UserPermissionSeeder::class,   // Atur hak akses per user
        ]);
    }
}
