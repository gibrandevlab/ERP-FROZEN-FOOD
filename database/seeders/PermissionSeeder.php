<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Isi tabel permissions dengan modul-modul awal aplikasi.
     *
     * Jalankan dengan: php artisan db:seed --class=PermissionSeeder
     *
     * Gunakan updateOrCreate agar aman dijalankan berkali-kali
     * tanpa membuat data duplikat.
     */
    public function run(): void
    {
        $permissions = [
            // ─── Kategori: Dashboard ───────────────────────────
            [
                'key'         => 'dashboard',
                'label'       => 'Dashboard',
                'category'    => 'dashboard',
                'description' => 'Akses ke halaman dashboard utama aplikasi.',
            ],
            // ─── Kategori: Bisnis ────────────────────────────
            [
                'key'         => 'products',
                'label'       => 'Manajemen Stok',
                'category'    => 'bisnis',
                'description' => 'Akses ke data produk, inventori, harga, dan stok.',
            ],
            [
                'key'         => 'categories',
                'label'       => 'Kategori Produk',
                'category'    => 'bisnis',
                'description' => 'Akses ke manajemen kategori produk.',
            ],
            [
                'key'         => 'locations',
                'label'       => 'Lokasi Gudang',
                'category'    => 'bisnis',
                'description' => 'Akses ke manajemen lokasi stok dan gudang.',
            ],

            // ─── Kategori: Keuangan ──────────────────────────
            [
                'key'         => 'ledger',
                'label'       => 'Pembukuan',
                'category'    => 'keuangan',
                'description' => 'Akses ke catatan pemasukan dan pengeluaran.',
            ],

            // ─── Kategori: Sistem ────────────────────────────
            [
                'key'         => 'users',
                'label'       => 'Manajemen Pengguna',
                'category'    => 'sistem',
                'description' => 'Akses ke data user dan pengaturan hak akses staf.',
            ],
        ];

        foreach ($permissions as $data) {
            Permission::updateOrCreate(
                ['key' => $data['key']], // Cari berdasarkan key
                $data                    // Update atau buat dengan data ini
            );
        }

        $this->command->info('✅ ' . count($permissions) . ' permission berhasil di-seed.');
    }
}
