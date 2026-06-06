<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ledger extends Model
{
    use SoftDeletes;

    // ─── Mass Assignment ─────────────────────────────────────

    protected $fillable = [
        'type',
        'title',
        'slug',
        'amount',
        'payment_status',
        'due_date',
        'description',
        'date',
        'reference',
        'product_id',
        'location_id',
        'quantity',
        'stock_movement',
        'proof_image',
        'customer_id',
        'supplier_id',
        'user_id',
        'updated_by',
    ];

    // ─── Casting ──────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'date'     => 'date',
            'due_date' => 'date',
        ];
    }

    // ─── Auto Slug ───────────────────────────────────────────

    /**
     * Buat slug unik dari judul + timestamp agar tidak pernah tabrakan.
     * Contoh slug: "penjualan-nugget-minggu-ini-20260421-143022"
     */
    protected static function booted(): void
    {
        static::creating(function (Ledger $ledger) {
            if (empty($ledger->slug)) {
                $ledger->slug = Str::slug($ledger->title . '-' . now()->format('Ymd-His') . '-' . Str::random(4));
            }
        });

        static::created(function (Ledger $ledger) {
            // Update stok jika ledger memiliki info produk, lokasi, dan mutasi stok
            // Meskipun status unpaid (ngutang), stok tetap berpindah secara fisik.
            if ($ledger->product_id && $ledger->location_id && $ledger->quantity && $ledger->stock_movement) {
                $stock = Stock::firstOrCreate([
                    'product_id' => $ledger->product_id,
                    'location_id' => $ledger->location_id,
                ]);

                if ($ledger->stock_movement === 'in') {
                    $stock->increment('quantity', $ledger->quantity);
                } elseif ($ledger->stock_movement === 'out') {
                    $stock->decrement('quantity', $ledger->quantity);
                }
            }
        });

        static::updating(function (Ledger $ledger) {
            if (auth()->check()) {
                $ledger->updated_by = auth()->id();
            }
        });
    }

    // ─── Relasi ──────────────────────────────────────────────

    /**
     * Produk yang terlibat dalam transaksi ini (jika ada).
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Lokasi penyimpanan yang terlibat dalam transaksi ini (jika ada).
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Pelanggan yang terlibat dalam transaksi ini (jika ada).
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Supplier yang menyuplai barang pada transaksi ini (jika ada).
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Pengguna yang membuat catatan ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pengguna yang terakhir memperbarui catatan ini.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Scope ───────────────────────────────────────────────

    /**
     * Filter hanya transaksi pemasukan yang sudah lunas (Income).
     * Contoh: Ledger::income()->sum('amount')
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income')->where('payment_status', 'paid');
    }

    /**
     * Filter hanya transaksi pengeluaran yang sudah lunas (Expense).
     * Contoh: Ledger::expense()->sum('amount')
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense')->where('payment_status', 'paid');
    }

    /**
     * Filter transaksi Piutang (Pelanggan utang ke kita / pemasukan tertunda).
     */
    public function scopeReceivables($query)
    {
        return $query->where('type', 'income')->where('payment_status', 'unpaid');
    }

    /**
     * Filter transaksi Utang (Kita ngutang ke supplier / pengeluaran tertunda).
     */
    public function scopePayables($query)
    {
        return $query->where('type', 'expense')->where('payment_status', 'unpaid');
    }
}
