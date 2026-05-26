<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddNewPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perms = [
            ['key' => 'customers', 'label' => 'Data Pelanggan', 'category' => 'bisnis', 'description' => 'Akses ke data pelanggan dan riwayat.'],
            ['key' => 'suppliers', 'label' => 'Data Supplier', 'category' => 'bisnis', 'description' => 'Akses ke manajemen supplier.'],
            ['key' => 'spk', 'label' => 'SPK Prioritas', 'category' => 'bisnis', 'description' => 'Akses ke analisis SPK prioritas restock.'],
            ['key' => 'summary', 'label' => 'Ringkasan Keuangan', 'category' => 'keuangan', 'description' => 'Akses ke laporan dan ringkasan pembukuan.']
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['key' => $p['key']], $p);
        }
    }
}
