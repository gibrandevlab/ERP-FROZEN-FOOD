<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder lengkap untuk Riza Frozen Food ERP.
 *
 * Jalankan: php artisan db:seed --class=AllTableSeeder
 *
 * Aman untuk database yang SUDAH berisi data:
 *   - Seeder tidak menghapus apa pun dan tidak memakai migrate:fresh.
 *   - User dibuat dengan ID dari auto-increment (tidak menimpa user yang ada).
 *   - Permission dicari berdasarkan key; yang belum ada saja yang ditambahkan.
 *   - Tabel lain memakai ID eksplisit yang digeser (offset) dari MAX(id) tabel tersebut,
 *     lalu relasinya ikut digeser, jadi tidak bentrok primary key.
 *   - Seluruh proses dibungkus transaksi: gagal di tengah = rollback, tidak ada data setengah jadi.
 *   - Dijalankan kedua kali akan dilewati (ditandai dari email admin seeder).
 *   Catatan: kolom unique lain (mis. slug kategori, sku produk) tetap bisa bentrok kalau
 *   data proyek yang sudah ada memakai nilai yang sama persis.
 *
 * Master data (users, permissions, categories, locations, suppliers, customers,
 * products) ditulis manual. Sisanya DI-GENERATE lewat simulasi 6 bulan terakhir:
 *
 *   - ledgers           : penjualan (income), pembelian restock (expense), biaya operasional
 *   - purchase_requests : otomatis muncul saat stok menipis, alur pending -> approved ->
 *                         purchased -> completed (atau rejected). Status "completed" berarti
 *                         barang sudah diterima; hanya PR completed yang punya ledger_id
 *   - stocks            : = total stock_movement dari semua ledger per produk (pasti sinkron)
 *
 * Soal keacakan: mt_srand() mengunci pilihan mt_rand(), jadi POLA simulasinya sama tiap
 * seed (produk laris, kejadian stok habis, dst). Hasilnya TIDAK identik persis: tanggal
 * dihitung relatif terhadap hari ini, dan hash password/recovery phrase serta
 * remember_token memang acak.
 */
class AllTableSeeder extends Seeder
{
    // ------------------------------------------------------------------
    // NILAI ENUM / STRING. Disesuaikan dengan migration proyek.
    // ------------------------------------------------------------------
    // customers.type: enum('seller', 'non_seller'). Seller = pembeli grosir/reseller.
    private const CUSTOMER_RETAIL = 'non_seller';
    private const CUSTOMER_WHOLESALE = 'seller';

    private const INCOME = 'income';
    private const EXPENSE = 'expense';

    private const PAID = 'paid';
    private const UNPAID = 'unpaid';

    private const PR_PENDING = 'pending';
    private const PR_APPROVED = 'approved';
    private const PR_REJECTED = 'rejected';
    private const PR_PURCHASED = 'purchased';
    private const PR_COMPLETED = 'completed'; // status akhir enum purchase_requests.status = barang sudah diterima

    // ------------------------------------------------------------------
    // STATE SIMULASI
    // ------------------------------------------------------------------
    private Carbon $today;
    private array $userId = [];         // role ('admin', 'kasir', 'gudang', 'purchasing') => users.id asli
    private array $meta = [];            // product_id => data bantu (supplier, lokasi, harga, dll)
    private array $supplierNames = [];
    private array $customers = [];       // id => ['name', 'type']
    private array $weightedProducts = [];

    private array $ledgers = [];
    private array $purchaseRequests = [];
    private array $unitCost = [];        // index PR => harga beli per unit
    private array $receipts = [];        // 'Y-m-d' => [index PR yang diterima hari itu]

    private array $stock = [];           // product_id => qty saat ini
    private array $inbound = [];         // product_id => qty yang sudah dipesan tapi belum diterima
    private array $lastRequest = [];     // product_id => Carbon

