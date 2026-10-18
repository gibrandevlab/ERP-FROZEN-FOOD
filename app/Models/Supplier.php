<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function purchaseLedgers()
    {
        return $this->hasMany(Ledger::class)->where('type', 'expense');
    }

    /**
     * Produk yang disuplai oleh supplier ini (melalui transaksi pembukuan/ledger).
     */
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Ledger::class,
            'supplier_id', // Foreign key on ledgers table
            'id',          // Foreign key on products table
            'id',          // Local key on suppliers table
            'product_id'   // Local key on ledgers table
        )->distinct();
    }

    public function totalTransactions(): int
    {
        return (int) $this->ledgers()->where('type', 'expense')->count();
    }

    public function totalItemsSupplied(): int
    {
        return (int) $this->ledgers()->where('type', 'expense')->sum('quantity');
    }

    public function totalPurchases(): float
    {
        return (float) $this->ledgers()->where('type', 'expense')->sum('amount');
    }

    public function unpaidPayables(): float
    {
        return (float) $this->ledgers()->where('type', 'expense')->where('payment_status', 'unpaid')->sum('amount');
    }
}
