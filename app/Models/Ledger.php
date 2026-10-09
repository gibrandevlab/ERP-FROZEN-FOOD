<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
                $ledger->slug = Str::slug($ledger->title . '-' . now()->format('Ymd-His') . '-' . Str::random(12));
            }
        });

        static::created(fn (Ledger $ledger) => $ledger->applyStockMovement());

        static::updated(function (Ledger $ledger) {
            $ledger->reverseStockMovement($ledger->getOriginal());
            $ledger->applyStockMovement();
        });

        static::deleted(fn (Ledger $ledger) => $ledger->reverseStockMovement($ledger->getAttributes()));

        static::restored(fn (Ledger $ledger) => $ledger->applyStockMovement());

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

    protected function applyStockMovement(): void
    {
        $this->changeStock($this->getAttributes(), 1);
    }

    protected function reverseStockMovement(array $movement): void
    {
        $this->changeStock($movement, -1);
    }

    protected function changeStock(array $movement, int $multiplier): void
    {
        if (empty($movement['product_id']) || empty($movement['location_id']) ||
            empty($movement['quantity']) || empty($movement['stock_movement'])) {
            return;
        }

        DB::transaction(function () use ($movement, $multiplier) {
            $stock = Stock::where('product_id', $movement['product_id'])
                ->where('location_id', $movement['location_id'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = Stock::create([
                    'product_id' => $movement['product_id'],
                    'location_id' => $movement['location_id'],
                    'quantity' => 0,
                ]);
            }

            $delta = (int) $movement['quantity'] *
                ($movement['stock_movement'] === 'in' ? 1 : -1) * $multiplier;

            if ($delta < 0 && $stock->quantity + $delta < 0) {
                throw new \RuntimeException('Stok tidak mencukupi untuk mutasi ini.');
            }

            $stock->increment('quantity', $delta);
        });
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
