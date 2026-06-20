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
        // Peta perubahan key lama ke key baru
        $renameMap = [
            'products'   => 'stok',
            'categories' => 'kategori',
            'locations'  => 'lokasi',
            'ledger'     => 'pembukuan',
            'summary'    => 'ringkasan',
            'customers'  => 'pelanggan',
            'suppliers'  => 'supplier',
            'users'      => 'pengguna',
        ];

        // 1. Rename existing permissions to preserve user settings
        foreach ($renameMap as $oldKey => $newKey) {
            $permission = Permission::where('key', $oldKey)->first();
            if ($permission) {
                $exists = Permission::where('key', $newKey)->first();
                if ($exists) {
                    $permission->delete(); // Cascade delete user permissions if duplicate
                } else {
                    $permission->update(['key' => $newKey]);
                }
            }
        }

        // 2. Definisi semua permission modul yang sesuai dengan nama rute
        $permissions = [
            [
                'key'         => 'dashboard',
                'label'       => 'Dashboard',
                'category'    => 'dashboard',
                'description' => 'Akses ke halaman dashboard utama aplikasi.',
            ],
            [
                'key'         => 'stok',
                'label'       => 'Stok Produk',
                'category'    => 'bisnis',
                'description' => 'Akses ke data produk, inventori, harga, dan stok.',
            ],
            [
                'key'         => 'kategori',
                'label'       => 'Kategori Produk',
                'category'    => 'bisnis',
                'description' => 'Akses ke manajemen kategori produk.',
            ],
            [
                'key'         => 'lokasi',
                'label'       => 'Lokasi Gudang',
                'category'    => 'bisnis',
                'description' => 'Akses ke manajemen lokasi stok dan gudang.',
            ],
            [
                'key'         => 'pelanggan',
                'label'       => 'Data Pelanggan',
                'category'    => 'bisnis',
                'description' => 'Akses ke data pelanggan dan riwayat.',
            ],
            [
                'key'         => 'supplier',
                'label'       => 'Data Supplier',
                'category'    => 'bisnis',
                'description' => 'Akses ke manajemen supplier.',
            ],
            [
                'key'         => 'spk',
                'label'       => 'SPK Prioritas',
                'category'    => 'bisnis',
                'description' => 'Akses ke analisis SPK prioritas restock.',
            ],
            [
                'key'         => 'pembukuan',
                'label'       => 'Pembukuan',
                'category'    => 'keuangan',
                'description' => 'Akses ke catatan pemasukan dan pengeluaran.',
            ],
            [
                'key'         => 'ringkasan',
                'label'       => 'Ringkasan Keuangan',
                'category'    => 'keuangan',
                'description' => 'Akses ke laporan dan ringkasan pembukuan.',
            ],
            [
                'key'         => 'pengguna',
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

        // 3. Bersihkan jika ada permission lama yang tersisa
        $validKeys = collect($permissions)->pluck('key')->toArray();
        Permission::whereNotIn('key', $validKeys)->delete();

        $this->command->info('✅ ' . count($permissions) . ' permission berhasil di-seed.');
    }
}
