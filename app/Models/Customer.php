<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'type',
    ];

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    public function totalItemsBought()
    {
        return $this->ledgers()->where('type', 'income')->sum('quantity');
    }

    public function totalProfit()
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
        return $profit;
    }
}
