<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'type',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    public function salesLedgers()
    {
        return $this->hasMany(Ledger::class)->where('type', 'income');
    }

    public function isSeller(): bool
    {
        return $this->type === 'seller';
    }

    public function totalTransactions(): int
    {
        return (int) $this->ledgers()->where('type', 'income')->count();
    }

    public function totalItemsBought(): int
    {
        return (int) $this->ledgers()->where('type', 'income')->sum('quantity');
    }

    public function totalSpent(): float
    {
        return (float) $this->ledgers()->where('type', 'income')->sum('amount');
    }

    public function unpaidReceivables(): float
    {
        return (float) $this->ledgers()->where('type', 'income')->where('payment_status', 'unpaid')->sum('amount');
    }

    public function totalProfit(): float
    {
        $ledgers = $this->ledgers()->where('type', 'income')->with('product')->get();
        $profit = 0;
        foreach ($ledgers as $ledger) {
            if ($ledger->product) {
                $profit += ($ledger->amount - ($ledger->product->cost * $ledger->quantity));
            } else {
                // Jika tidak ada produk (jasa), asumsikan profit 100% dari amount
                $profit += $ledger->amount;
            }
        }
        return (float) $profit;
    }

    public function scopeSellers($query)
    {
        return $query->where('type', 'seller');
    }

    public function scopeNonSellers($query)
    {
        return $query->where('type', 'non_seller');
    }
}
