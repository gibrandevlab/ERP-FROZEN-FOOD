<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ledger;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lokasi
        $locations = [
            Location::firstOrCreate(['name' => 'Gudang Utama'], ['description' => 'Gudang penyimpanan pusat', 'is_active' => true]),
            Location::firstOrCreate(['name' => 'Toko Depan'], ['description' => 'Etalase toko untuk penjualan', 'is_active' => true]),
            Location::firstOrCreate(['name' => 'Freezer Cadangan'], ['description' => 'Freezer tambahan di belakang', 'is_active' => true]),
        ];

        // 2. Kategori
        $categories = [
            Category::firstOrCreate(['name' => 'Frozen Food'], ['description' => 'Makanan beku seperti nugget, sosis, baso']),
            Category::firstOrCreate(['name' => 'Bumbu Dapur'], ['description' => 'Berbagai macam bumbu masak kemasan']),
            Category::firstOrCreate(['name' => 'Minuman'], ['description' => 'Minuman kemasan dan serbuk instan']),
            Category::firstOrCreate(['name' => 'Sembako'], ['description' => 'Sembilan bahan pokok']),
            Category::firstOrCreate(['name' => 'Cemilan'], ['description' => 'Makanan ringan dan snack']),
        ];

        // 3. Supplier
        $suppliers = [
            \App\Models\Supplier::firstOrCreate(['name' => 'PT Aneka Frozen Nusantara'], ['phone' => '0811111111', 'address' => 'Jakarta', 'description' => 'Supplier nugget dan sosis', 'is_active' => true]),
            \App\Models\Supplier::firstOrCreate(['name' => 'CV Sumber Rejeki Sembako'], ['phone' => '0822222222', 'address' => 'Bandung', 'description' => 'Supplier beras, gula, minyak', 'is_active' => true]),
            \App\Models\Supplier::firstOrCreate(['name' => 'Distributor Minuman Bersama'], ['phone' => '0833333333', 'address' => 'Surabaya', 'description' => 'Supplier aneka minuman ringan', 'is_active' => true]),
        ];

        // 4. Customer
        $customers = [
            \App\Models\Customer::firstOrCreate(['name' => 'Budi Retail'], ['phone' => '0855555555', 'type' => 'seller']),
            \App\Models\Customer::firstOrCreate(['name' => 'Ibu Ani Catering'], ['phone' => '0866666666', 'type' => 'seller']),
            \App\Models\Customer::firstOrCreate(['name' => 'Pak Joko'], ['phone' => '0877777777', 'type' => 'non_seller']),
            \App\Models\Customer::firstOrCreate(['name' => 'Siti (Tetangga)'], ['phone' => '0888888888', 'type' => 'non_seller']),
        ];

        // 5. Produk
        $productsData = [
            ['name' => 'Nugget Ayam Fiesta 500gr', 'category' => 'Frozen Food', 'cost' => 35000, 'price' => 42000, 'unit' => 'pcs'],
            ['name' => 'Sosis Champ 1kg', 'category' => 'Frozen Food', 'cost' => 45000, 'price' => 55000, 'unit' => 'pack'],
            ['name' => 'Kentang Shoestring 1kg', 'category' => 'Frozen Food', 'cost' => 28000, 'price' => 35000, 'unit' => 'pack'],
            ['name' => 'Minyak Goreng Bimoli 2L', 'category' => 'Sembako', 'cost' => 32000, 'price' => 36000, 'unit' => 'pcs'],
            ['name' => 'Gula Pasir Gulaku 1kg', 'category' => 'Sembako', 'cost' => 14000, 'price' => 16000, 'unit' => 'kg'],
            ['name' => 'Beras Raja Lele 5kg', 'category' => 'Sembako', 'cost' => 65000, 'price' => 72000, 'unit' => 'sak'],
            ['name' => 'Kecap Bango 520ml', 'category' => 'Bumbu Dapur', 'cost' => 20000, 'price' => 24000, 'unit' => 'pouch'],
            ['name' => 'Saus Sambal ABC 340ml', 'category' => 'Bumbu Dapur', 'cost' => 12000, 'price' => 15000, 'unit' => 'botol'],
            ['name' => 'Teh Pucuk Harum 350ml', 'category' => 'Minuman', 'cost' => 2500, 'price' => 3500, 'unit' => 'botol'],
            ['name' => 'Kopi Kapal Api 380gr', 'category' => 'Minuman', 'cost' => 22000, 'price' => 27000, 'unit' => 'pcs'],
            ['name' => 'Keripik Singkong Kusuka', 'category' => 'Cemilan', 'cost' => 8000, 'price' => 11000, 'unit' => 'pcs'],
            ['name' => 'Chitato Sapi Panggang 68gr', 'category' => 'Cemilan', 'cost' => 9000, 'price' => 12000, 'unit' => 'pcs'],
            ['name' => 'Tepung Terigu Segitiga Biru 1kg', 'category' => 'Sembako', 'cost' => 11000, 'price' => 13500, 'unit' => 'kg'],
            ['name' => 'Margarin Blue Band 200gr', 'category' => 'Bumbu Dapur', 'cost' => 7500, 'price' => 9500, 'unit' => 'sachet'],
            ['name' => 'Bakso Sapi Sumber Selera', 'category' => 'Frozen Food', 'cost' => 55000, 'price' => 65000, 'unit' => 'pack'],
        ];

        $products = [];
        foreach ($productsData as $i => $data) {
            $cat = collect($categories)->firstWhere('name', $data['category']);
            // Harga grosir = modal + margin kecil, atau diskon 10% dari harga eceran
            $wholesalePrice = max($data['cost'] + 1000, $data['price'] * 0.9);
            
            $products[] = Product::firstOrCreate(
                ['name' => $data['name']],
                [
                    'category_id' => $cat->id,
                    'sku' => 'PRD-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'description' => 'Produk ' . $data['name'] . ' dengan kualitas terbaik dan harga terjangkau.',
                    'cost' => $data['cost'],
                    'price' => $data['price'],
                    'wholesale_price' => $wholesalePrice,
                    'wholesale_min_qty' => 10,
                    'unit' => $data['unit'],
                    'is_active' => true,
                ]
            );
        }

        // 6. Pembukuan (Ledger) & Mutasi Stok
        // Simulasi transaksi selama 2 bulan terakhir
        $startDate = Carbon::now()->subMonths(2);
        
        foreach ($products as $product) {
            $loc = $locations[array_rand($locations)]; // Pilih lokasi acak
            $sup = $suppliers[array_rand($suppliers)]; // Pilih supplier acak
            
            // A. Stok Awal (Modal Keluar / Restock ke Supplier)
            $qtyInit = rand(20, 100);
            
            // Simulasi kas bon ke supplier (10% peluang unpaid)
            $isDebt = rand(1, 10) === 1;
            
            Ledger::create([
                'type' => 'expense',
                'title' => 'Pembelian Stok Awal: ' . $product->name,
                'amount' => $qtyInit * $product->cost,
                'payment_status' => $isDebt ? 'unpaid' : 'paid',
                'due_date' => $isDebt ? now()->addDays(30) : null,
                'date' => (clone $startDate)->addDays(rand(1, 5)),
                'reference' => 'INV-INIT-' . rand(1000, 9999),
                'product_id' => $product->id,
                'location_id' => $loc->id,
                'supplier_id' => $sup->id, // integrasi supplier
                'quantity' => $qtyInit,
                'stock_movement' => 'in',
            ]);

            // B. Simulasi Penjualan (Pemasukan dari Pelanggan)
            $salesCount = rand(2, 6);
            for ($j = 0; $j < $salesCount; $j++) {
                $qtySold = rand(1, 5);
                
                // Ada probabilitas 50% pembeli terdaftar (Customer)
                $cust = rand(1, 2) === 1 ? $customers[array_rand($customers)] : null;
                
                // Pelanggan Seller biasanya beli partai besar (harga grosir)
                $appliedPrice = ($cust && $cust->type == 'seller' && $qtySold >= 10) ? $product->wholesale_price : $product->price;
                
                // Simulasi Piutang (orang ngutang ke kita)
                $isCredit = rand(1, 15) === 1; // peluang kecil orang ngutang
                
                Ledger::create([
                    'type' => 'income',
                    'title' => 'Penjualan: ' . $product->name,
                    'amount' => $qtySold * $appliedPrice,
                    'payment_status' => $isCredit ? 'unpaid' : 'paid',
                    'due_date' => $isCredit ? now()->addDays(7) : null,
                    'date' => (clone $startDate)->addDays(rand(6, 50)),
                    'reference' => 'TRX-' . rand(10000, 99999),
                    'product_id' => $product->id,
                    'location_id' => $loc->id,
                    'customer_id' => $cust ? $cust->id : null, // integrasi customer
                    'quantity' => $qtySold,
                    'stock_movement' => 'out', // Otomatis mengurangi stok
                ]);
            }
        }
        
        // 7. Tambahan Transaksi Operasional Acak (tanpa mutasi stok/produk)
        for ($k = 0; $k < 5; $k++) {
            Ledger::create([
                'type' => 'expense',
                'title' => 'Biaya Operasional (Listrik/Air/Lainnya)',
                'amount' => rand(50000, 200000),
                'payment_status' => 'paid',
                'date' => (clone $startDate)->addDays(rand(10, 50)),
                'reference' => 'OPR-' . rand(100, 999),
            ]);
        }
    }
}
