<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Seed permissions
        $this->call([
            PermissionSeeder::class,
        ]);

        // 1. Disable foreign key constraints to safely clear tables
        Schema::disableForeignKeyConstraints();
        Ledger::truncate();
        Stock::truncate();
        Product::truncate();
        Supplier::truncate();
        Customer::truncate();
        Location::truncate();
        Category::truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Dapatkan atau buat User pencatat
        // Pastikan kita punya user Sari (Admin), Budi (Staff), dan Siti (Kasir)
        $users = [
            'sari' => User::where('email', 'sari@gmail.com')->first(),
            'budi' => User::firstOrCreate(
                ['email' => 'budi@gmail.com'],
                [
                    'name' => 'Budi (Staff)',
                    'password' => Hash::make('+62 857-7666-4943'),
                    'recovery_phrase' => 'budi-recovery-phrase',
                    'is_admin' => false,
                ]
            ),
            'siti' => User::firstOrCreate(
                ['email' => 'siti@gmail.com'],
                [
                    'name' => 'Siti (Kasir)',
                    'password' => Hash::make('+62 857-7666-4943'),
                    'recovery_phrase' => 'siti-recovery-phrase',
                    'is_admin' => false,
                ]
            ),
        ];

        // Jika Sari tidak ada (misal di-delete manual), buat ulang
        if (!$users['sari']) {
            $users['sari'] = User::firstOrCreate(
                ['email' => 'sari@gmail.com'],
                [
                    'name'             => 'Sari',
                    'password'         => Hash::make('+62 857-7666-4943'),
                    'recovery_phrase'  => 'secret-recovery-phrase-sara',
                    'is_admin'         => true,
                ]
            );
        }

        // 1.1 Seed Sari (Admin) permissions now that the user exists
        $this->call([
            UserPermissionSeeder::class,
        ]);

        // 1b. Assign granular permissions for Budi (Staff) & Siti (Kasir)
        $budiPermissions = [
            'dashboard'  => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'stok'       => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'kategori'   => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'lokasi'     => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'pelanggan'  => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'supplier'   => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'pembukuan'  => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'ringkasan'  => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'spk'        => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
        ];

        $sitiPermissions = [
            'dashboard'  => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'stok'       => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            'pelanggan'  => ['view' => true, 'create' => true,  'edit' => false, 'delete' => false],
            'pembukuan'  => ['view' => true, 'create' => true,  'edit' => true,  'delete' => false],
        ];

        foreach ($budiPermissions as $key => $actions) {
            $perm = \App\Models\Permission::where('key', $key)->first();
            if ($perm) {
                \App\Models\UserPermission::updateOrCreate(
                    ['user_id' => $users['budi']->id, 'permission_id' => $perm->id],
                    [
                        'can_view'   => $actions['view'],
                        'can_create' => $actions['create'],
                        'can_edit'   => $actions['edit'],
                        'can_delete' => $actions['delete'],
                    ]
                );
            }
        }

        foreach ($sitiPermissions as $key => $actions) {
            $perm = \App\Models\Permission::where('key', $key)->first();
            if ($perm) {
                \App\Models\UserPermission::updateOrCreate(
                    ['user_id' => $users['siti']->id, 'permission_id' => $perm->id],
                    [
                        'can_view'   => $actions['view'],
                        'can_create' => $actions['create'],
                        'can_edit'   => $actions['edit'],
                        'can_delete' => $actions['delete'],
                    ]
                );
            }
        }

        $userIds = array_values(array_map(fn($u) => $u->id, $users));

        // Gunakan DB Transaction agar eksekusi seeder cepat & efisien
        DB::transaction(function () use ($users, $userIds) {
            
            // 2. Seed 4 Lokasi
            $locations = [
                Location::create([
                    'name' => 'Gudang Utama',
                    'description' => 'Gudang penyimpanan pusat (Cold Storage Utama)',
                    'is_active' => true
                ]),
                Location::create([
                    'name' => 'Toko Depan',
                    'description' => 'Etalase toko untuk penjualan langsung',
                    'is_active' => true
                ]),
                Location::create([
                    'name' => 'Freezer Cadangan',
                    'description' => 'Freezer tambahan di bagian belakang toko',
                    'is_active' => true
                ]),
                Location::create([
                    'name' => 'Cold Storage Sentral',
                    'description' => 'Gudang pembekuan kapasitas besar kemitraan',
                    'is_active' => true
                ]),
            ];

            // 3. Seed Kategori Frozen Food
            $category = Category::create([
                'name' => 'Frozen Food',
                'description' => 'Makanan beku seperti nugget, sosis, bakso, dimsum, pempek, dll.'
            ]);

            // 4. Seed 3 Supplier
            $suppliers = [
                'PT Aneka Frozen Nusantara' => Supplier::create([
                    'name' => 'PT Aneka Frozen Nusantara',
                    'phone' => '081122334455',
                    'address' => 'Kawasan Industri Pulogadung, Jakarta Timur',
                    'description' => 'Supplier produk nugget, sosis, kentang, dan sayuran beku merk Fiesta & Champ',
                    'is_active' => true
                ]),
                'CV Berkah Jaya Seafood' => Supplier::create([
                    'name' => 'CV Berkah Jaya Seafood',
                    'phone' => '081234567890',
                    'address' => 'Pelabuhan Muara Baru, Jakarta Utara',
                    'description' => 'Pemasok produk olahan seafood, bakso sapi, siomay, dimsum, dan fish roll',
                    'is_active' => true
                ]),
                'UD Selera Frozen Food' => Supplier::create([
                    'name' => 'UD Selera Frozen Food',
                    'phone' => '082233445566',
                    'address' => 'Jl. Cibaduyut No. 45, Bandung',
                    'description' => 'Distributor kebab mini, pempek palembang, risoles, cireng rujak, dan cemilan beku lainnya',
                    'is_active' => true
                ]),
            ];

            // 5. Seed 5 Pelanggan (Customer)
            $customers = [
                Customer::create(['name' => 'Budi Retail', 'phone' => '0855555555', 'type' => 'seller']),
                Customer::create(['name' => 'Ibu Ani Catering', 'phone' => '0866666666', 'type' => 'seller']),
                Customer::create(['name' => 'Pak Joko', 'phone' => '0877777777', 'type' => 'non_seller']),
                Customer::create(['name' => 'Siti (Tetangga)', 'phone' => '0888888888', 'type' => 'non_seller']),
                Customer::create(['name' => 'Agen Frozen Depok', 'phone' => '0899999999', 'type' => 'seller']),
            ];

            // 6. Seed 18 Produk Frozen Food
            $productsRaw = [
                // PT Aneka Frozen Nusantara
                ['name' => 'Nugget Ayam Fiesta 500gr', 'cost' => 15000, 'price' => 38000, 'unit' => 'pack', 'supplier_name' => 'PT Aneka Frozen Nusantara'],
                ['name' => 'Sosis Sapi Champ 1kg', 'cost' => 20000, 'price' => 50000, 'unit' => 'pack', 'supplier_name' => 'PT Aneka Frozen Nusantara'],
                ['name' => 'Kentang Shoestring Champ 1kg', 'cost' => 12000, 'price' => 30000, 'unit' => 'pack', 'supplier_name' => 'PT Aneka Frozen Nusantara'],
                ['name' => 'Nugget Keju Fiesta 500gr', 'cost' => 16000, 'price' => 40000, 'unit' => 'pack', 'supplier_name' => 'PT Aneka Frozen Nusantara'],
                ['name' => 'Sosis Ayam Champ 500gr', 'cost' => 10000, 'price' => 25000, 'unit' => 'pack', 'supplier_name' => 'PT Aneka Frozen Nusantara'],
                ['name' => 'Spicy Chicken Wings Fiesta 500gr', 'cost' => 25000, 'price' => 62500, 'unit' => 'pack', 'supplier_name' => 'PT Aneka Frozen Nusantara'],

                // CV Berkah Jaya Seafood
                ['name' => 'Bakso Sapi Sumber Selera 500gr', 'cost' => 25000, 'price' => 62500, 'unit' => 'pack', 'supplier_name' => 'CV Berkah Jaya Seafood'],
                ['name' => 'Otak-otak Singapura Seafood 500gr', 'cost' => 10000, 'price' => 25000, 'unit' => 'pack', 'supplier_name' => 'CV Berkah Jaya Seafood'],
                ['name' => 'Siomay Ayam Premium Kanzler', 'cost' => 18000, 'price' => 45000, 'unit' => 'pack', 'supplier_name' => 'CV Berkah Jaya Seafood'],
                ['name' => 'Dimsum Udang Keju 15pcs', 'cost' => 16000, 'price' => 40000, 'unit' => 'pack', 'supplier_name' => 'CV Berkah Jaya Seafood'],
                ['name' => 'Tempura Udang Bento 250gr', 'cost' => 12000, 'price' => 30000, 'unit' => 'pack', 'supplier_name' => 'CV Berkah Jaya Seafood'],
                ['name' => 'Fish Roll Seafood 500gr', 'cost' => 10000, 'price' => 25000, 'unit' => 'pack', 'supplier_name' => 'CV Berkah Jaya Seafood'],

                // UD Selera Frozen Food
                ['name' => 'Kebab Mini Sapi 10pcs', 'cost' => 10000, 'price' => 25000, 'unit' => 'pack', 'supplier_name' => 'UD Selera Frozen Food'],
                ['name' => 'Pempek Palembang Campur 500gr', 'cost' => 15000, 'price' => 38000, 'unit' => 'pack', 'supplier_name' => 'UD Selera Frozen Food'],
                ['name' => 'Risoles Ragout Ayam 10pcs', 'cost' => 8000, 'price' => 20000, 'unit' => 'pack', 'supplier_name' => 'UD Selera Frozen Food'],
                ['name' => 'Cireng Rujak Higienis 20pcs', 'cost' => 5000, 'price' => 12500, 'unit' => 'pack', 'supplier_name' => 'UD Selera Frozen Food'],
                ['name' => 'Singkong Keju Merekah 1kg', 'cost' => 6000, 'price' => 15000, 'unit' => 'pack', 'supplier_name' => 'UD Selera Frozen Food'],
                ['name' => 'Donat Kentang Lembut 10pcs', 'cost' => 8000, 'price' => 20000, 'unit' => 'pack', 'supplier_name' => 'UD Selera Frozen Food'],
            ];

            $products = [];
            foreach ($productsRaw as $index => $raw) {
                // Harga grosir = diskon 10% dari eceran
                $wholesalePrice = max($raw['cost'] + 1000, $raw['price'] * 0.9);

                $products[] = Product::create([
                    'category_id' => $category->id,
                    'name' => $raw['name'],
                    'sku' => 'PRD-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'description' => 'Produk beku premium ' . $raw['name'] . '. Disuplai oleh ' . $raw['supplier_name'] . '.',
                    'cost' => $raw['cost'],
                    'price' => $raw['price'],
                    'wholesale_price' => $wholesalePrice,
                    'wholesale_min_qty' => 10,
                    'unit' => $raw['unit'],
                    'is_active' => true,
                    'lead_time' => rand(1, 7),
                    'updated_by' => $users['sari']->id,
                ]);
            }

            // 7. Seed Transaksi Pembukuan Harian (9 Bulan Kebelakang dari hari ini)
            // Permintaan: Pemasukan dan Pengeluaran bervariasi setiap bulan antara Rp 10 Juta - Rp 20 Juta (Max 20 Juta/bulan)
            $endDate = now();
            $startDate = now()->subMonths(9)->startOfMonth();

            $currentDate = clone $startDate;
            $allDates = [];
            while ($currentDate->lte($endDate)) {
                $allDates[] = clone $currentDate;
                $currentDate->addDay();
            }

            // Group dates by month key (Y-m)
            $groupedDates = [];
            foreach ($allDates as $date) {
                $monthKey = $date->format('Y-m');
                $groupedDates[$monthKey][] = $date;
            }

            $localStock = []; // [product_id_location_id] => quantity
            $isFirstMonth = true;

            foreach ($groupedDates as $monthKey => $monthDates) {
                $daysInMonth = count($monthDates);

                // Target Pemasukan & Pengeluaran Bulanan (Antara Rp 10 Juta dan Rp 20 Juta)
                $monthlyExpenseLimit = rand(10000000, 20000000); 
                $monthlyIncomeLimit  = rand(10000000, 20000000);

                // Hitung biaya operasional tetap untuk bulan ini
                $fixedExpenses = 330000; // Listrik & air (130k) + Gaji karyawan (200k)
                if ($isFirstMonth) {
                    $fixedExpenses += 1500000; // Sewa kios tahunan (1.5M)
                }

                // Sisa budget untuk pembelian barang (restock)
                $monthlyStockPurchaseLimit = max(0, $monthlyExpenseLimit - $fixedExpenses);

                $monthlyStockPurchaseExpense = 0;
                $monthlyIncomeAmount = 0;

                foreach ($monthDates as $dayIndex0 => $date) {
                    $dayIndex = $dayIndex0 + 1; // 1-indexed day in the month

                    // ── A. Biaya Operasional Tetap ──
                    // Sewa Kios (Hari ke-5 bulan pertama saja)
                    if ($isFirstMonth && $dayIndex === 5) {
                        $uId = $userIds[array_rand($userIds)];
                        Ledger::create([
                            'type' => 'expense',
                            'title' => 'Biaya operasional',
                            'amount' => 1500000.00,
                            'payment_status' => 'paid',
                            'date' => $date,
                            'reference' => 'RENT-OPR-' . $date->format('Y'),
                            'description' => 'Pembayaran sewa tempat tahunan',
                            'user_id' => $uId,
                            'updated_by' => $uId,
                        ]);
                    }

                    // Listrik & Air bulanan (Setiap tanggal 5)
                    if ($date->day === 5) {
                        $uId = $userIds[array_rand($userIds)];
                        Ledger::create([
                            'type' => 'expense',
                            'title' => 'Biaya operasional',
                            'amount' => 130000.00,
                            'payment_status' => 'paid',
                            'date' => $date,
                            'reference' => 'UTIL-OPR-' . $date->format('Ym'),
                            'description' => 'Pembayaran tagihan listrik & air bulanan',
                            'user_id' => $uId,
                            'updated_by' => $uId,
                        ]);
                    }

                    // Gaji bulanan (Setiap tanggal 25)
                    if ($date->day === 25) {
                        $uId = $userIds[array_rand($userIds)];
                        Ledger::create([
                            'type' => 'expense',
                            'title' => 'Gaji/bonus karyawan',
                            'amount' => 200000.00,
                            'payment_status' => 'paid',
                            'date' => $date,
                            'reference' => 'SAL-OPR-' . $date->format('Ym'),
                            'description' => 'Pembayaran gaji karyawan bulanan',
                            'user_id' => $uId,
                            'updated_by' => $uId,
                        ]);
                    }

                    // Pacing Harian dalam Bulan ini
                    $expectedPurchase = ($dayIndex / $daysInMonth) * $monthlyStockPurchaseLimit;
                    $expectedIncome   = ($dayIndex / $daysInMonth) * $monthlyIncomeLimit;

                    // ── B. Pembelian Stok (Restock - Pengeluaran) ──
                    $purchaseChance = 0.20; // 20% default
                    if ($monthlyStockPurchaseExpense < $expectedPurchase) {
                        $purchaseChance = 0.50; // Naikkan chance jika di bawah garis target
                    }

                    if ($monthlyStockPurchaseExpense < $monthlyStockPurchaseLimit && rand(1, 100) <= ($purchaseChance * 100)) {
                        $prodIndex = array_rand($products);
                        $product = $products[$prodIndex];
                        $location = $locations[array_rand($locations)];
                        $supplierName = $productsRaw[$prodIndex]['supplier_name'];
                        $supplier = $suppliers[$supplierName];

                        $qtyToBuy = rand(15, 30);
                        $purchaseCost = $qtyToBuy * $product->cost;

                        // Capping check agar tidak melampaui limit bulan ini
                        if ($monthlyStockPurchaseExpense + $purchaseCost >= $monthlyStockPurchaseLimit) {
                            $purchaseCost = $monthlyStockPurchaseLimit - $monthlyStockPurchaseExpense;
                            $qtyToBuy = (int) max(1, round($purchaseCost / $product->cost));
                            $purchaseCost = $qtyToBuy * $product->cost;
                            if ($monthlyStockPurchaseExpense + $purchaseCost > $monthlyStockPurchaseLimit) {
                                $purchaseCost = $monthlyStockPurchaseLimit - $monthlyStockPurchaseExpense;
                            }
                        }

                        if ($purchaseCost > 0) {
                            $uId = $userIds[array_rand($userIds)];
                            $buyUnpaid = rand(1, 10) === 1;

                            Ledger::create([
                                'type' => 'expense',
                                'title' => 'Pembelian stok',
                                'amount' => $purchaseCost,
                                'payment_status' => $buyUnpaid ? 'unpaid' : 'paid',
                                'due_date' => $buyUnpaid ? (clone $date)->addDays(30) : null,
                                'date' => $date,
                                'reference' => 'INV-SUP-' . $date->format('Ym') . '-' . rand(1000, 9999),
                                'product_id' => $product->id,
                                'location_id' => $location->id,
                                'supplier_id' => $supplier->id,
                                'quantity' => $qtyToBuy,
                                'stock_movement' => 'in',
                                'user_id' => $uId,
                                'updated_by' => $uId,
                            ]);

                            $stockKey = "{$product->id}_{$location->id}";
                            $localStock[$stockKey] = ($localStock[$stockKey] ?? 0) + $qtyToBuy;
                            $monthlyStockPurchaseExpense += $purchaseCost;
                        }
                    }

                    // ── C. Penjualan (Sales - Pemasukan) ──
                    $saleChance = 0.55; // 55% default
                    if ($monthlyIncomeAmount < $expectedIncome) {
                        $saleChance = 0.85; // Naikkan chance jika di bawah garis target
                    }

                    if ($monthlyIncomeAmount < $monthlyIncomeLimit && rand(1, 100) <= ($saleChance * 100)) {
                        // Ambil produk yang stok lokalnya tersedia
                        $availableKeys = array_keys(array_filter($localStock, fn($q) => $q > 0));
                        if (!empty($availableKeys)) {
                            $key = $availableKeys[array_rand($availableKeys)];
                            [$prodId, $locId] = explode('_', $key);

                            $product = collect($products)->firstWhere('id', $prodId);
                            $location = collect($locations)->firstWhere('id', $locId);

                            $maxQty = $localStock[$key];
                            $qtyToSell = rand(2, min(8, $maxQty));

                            if ($qtyToSell > 0) {
                                $cust = rand(1, 10) > 2 ? $customers[array_rand($customers)] : null;

                                // Gunakan harga grosir jika memenuhi syarat qty minimal agen
                                $appliedPrice = ($cust && $cust->type === 'seller' && $qtyToSell >= $product->wholesale_min_qty)
                                    ? $product->wholesale_price
                                    : $product->price;

                                $saleAmount = $qtyToSell * $appliedPrice;

                                // Capping check agar tidak melampaui limit bulan ini
                                if ($monthlyIncomeAmount + $saleAmount >= $monthlyIncomeLimit) {
                                    $saleAmount = $monthlyIncomeLimit - $monthlyIncomeAmount;
                                }

                                if ($saleAmount > 0) {
                                    $uId = $userIds[array_rand($userIds)];
                                    $saleUnpaid = rand(1, 100) <= 15;

                                    Ledger::create([
                                        'type' => 'income',
                                        'title' => 'Penjualan',
                                        'amount' => $saleAmount,
                                        'payment_status' => $saleUnpaid ? 'unpaid' : 'paid',
                                        'due_date' => $saleUnpaid ? (clone $date)->addDays(7) : null,
                                        'date' => $date,
                                        'reference' => 'TRX-' . $date->format('Ym') . '-' . rand(10000, 99999),
                                        'product_id' => $product->id,
                                        'location_id' => $location->id,
                                        'customer_id' => $cust ? $cust->id : null,
                                        'quantity' => $qtyToSell,
                                        'stock_movement' => 'out',
                                        'user_id' => $uId,
                                        'updated_by' => $uId,
                                    ]);

                                    $localStock[$key] -= $qtyToSell;
                                    $monthlyIncomeAmount += $saleAmount;
                                }
                            }
                        }
                    }
                }

                // ── D. Cleanup Bulan Ini (Hari Terakhir Bulan) ──
                $lastDayOfMonth = $monthDates[count($monthDates) - 1];

                // 1. Penuhi target Pengeluaran bulanan
                while ($monthlyStockPurchaseExpense < $monthlyStockPurchaseLimit) {
                    $prodIndex = array_rand($products);
                    $product = $products[$prodIndex];
                    $location = $locations[array_rand($locations)];
                    $supplierName = $productsRaw[$prodIndex]['supplier_name'];
                    $supplier = $suppliers[$supplierName];

                    $qtyToBuy = rand(15, 30);
                    $purchaseCost = $qtyToBuy * $product->cost;

                    if ($monthlyStockPurchaseExpense + $purchaseCost >= $monthlyStockPurchaseLimit) {
                        $purchaseCost = $monthlyStockPurchaseLimit - $monthlyStockPurchaseExpense;
                        $qtyToBuy = (int) max(1, round($purchaseCost / $product->cost));
                        $purchaseCost = $qtyToBuy * $product->cost;
                        if ($monthlyStockPurchaseExpense + $purchaseCost > $monthlyStockPurchaseLimit) {
                            $purchaseCost = $monthlyStockPurchaseLimit - $monthlyStockPurchaseExpense;
                        }
                    }

                    if ($purchaseCost <= 0) {
                        break;
                    }

                    $uId = $userIds[array_rand($userIds)];
                    Ledger::create([
                        'type' => 'expense',
                        'title' => 'Pembelian stok',
                        'amount' => $purchaseCost,
                        'payment_status' => 'paid',
                        'date' => $lastDayOfMonth,
                        'reference' => 'INV-SUP-CLN-' . $lastDayOfMonth->format('Ym') . '-' . rand(1000, 9999),
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'supplier_id' => $supplier->id,
                        'quantity' => $qtyToBuy,
                        'stock_movement' => 'in',
                        'user_id' => $uId,
                        'updated_by' => $uId,
                    ]);

                    $stockKey = "{$product->id}_{$location->id}";
                    $localStock[$stockKey] = ($localStock[$stockKey] ?? 0) + $qtyToBuy;
                    $monthlyStockPurchaseExpense += $purchaseCost;
                }

                // 2. Penuhi target Pemasukan bulanan
                while ($monthlyIncomeAmount < $monthlyIncomeLimit) {
                    $availableKeys = array_keys(array_filter($localStock, fn($q) => $q > 0));
                    if (empty($availableKeys)) {
                        // Injeksi penyesuaian stok 0 biaya agar tidak melampaui limit pengeluaran
                        $product = $products[0];
                        $location = $locations[0];
                        $supplierName = $productsRaw[0]['supplier_name'];
                        $supplier = $suppliers[$supplierName];
                        $qtyToBuy = 50;
                        $uId = $userIds[array_rand($userIds)];

                        Ledger::create([
                            'type' => 'expense',
                            'title' => 'Penyesuaian stok masuk',
                            'amount' => 0.00,
                            'payment_status' => 'paid',
                            'date' => $lastDayOfMonth,
                            'reference' => 'ADJ-SUP-CLN-' . $lastDayOfMonth->format('Ym') . '-' . rand(1000, 9999),
                            'product_id' => $product->id,
                            'location_id' => $location->id,
                            'supplier_id' => $supplier->id,
                            'quantity' => $qtyToBuy,
                            'stock_movement' => 'in',
                            'user_id' => $uId,
                            'updated_by' => $uId,
                        ]);

                        $stockKey = "{$product->id}_{$location->id}";
                        $localStock[$stockKey] = ($localStock[$stockKey] ?? 0) + $qtyToBuy;
                        continue;
                    }

                    $key = $availableKeys[array_rand($availableKeys)];
                    [$prodId, $locId] = explode('_', $key);
                    $product = collect($products)->firstWhere('id', $prodId);
                    $location = collect($locations)->firstWhere('id', $locId);

                    $qtyToSell = min(5, $localStock[$key]);
                    if ($qtyToSell <= 0) {
                        continue;
                    }

                    $cust = $customers[array_rand($customers)];
                    
                    $appliedPrice = ($cust && $cust->type === 'seller' && $qtyToSell >= $product->wholesale_min_qty)
                        ? $product->wholesale_price
                        : $product->price;
                    $saleAmount = $qtyToSell * $appliedPrice;

                    if ($monthlyIncomeAmount + $saleAmount >= $monthlyIncomeLimit) {
                        $saleAmount = $monthlyIncomeLimit - $monthlyIncomeAmount;
                    }

                    if ($saleAmount <= 0) {
                        break;
                    }

                    $uId = $userIds[array_rand($userIds)];
                    Ledger::create([
                        'type' => 'income',
                        'title' => 'Penjualan',
                        'amount' => $saleAmount,
                        'payment_status' => 'paid',
                        'date' => $lastDayOfMonth,
                        'reference' => 'TRX-CLN-' . $lastDayOfMonth->format('Ym') . '-' . rand(10000, 99999),
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'customer_id' => $cust->id,
                        'quantity' => $qtyToSell,
                        'stock_movement' => 'out',
                        'user_id' => $uId,
                        'updated_by' => $uId,
                    ]);

                    $localStock[$key] -= $qtyToSell;
                    $monthlyIncomeAmount += $saleAmount;
                }

                $isFirstMonth = false;
            }
        });
    }
}
