<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Ledger;
use App\Models\Stock;
use App\Models\PurchaseRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PurchaseRequestSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk PurchaseRequest sesuai 4-stage workflow:
     * 1. System Scanning (Low Stock) -> Staff Gudang create PR (pending)
     * 2. Pimpinan Review -> (approved / rejected)
     * 3. Staf Pembeli -> Input Nota & Supplier (purchased)
     * 4. Staff Gudang -> Terima Barang & auto-update Stock & Ledger (completed)
     */
    public function run(): void
    {
        $products  = Product::all();
        $locations = Location::all();
        $suppliers = Supplier::all();
        $users     = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('⚠️ User kosong. Memanggil UserSeeder...');
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        if ($locations->isEmpty()) {
            $location = Location::create([
                'name'        => 'Gudang Utama Frozen',
                'description' => 'Gudang utama penyimpanan frozen food',
            ]);
        } else {
            $location = $locations->first();
        }

        if ($suppliers->isEmpty()) {
            $supplier = Supplier::create([
                'name'  => 'PT Supplier Frozen Indonesia',
                'phone' => '081234567890',
            ]);
        } else {
            $supplier = $suppliers->first();
        }

        if ($products->isEmpty()) {
            $products = collect([
                Product::create(['name' => 'Nugget Ayam Premium 500g', 'price' => 35000, 'cost' => 25000, 'unit' => 'pack', 'min_stock' => 15]),
                Product::create(['name' => 'Sosis Sapi Jumbo 1kg', 'price' => 60000, 'cost' => 45000, 'unit' => 'pack', 'min_stock' => 10]),
                Product::create(['name' => 'Kentang Shoestring 1kg', 'price' => 40000, 'cost' => 28000, 'unit' => 'pack', 'min_stock' => 20]),
                Product::create(['name' => 'Daging Sapi Slice 500g', 'price' => 75000, 'cost' => 55000, 'unit' => 'pack', 'min_stock' => 10]),
                Product::create(['name' => 'Bakso Sapi Urat 500g', 'price' => 45000, 'cost' => 32000, 'unit' => 'pack', 'min_stock' => 15]),
            ]);
        }

        $adminUser = $users->where('is_admin', true)->first() ?? $users->first();
        $staffUser = $users->where('is_admin', false)->first() ?? $users->first();

        // Ambil beberapa produk untuk variasi tiket
        $prod1 = $products->offsetGet(0) ?? $products->first();
        $prod2 = $products->count() > 1 ? $products->offsetGet(1) : $prod1;
        $prod3 = $products->count() > 2 ? $products->offsetGet(2) : $prod1;
        $prod4 = $products->count() > 3 ? $products->offsetGet(3) : $prod1;
        $prod5 = $products->count() > 4 ? $products->offsetGet(4) : $prod1;

        $now = Carbon::now();

        // ─────────────────────────────────────────────────────────────
        // TIKET 1: STATUS = PENDING (Staff Gudang baru buat PR dari sinyal stok habis)
        // ─────────────────────────────────────────────────────────────
        PurchaseRequest::updateOrCreate(
            ['ticket_number' => 'PR-' . $now->format('Ymd') . '-0001'],
            [
                'product_id'     => $prod1->id,
                'location_id'    => $location->id,
                'supplier_id'    => $supplier?->id,
                'requested_qty'  => 50,
                'status'         => 'pending',
                'requested_by'   => $staffUser->id,
                'request_notes'  => 'Stok Nugget tersisa di bawah minimum (scanning sistem). Minta restock segera.',
                'created_at'     => $now->subHours(5),
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // TIKET 2: STATUS = APPROVED (Disetujui Pimpinan, menunggu dibeli Staf Pembeli)
        // ─────────────────────────────────────────────────────────────
        PurchaseRequest::updateOrCreate(
            ['ticket_number' => 'PR-' . $now->format('Ymd') . '-0002'],
            [
                'product_id'     => $prod2->id,
                'location_id'    => $location->id,
                'supplier_id'    => $supplier?->id,
                'requested_qty'  => 30,
                'status'         => 'approved',
                'requested_by'   => $staffUser->id,
                'request_notes'  => 'Sosis Frozen habis karena pesanan grosir.',
                'reviewed_by'    => $adminUser->id,
                'reviewed_at'    => $now->subHours(3),
                'created_at'     => $now->subHours(4),
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // TIKET 3: STATUS = PURCHASED (Sudah dibeli Staf Pembeli, nota & supplier terisi, barang OTW)
        // ─────────────────────────────────────────────────────────────
        $cost3 = ($prod3->cost > 0 ? $prod3->cost : 25000) * 40;
        PurchaseRequest::updateOrCreate(
            ['ticket_number' => 'PR-' . $now->format('Ymd') . '-0003'],
            [
                'product_id'     => $prod3->id,
                'location_id'    => $location->id,
                'supplier_id'    => $supplier?->id,
                'requested_qty'  => 40,
                'status'         => 'purchased',
                'requested_by'   => $staffUser->id,
                'request_notes'  => 'Pengajuan Kentang Frozen untuk stok weekend.',
                'reviewed_by'    => $adminUser->id,
                'reviewed_at'    => $now->subHours(6),
                'purchased_by'   => $adminUser->id,
                'invoice_number' => 'INV-SUPP-' . $now->format('Ymd') . '-88',
                'proof_image'    => 'proofs/nota_sample.jpg',
                'total_cost'     => $cost3,
                'purchased_at'   => $now->subHours(2),
                'created_at'     => $now->subHours(8),
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // TIKET 4: STATUS = COMPLETED (Barang diterima fisik gudang, stok & ledger ter-update)
        // ─────────────────────────────────────────────────────────────
        $cost4 = ($prod4->cost > 0 ? $prod4->cost : 30000) * 20;

        // Buat record Ledger Pengeluaran terkait (dengan stock_movement = in untuk auto-tambah stok)
        $ledger4 = Ledger::create([
            'type'           => 'expense',
            'title'          => 'Pembelian Produk ' . $prod4->name . ' (PR-' . $now->format('Ymd') . '-0004)',
            'slug'           => 'pembelian-' . $prod4->slug . '-' . time(),
            'amount'         => $cost4,
            'date'           => $now->toDateString(),
            'reference'      => 'INV-SUPP-' . $now->format('Ymd') . '-99',
            'product_id'     => $prod4->id,
            'location_id'    => $location->id,
            'quantity'       => 20,
            'stock_movement' => 'in',
            'supplier_id'    => $supplier?->id,
            'user_id'        => $adminUser->id,
            'payment_status' => 'paid',
            'description'    => 'Pengeluaran kas otomatis dari Tiket PR yang telah diselesaikan.',
        ]);

        // Simpan Tiket PR Completed
        PurchaseRequest::updateOrCreate(
            ['ticket_number' => 'PR-' . $now->format('Ymd') . '-0004'],
            [
                'product_id'     => $prod4->id,
                'location_id'    => $location->id,
                'supplier_id'    => $supplier?->id,
                'requested_qty'  => 20,
                'received_qty'   => 20,
                'status'         => 'completed',
                'requested_by'   => $staffUser->id,
                'request_notes'  => 'Daging Sapi Slice restock bulanan.',
                'reviewed_by'    => $adminUser->id,
                'reviewed_at'    => $now->subDays(1),
                'purchased_by'   => $adminUser->id,
                'invoice_number' => 'INV-SUPP-' . $now->format('Ymd') . '-99',
                'proof_image'    => 'proofs/nota_completed.jpg',
                'total_cost'     => $cost4,
                'purchased_at'   => $now->subHours(10),
                'received_by'    => $staffUser->id,
                'received_at'    => $now->subHours(1),
                'ledger_id'      => $ledger4->id,
                'created_at'     => $now->subDays(2),
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // TIKET 5: STATUS = REJECTED (Ditolak Pimpinan beserta alasan)
        // ─────────────────────────────────────────────────────────────
        PurchaseRequest::updateOrCreate(
            ['ticket_number' => 'PR-' . $now->format('Ymd') . '-0005'],
            [
                'product_id'       => $prod5->id,
                'location_id'      => $location->id,
                'supplier_id'      => $supplier?->id,
                'requested_qty'    => 100,
                'status'           => 'rejected',
                'requested_by'     => $staffUser->id,
                'request_notes'    => 'Pengajuan cadangan stok ekstra untuk promo.',
                'reviewed_by'      => $adminUser->id,
                'rejection_reason' => 'Anggaran pengeluaran bulan ini melebihi batas. Pengajuan ditunda bulan depan.',
                'reviewed_at'      => $now->subHours(4),
                'created_at'       => $now->subHours(12),
            ]
        );

        $this->command->info('✅ 5 Tiket PurchaseRequest (5 alur status lengkap) berhasil di-seed.');
    }
}
