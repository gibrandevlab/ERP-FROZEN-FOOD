<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function fieldLabel(string $field): string
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'is_admin' => 'Admin',
            'category_id' => 'Kategori (ID)',
            'slug' => 'Slug',
            'sku' => 'SKU',
            'description' => 'Deskripsi',
            'price' => 'Harga jual',
            'wholesale_price' => 'Harga grosir',
            'wholesale_min_qty' => 'Minimal grosir',
            'cost' => 'HPP',
            'unit' => 'Satuan',
            'image' => 'Gambar produk',
            'is_active' => 'Status aktif',
            'lead_time' => 'Lead time',
            'min_stock' => 'Stok minimum',
            'phone' => 'Telepon',
            'address' => 'Alamat',
            'type' => 'Jenis',
            'product_id' => 'Produk (ID)',
            'location_id' => 'Lokasi (ID)',
            'supplier_id' => 'Supplier (ID)',
            'customer_id' => 'Pelanggan (ID)',
            'quantity' => 'Jumlah',
            'unit_price' => 'Harga per unit',
            'title' => 'Judul',
            'amount' => 'Nominal',
            'payment_status' => 'Status pembayaran',
            'due_date' => 'Jatuh tempo',
            'date' => 'Tanggal transaksi',
            'reference' => 'Nomor invoice',
            'stock_movement' => 'Arah mutasi',
            'ticket_number' => 'Nomor permintaan',
            'requested_qty' => 'Jumlah diminta',
            'received_qty' => 'Jumlah diterima',
            'status' => 'Status',
            'request_notes' => 'Catatan permintaan',
            'rejection_reason' => 'Alasan penolakan',
            'invoice_number' => 'Nomor invoice',
            'total_cost' => 'Total biaya',
            'proof_image' => 'Bukti transaksi',
            'requested_by' => 'Pemohon (ID)',
            'reviewed_by' => 'Peninjau (ID)',
            'purchased_by' => 'Pembeli (ID)',
            'received_by' => 'Penerima (ID)',
            'permission_id' => 'Hak akses (ID)',
            'can_view' => 'Hak melihat',
            'can_create' => 'Hak membuat',
            'can_edit' => 'Hak mengedit',
            'can_delete' => 'Hak menghapus',
        ][$field] ?? str($field)->replace('_', ' ')->title()->toString();
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Dibuat',
            'updated' => 'Diedit',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            default => str($this->event)->title()->toString(),
        };
    }

    public function subjectTypeLabel(): string
    {
        return [
            Product::class => 'Produk',
            Category::class => 'Kategori',
            Location::class => 'Lokasi',
            Customer::class => 'Pelanggan',
            Supplier::class => 'Supplier',
            Ledger::class => 'Transaksi',
            LedgerItem::class => 'Detail transaksi',
            PurchaseRequest::class => 'Purchase Request',
            User::class => 'Pengguna',
            UserPermission::class => 'Hak akses pengguna',
        ][$this->subject_type] ?? class_basename($this->subject_type);
    }
}