    private int $ledgerSeq = 0;
    private int $prSeq = 0;
    private array $counters = ['inv' => [], 'pr' => []];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seed();
        });

        Cache::forget('app.permissions');
    }

    private function seed(): void
    {
        $adminEmail = 'admin@rizafrozenfood.test';
        if (DB::table('users')->where('email', $adminEmail)->exists()) {
            $this->command?->warn('AllTablesSeeder dilewati: data seeder ini sudah ada (' . $adminEmail . ').');

            return;
        }

        mt_srand(20260101);

        $now = now();
        $this->today = Carbon::today();

        // ============ MASTER DATA ============
        // Insert lewat DB::table() melewati cast 'hashed' di model User,
        // jadi password dan recovery_phrase di-hash manual di sini.
        $password = Hash::make('password123');

        $userDefs = [
            'admin' => ['Riza Admin', $adminEmail, true, 'beku nugget sosis dimsum bakso kentang'],
            'kasir' => ['Siti Rahma', 'kasir@rizafrozenfood.test', false, 'kasir toko freezer tunai nota harian'],
            'gudang' => ['Budi Santoso', 'gudang@rizafrozenfood.test', false, 'gudang stok palet dingin opname rak'],
            'purchasing' => ['Dewi Lestari', 'purchasing@rizafrozenfood.test', false, 'supplier pesan faktur tempo kirim terima'],
        ];
        foreach ($userDefs as $role => [$name, $email, $isAdmin, $phrase]) {
            // ID dari auto-increment supaya tidak menabrak user yang sudah ada.
            $this->userId[$role] = DB::table('users')->insertGetId([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'recovery_phrase' => Hash::make($phrase),
                'is_admin' => $isAdmin,
                'remember_token' => Str::random(10),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Key mengikuti PermissionSeeder proyek. Label/kategori di bawah hanya dipakai
        // kalau key tersebut belum ada di tabel permissions.
        $permissionDefs = [
            ['dashboard', 'Dashboard', 'umum', 'Melihat ringkasan penjualan dan stok'],
            ['stok', 'Stok', 'inventori', 'Kelola produk dan stok per lokasi'],
            ['kategori', 'Kategori', 'master', 'Kelola kategori produk'],
            ['lokasi', 'Lokasi', 'master', 'Kelola lokasi gudang dan freezer'],
            ['pelanggan', 'Pelanggan', 'master', 'Kelola data pelanggan'],
            ['supplier', 'Supplier', 'master', 'Kelola data supplier'],
            ['spk', 'SPK', 'analisis', 'Sistem pendukung keputusan pemilihan supplier'],
            ['purchase_request', 'Permintaan Pembelian', 'inventori', 'Ajukan dan proses permintaan pembelian'],
            ['pembukuan', 'Pembukuan', 'transaksi', 'Catat pemasukan dan pengeluaran'],
            ['ringkasan', 'Ringkasan', 'umum', 'Lihat ringkasan dan laporan'],
            ['pengguna', 'Pengguna', 'admin', 'Kelola pengguna dan hak akses'],
        ];
        $permissionId = [];
        foreach ($permissionDefs as [$key, $label, $category, $desc]) {
            $existing = DB::table('permissions')->where('key', $key)->value('id');
            $permissionId[$key] = $existing ?? DB::table('permissions')->insertGetId([
                'key' => $key,
                'label' => $label,
                'category' => $category,
                'description' => $desc,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $categories = [
            ['id' => 1, 'name' => 'Olahan Ayam', 'description' => 'Nugget, katsu, wings, dan olahan ayam beku lainnya'],
            ['id' => 2, 'name' => 'Sosis & Bakso', 'description' => 'Sosis dan bakso beku siap masak'],
            ['id' => 3, 'name' => 'Dimsum & Seafood', 'description' => 'Dimsum, siomay, otak-otak, udang, dan olahan laut'],
            ['id' => 4, 'name' => 'Kentang & Snack', 'description' => 'Kentang goreng, cireng, dan snack beku'],
            ['id' => 5, 'name' => 'Daging Beku', 'description' => 'Daging sapi dan fillet ayam potong beku'],
        ];
        $categories = array_map(fn ($c) => $c + ['slug' => Str::slug($c['name']), 'user_id' => $this->userId['admin']], $categories);

        $locations = [
            ['id' => 1, 'name' => 'Gudang Utama', 'description' => 'Cold storage utama, suhu -20°C', 'is_active' => true, 'user_id' => $this->userId['admin']],
            ['id' => 2, 'name' => 'Freezer Toko', 'description' => 'Freezer display di toko untuk penjualan eceran', 'is_active' => true, 'user_id' => $this->userId['admin']],
            ['id' => 3, 'name' => 'Cold Storage Cadangan', 'description' => 'Cadangan kapasitas, belum dipakai', 'is_active' => false, 'user_id' => $this->userId['admin']],
        ];

        $suppliers = [
            ['id' => 1, 'name' => 'PT Mitra Pangan Nusantara', 'phone' => '0215550101', 'address' => 'Jl. Raya Cibitung No. 12, Bekasi', 'description' => 'Sosis, bakso, dan daging sapi beku', 'is_active' => true],
            ['id' => 2, 'name' => 'CV Cemilan Beku Mandiri', 'phone' => '0215550102', 'address' => 'Jl. Industri Raya No. 7, Cikarang', 'description' => 'Cireng, cheese roll, dan snack beku', 'is_active' => true],
            ['id' => 3, 'name' => 'UD Laut Segar Bekasi', 'phone' => '081311110003', 'address' => 'Pasar Induk Kramat Jati, Jakarta Timur', 'description' => 'Dimsum, olahan ikan, dan udang', 'is_active' => true],
            ['id' => 4, 'name' => 'CV Berkah Olahan Ayam', 'phone' => '081311110004', 'address' => 'Jl. Peternakan No. 21, Bogor', 'description' => 'Nugget, katsu, wings, dan fillet ayam', 'is_active' => true],
            ['id' => 5, 'name' => 'PT Kentang Prima Sejahtera', 'phone' => '0215550105', 'address' => 'Kawasan Industri MM2100, Cikarang', 'description' => 'Kentang goreng dan wedges beku', 'is_active' => true],
        ];
        foreach ($suppliers as $s) {
            $this->supplierNames[$s['id']] = $s['name'];
        }

        $customers = [
            ['id' => 1, 'name' => 'Pelanggan Umum', 'phone' => null, 'type' => self::CUSTOMER_RETAIL],
            ['id' => 2, 'name' => 'Ibu Rina Wulandari', 'phone' => '081290001002', 'type' => self::CUSTOMER_RETAIL],
            ['id' => 3, 'name' => 'Pak Hendra Gunawan', 'phone' => '081290001003', 'type' => self::CUSTOMER_RETAIL],
            ['id' => 4, 'name' => 'Mbak Sari Puspita', 'phone' => '081290001004', 'type' => self::CUSTOMER_RETAIL],
            ['id' => 5, 'name' => 'Warung Makan Bu Ani', 'phone' => '081290001005', 'type' => self::CUSTOMER_WHOLESALE],
            ['id' => 6, 'name' => 'Kedai Bakso Pak Jono', 'phone' => '081290001006', 'type' => self::CUSTOMER_WHOLESALE],
            ['id' => 7, 'name' => 'Katering Barokah', 'phone' => '081290001007', 'type' => self::CUSTOMER_WHOLESALE],
            ['id' => 8, 'name' => 'Toko Kelontong Maju Jaya', 'phone' => '081290001008', 'type' => self::CUSTOMER_WHOLESALE],
            ['id' => 9, 'name' => 'Resto Seafood Bekasi Raya', 'phone' => '081290001009', 'type' => self::CUSTOMER_WHOLESALE],
        ];
        foreach ($customers as $c) {
            $this->customers[$c['id']] = ['name' => $c['name'], 'type' => $c['type']];
        }
        $customers = array_map(fn ($c) => $c + ['user_id' => $this->userId['kasir']], $customers);

        // [nama, kategori, supplier, lokasi, modal, harga, harga grosir, min qty grosir,
        //  min stok, satuan, lead time (hari), popularitas]
        $catalog = [
            ['Nugget Ayam Original 500g', 1, 4, 2, 28000, 35000, 31500, 10, 30, 'pack', 2, 10],
            ['Nugget Ayam Crispy 1kg', 1, 4, 1, 52000, 65000, 58000, 6, 20, 'pack', 2, 7],
            ['Chicken Wings Frozen 1kg', 1, 4, 1, 40000, 50000, 45000, 6, 15, 'pack', 3, 5],
            ['Chicken Katsu 500g', 1, 4, 2, 32000, 40000, 36000, 10, 20, 'pack', 2, 6],
            ['Risoles Ragout Ayam isi 10', 1, 4, 2, 20000, 27000, 24000, 10, 20, 'pack', 2, 5],
            ['Sosis Sapi 500g', 2, 1, 2, 30000, 38000, 34000, 10, 30, 'pack', 2, 9],
            ['Sosis Ayam Jumbo 1kg', 2, 1, 1, 45000, 57000, 51000, 6, 20, 'pack', 2, 6],
            ['Bakso Sapi Halus 500g', 2, 1, 2, 30000, 38000, 34000, 10, 25, 'pack', 2, 8],
            ['Bakso Urat 500g', 2, 1, 2, 33000, 42000, 37500, 10, 20, 'pack', 2, 6],
            ['Bakso Ikan 500g', 2, 3, 2, 27000, 35000, 31500, 10, 20, 'pack', 2, 5],
            ['Dimsum Ayam isi 10', 3, 3, 2, 22000, 30000, 27000, 10, 30, 'pack', 2, 9],
            ['Dimsum Udang isi 10', 3, 3, 2, 28000, 38000, 34000, 10, 20, 'pack', 2, 6],
            ['Siomay Ikan isi 20', 3, 3, 2, 30000, 40000, 36000, 8, 20, 'pack', 2, 6],
            ['Otak-otak Ikan isi 10', 3, 3, 2, 18000, 25000, 22500, 10, 20, 'pack', 2, 4],
            ['Udang Kupas 500g', 3, 3, 1, 55000, 70000, 63000, 6, 10, 'pack', 3, 4],
            ['Tempura Udang isi 10', 3, 3, 2, 38000, 50000, 45000, 6, 12, 'pack', 3, 3],
            ['Kentang Goreng Shoestring 1kg', 4, 5, 1, 28000, 36000, 32000, 10, 25, 'pack', 3, 8],
            ['Kentang Wedges 1kg', 4, 5, 1, 30000, 39000, 35000, 10, 15, 'pack', 3, 4],
            ['Cireng Isi Ayam Pedas isi 20', 4, 2, 2, 20000, 28000, 25000, 10, 20, 'pack', 2, 6],
            ['Cheese Roll isi 10', 4, 2, 2, 24000, 32000, 29000, 10, 15, 'pack', 2, 4],
            ['Daging Sapi Slice 500g', 5, 1, 1, 60000, 75000, 68000, 6, 12, 'pack', 3, 4],
            ['Fillet Dada Ayam 1kg', 5, 4, 1, 38000, 48000, 43000, 6, 15, 'pack', 2, 5],
        ];

        $products = [];
        foreach ($catalog as $i => [$name, $cat, $sup, $loc, $cost, $price, $wh, $whMin, $minStock, $unit, $lead, $pop]) {
            $id = $i + 1;
            $products[] = [
                'id' => $id,
                'category_id' => $cat,
                'user_id' => $this->userId['admin'],
                'updated_by' => $this->userId['admin'],
                'name' => $name,
                'slug' => Str::slug($name),
                'sku' => sprintf('FRZ-%d%03d', $cat, $id),
                'description' => "{$name}. Simpan di freezer bersuhu -18°C atau lebih dingin.",
                'price' => $price,
                'wholesale_price' => $wh,
                'wholesale_min_qty' => $whMin,
                'cost' => $cost,
                'min_stock' => $minStock,
                'unit' => $unit,
                'image' => null,
                'is_active' => true,
                'lead_time' => $lead,
            ];

            $this->meta[$id] = [
                'name' => $name, 'sup' => $sup, 'loc' => $loc, 'cost' => $cost, 'price' => $price,
                'wh' => $wh, 'wh_min' => $whMin, 'min_stock' => $minStock, 'unit' => $unit, 'lead' => $lead,
            ];
            $this->stock[$id] = 0;
            $this->inbound[$id] = 0;
            $this->lastRequest[$id] = null;
            for ($w = 0; $w < $pop; $w++) {
                $this->weightedProducts[] = $id;
            }
        }

        // ============ SIMULASI 6 BULAN ============
        $this->simulate();

        // ============ STOCKS (turunan dari ledger) ============
        $stocks = [];
        foreach ($this->meta as $pid => $m) {
            $stocks[] = [
                'id' => $pid,
                'product_id' => $pid,
                'location_id' => $m['loc'],
                'user_id' => $this->userId['gudang'],
                'quantity' => $this->stock[$pid],
            ];
        }

        // ============ USER PERMISSIONS ============
        // v = view, c = create, e = edit, d = delete. Semua key harus ada di $permissionDefs.
        $roleMatrix = [
            'admin' => array_fill_keys(array_keys($permissionId), 'vced'),
            'kasir' => [
                'dashboard' => 'v', 'stok' => 'v', 'pelanggan' => 'vce', 'pembukuan' => 'vc',
            ],
            'gudang' => [
                'dashboard' => 'v', 'stok' => 'vce', 'lokasi' => 'v', 'purchase_request' => 'vce',
            ],
            'purchasing' => [
                'dashboard' => 'v', 'stok' => 'v', 'supplier' => 'vce', 'pembukuan' => 'v',
                'purchase_request' => 'vced', 'ringkasan' => 'v',
            ],
        ];
        $userPermissions = [];
        $upId = 0;
        foreach ($roleMatrix as $role => $perms) {
            foreach ($perms as $key => $flags) {
                $userPermissions[] = $this->permissionRow(++$upId, $this->userId[$role], $permissionId[$key], $flags);
            }
        }

        // Keep this order so referenced parent records exist first.
        // users & permissions sudah di-insert di atas (ID asli dari database).
        $recordsByTable = [
            'categories' => $categories,
            'locations' => $locations,
            'suppliers' => $suppliers,
            'customers' => $customers,
            'products' => $products,
            'ledgers' => $this->ledgers,
            'stocks' => $stocks,
            'purchase_requests' => $this->purchaseRequests,
            'user_permissions' => $userPermissions,
        ];

        // Geser ID eksplisit (dan relasinya) supaya tidak bentrok dengan data yang sudah ada.
        $offsets = [];
        foreach (array_keys($recordsByTable) as $table) {
            $offsets[$table] = (int) DB::table($table)->max('id');
        }
        $foreignTables = [
            'category_id' => 'categories',
            'location_id' => 'locations',
            'supplier_id' => 'suppliers',
            'customer_id' => 'customers',
            'product_id' => 'products',
            'ledger_id' => 'ledgers',
        ];

        foreach ($recordsByTable as $table => $records) {
            if ($records === []) {
                continue;
            }

            $records = array_map(function (array $record) use ($table, $offsets, $foreignTables): array {
                $record['id'] += $offsets[$table];
                foreach ($foreignTables as $column => $parent) {
                    if (array_key_exists($column, $record) && $record[$column] !== null) {
                        $record[$column] += $offsets[$parent];
                    }
                }

                return $record;
            }, $records);

            // Di-chunk supaya tidak melewati batas placeholder MySQL (65535).
            foreach (array_chunk($records, 200) as $chunk) {
                $chunk = array_map(
                    static fn (array $record): array => $record + [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $chunk
                );

                DB::table($table)->insert($chunk);
            }
        }
    }

    // ==================================================================
    // SIMULASI
    // ==================================================================

    private function simulate(): void
    {
        $start = $this->today->copy()->subMonths(6);

        // Stok awal: tiap produk dibeli lewat PR yang diterima tepat di hari pertama.
        foreach ($this->meta as $pid => $m) {
            $requestDate = $start->copy()->subDays(2 + $m['lead']);
            $this->requestRestock($pid, $requestDate, $m['min_stock'] * 4, true);
        }

        for ($d = $start->copy(); $d->lte($this->today); $d->addDay()) {
            $this->processReceipts($d);
            $this->generateSales($d);
            $this->generateOperationalExpenses($d);
            $this->checkReorder($d);
        }
    }

    /** Barang dari PR yang jatuh tempo diterima hari ini -> buat ledger pembelian + tambah stok. */
    private function processReceipts(Carbon $d): void
    {
        foreach ($this->receipts[$d->toDateString()] ?? [] as $i) {
            $pr = $this->purchaseRequests[$i];
            $m = $this->meta[$pr['product_id']];

            $recent = $d->gte($this->today->copy()->subDays(14));
            $unpaid = $recent && mt_rand(1, 100) <= 50;

            $ledgerId = $this->addLedger([
                'type' => self::EXPENSE,
                'title' => 'Pembelian ' . $m['name'],
                'amount' => $pr['total_cost'],
                'payment_status' => $unpaid ? self::UNPAID : self::PAID,
                'due_date' => $unpaid ? $d->copy()->addDays(14)->toDateString() : null,
                'description' => "Restock {$pr['received_qty']} {$m['unit']} dari {$this->supplierNames[$m['sup']]} (tiket {$pr['ticket_number']})",
                'date' => $d->toDateString(),
                'reference' => $pr['invoice_number'],
                'quantity' => $pr['received_qty'],
                'stock_movement' => $this->movement($pr['received_qty']),
                'product_id' => $pr['product_id'],
                'supplier_id' => $m['sup'],
                'location_id' => $pr['location_id'],
                'user_id' => $pr['received_by'],
                'updated_by' => $pr['received_by'],
                'created_at' => $pr['received_at'],
            ]);

            $this->purchaseRequests[$i]['ledger_id'] = $ledgerId;
            $this->stock[$pr['product_id']] += $pr['received_qty'];
            $this->inbound[$pr['product_id']] -= $pr['requested_qty'];
        }
    }

    /** Penjualan harian -> ledger income, stok berkurang. */
    private function generateSales(Carbon $d): void
    {
        $count = $d->isWeekend() ? mt_rand(10, 18) : mt_rand(6, 12);

        for ($n = 0; $n < $count; $n++) {
            $pid = $this->weightedProducts[mt_rand(0, count($this->weightedProducts) - 1)];
            $m = $this->meta[$pid];

            if ($this->stock[$pid] <= 0) {
                continue; // stok habis, penjualan batal
            }

            $roll = mt_rand(1, 100);
            $customerId = $roll <= 55 ? 1 : ($roll <= 75 ? mt_rand(2, 4) : mt_rand(5, 9));
            $customer = $this->customers[$customerId];
            $wholesale = $customer['type'] === self::CUSTOMER_WHOLESALE;

            $qty = $wholesale ? mt_rand($m['wh_min'], $m['wh_min'] * 3) : mt_rand(1, 5);
            $qty = min($qty, $this->stock[$pid]);

            $unitPrice = ($wholesale && $qty >= $m['wh_min']) ? $m['wh'] : $m['price'];

            // Pelanggan grosir yang belanja belakangan ini kadang masih bayar tempo.
            $unpaid = $wholesale
                && $d->gte($this->today->copy()->subDays(21))
                && mt_rand(1, 100) <= 40;

            $key = $d->toDateString();
            $this->counters['inv'][$key] = ($this->counters['inv'][$key] ?? 0) + 1;
            $reference = 'INV-' . $d->format('Ymd') . '-' . str_pad((string) $this->counters['inv'][$key], 4, '0', STR_PAD_LEFT);

            $userId = mt_rand(1, 100) <= 90 ? $this->userId['kasir'] : $this->userId['admin'];

            $this->addLedger([
                'type' => self::INCOME,
                'title' => 'Penjualan ' . $m['name'],
                'amount' => $qty * $unitPrice,
                'payment_status' => $unpaid ? self::UNPAID : self::PAID,
                'due_date' => $unpaid ? $d->copy()->addDays(14)->toDateString() : null,
                'description' => "Penjualan {$qty} {$m['unit']} {$m['name']} kepada {$customer['name']}"
                    . ($wholesale && $qty >= $m['wh_min'] ? ' (harga grosir)' : ''),
                'date' => $key,
                'reference' => $reference,
                'quantity' => $qty,
                'stock_movement' => $this->movement(-$qty),
                'product_id' => $pid,
                'customer_id' => $customerId,
                'location_id' => $m['loc'],
                'user_id' => $userId,
                'updated_by' => $userId,
                'created_at' => $d->copy()->setTime(mt_rand(8, 20), mt_rand(0, 59), mt_rand(0, 59))->toDateTimeString(),
            ]);

            $this->stock[$pid] -= $qty;
        }
    }

    /** Biaya operasional rutin (tanpa produk, tidak mengubah stok). */
    private function generateOperationalExpenses(Carbon $d): void
    {
        $items = [];

        if ($d->day === 1) {
            $items[] = ['SWT', 'Sewa Tempat', 4500000];
        }
        if ($d->day === 5) {
            $items[] = ['LST', 'Listrik & Daya Freezer', mt_rand(28, 36) * 100000];
        }
        if ($d->day === 10) {
            $items[] = ['NET', 'Internet & Telepon', 450000];
        }
        if ($d->day === 25) {
            $items[] = ['GJI', 'Gaji Karyawan', 9000000];
        }
        if ($d->isMonday()) {
            $items[] = ['ONG', 'Bensin & Ongkos Kirim', mt_rand(35, 60) * 10000];
        }

        foreach ($items as [$code, $title, $amount]) {
            $this->addLedger([
                'type' => self::EXPENSE,
                'title' => $title,
                'amount' => $amount,
                'payment_status' => self::PAID,
                'description' => $title . ' periode ' . $d->translatedFormat('F Y'),
                'date' => $d->toDateString(),
                'reference' => 'OPS-' . $code . '-' . $d->format('Ymd'),
                'quantity' => 1,
                'stock_movement' => $this->movement(0),
                'location_id' => 1,
                'user_id' => $this->userId['admin'],
                'updated_by' => $this->userId['admin'],
                'created_at' => $d->copy()->setTime(mt_rand(9, 16), mt_rand(0, 59))->toDateTimeString(),
            ]);
        }
    }

    /** Stok + barang yang sedang dipesan <= 2x min_stock -> ajukan purchase request baru. */
    private function checkReorder(Carbon $d): void
    {
        foreach ($this->meta as $pid => $m) {
            if ($this->stock[$pid] + $this->inbound[$pid] > $m['min_stock'] * 2) {
                continue;
            }

            $last = $this->lastRequest[$pid];
            if ($last !== null && $last->copy()->addDays(4)->gt($d)) {
                continue;
            }

            $qty = (int) (ceil($m['min_stock'] * 4 / 5) * 5) + 5 * mt_rand(0, 3);
            $this->requestRestock($pid, $d->copy(), $qty);
        }
    }

    /**
     * Buat satu purchase request lengkap dengan alur statusnya.
     * Status ditentukan dari posisi tanggal tiap tahap terhadap HARI INI,
     * jadi PR terbaru wajar masih pending/approved/purchased.
     */
    private function requestRestock(int $pid, Carbon $requestDate, int $qty, bool $opening = false): void
    {
        $m = $this->meta[$pid];
        $cut = $this->today->copy()->endOfDay();

        $requestedAt = $requestDate->copy()->setTime(mt_rand(8, 17), mt_rand(0, 59));
        $reviewedAt = $requestDate->copy()->addDay()->setTime(mt_rand(9, 15), mt_rand(0, 59));
        $purchasedAt = $reviewedAt->copy()->addDay()->setTime(mt_rand(8, 14), mt_rand(0, 59));
        $receivedAt = $purchasedAt->copy()->addDays($m['lead'])->setTime(mt_rand(8, 16), mt_rand(0, 59));

        $rejected = !$opening && $reviewedAt->lte($cut) && mt_rand(1, 100) <= 6;
        $unitCost = (int) round($m['cost'] * mt_rand(97, 105) / 100, -2);

        $id = ++$this->prSeq;
        $key = $requestDate->toDateString();
        $this->counters['pr'][$key] = ($this->counters['pr'][$key] ?? 0) + 1;

        $note = $opening
            ? 'Stok awal periode pembukaan'
            : "Stok menipis, sisa {$this->stock[$pid]} {$m['unit']} (batas aman {$m['min_stock']})";

        $row = [
            'id' => $id,
            'ticket_number' => 'PR-' . $requestDate->format('Ymd') . '-' . str_pad((string) $this->counters['pr'][$key], 3, '0', STR_PAD_LEFT),
            'product_id' => $pid,
            'location_id' => $m['loc'],
            'supplier_id' => $m['sup'],
            'ledger_id' => null,
            'requested_by' => $this->userId['gudang'],
            'reviewed_by' => null,
            'purchased_by' => null,
            'received_by' => null,
            'requested_qty' => $qty,
            'received_qty' => 0,
            'status' => self::PR_PENDING,
            'request_notes' => $note,
            'rejection_reason' => null,
            'reviewed_at' => null,
            'invoice_number' => null,
            'proof_image' => null,
            'total_cost' => 0,
            'purchased_at' => null,
            'received_at' => null,
            'created_at' => $requestedAt->toDateTimeString(),
        ];

        $index = count($this->purchaseRequests);
        $this->unitCost[$index] = $unitCost;
        $this->lastRequest[$pid] = $requestDate->copy();

        if ($rejected) {
            $row['status'] = self::PR_REJECTED;
            $row['reviewed_by'] = $this->userId['admin'];
            $row['reviewed_at'] = $reviewedAt->toDateTimeString();
            $row['rejection_reason'] = ['Anggaran bulan ini terbatas', 'Stok di lokasi lain masih cukup', 'Harga supplier terlalu tinggi'][mt_rand(0, 2)];
            $this->purchaseRequests[] = $row;

            return;
        }

        $this->inbound[$pid] += $qty;

        if ($reviewedAt->lte($cut)) {
            $row['status'] = self::PR_APPROVED;
            $row['reviewed_by'] = $this->userId['admin'];
            $row['reviewed_at'] = $reviewedAt->toDateTimeString();
        }

        if ($purchasedAt->lte($cut)) {
            $row['status'] = self::PR_PURCHASED;
            $row['purchased_by'] = $this->userId['purchasing'];
            $row['purchased_at'] = $purchasedAt->toDateTimeString();
            $row['invoice_number'] = sprintf('SUP%d-%s-%03d', $m['sup'], $purchasedAt->format('ymd'), $id);
            $row['total_cost'] = $qty * $unitCost;
        }

        if ($receivedAt->lte($cut)) {
            // Sesekali barang datang kurang dari yang dipesan.
            $receivedQty = mt_rand(1, 100) <= 94 ? $qty : $qty - mt_rand(1, max(1, intdiv($qty, 10)));

            $row['status'] = self::PR_COMPLETED;
            $row['received_by'] = $this->userId['gudang'];
            $row['received_at'] = $receivedAt->toDateTimeString();
            $row['received_qty'] = $receivedQty;
            $row['total_cost'] = $receivedQty * $unitCost;

            $this->receipts[$receivedAt->toDateString()][] = $index;
        }

        $this->purchaseRequests[] = $row;
    }

    // ==================================================================
    // HELPER
    // ==================================================================

    private function addLedger(array $attrs): int
    {
        $id = ++$this->ledgerSeq;

        // Semua baris ledger harus punya set kolom yang sama (syarat bulk insert).
        $this->ledgers[] = array_merge([
            'id' => $id,
            'type' => null,
            'title' => null,
            'slug' => null,
            'amount' => 0,
            'payment_status' => self::PAID,
            'due_date' => null,
            'description' => null,
            'date' => null,
            'reference' => null,
            'quantity' => 1,
            'stock_movement' => $this->movement(0),
            'proof_image' => null,
            'product_id' => null,
            'supplier_id' => null,
            'location_id' => null,
            'customer_id' => null,
            'user_id' => $this->userId['admin'],
            'updated_by' => $this->userId['admin'],
            'created_at' => null,
        ], $attrs, [
            'id' => $id,
            'slug' => Str::slug(($attrs['title'] ?? '') . ' ' . ($attrs['reference'] ?? $id)),
        ]);

        return $id;
    }

    /**
     * Nilai kolom ledgers.stock_movement: enum('in', 'out') nullable.
     * Positif => 'in', negatif => 'out', nol => null (mis. biaya operasional).
     */
    private function movement(int $signedQty): ?string
    {
        return match (true) {
            $signedQty > 0 => 'in',
            $signedQty < 0 => 'out',
            default => null,
        };
    }

    private function permissionRow(int $id, int $userId, int $permissionId, string $flags): array
    {
        return [
            'id' => $id,
            'user_id' => $userId,
            'permission_id' => $permissionId,
            'can_view' => str_contains($flags, 'v'),
            'can_create' => str_contains($flags, 'c'),
            'can_edit' => str_contains($flags, 'e'),
            'can_delete' => str_contains($flags, 'd'),
        ];
    }
}
