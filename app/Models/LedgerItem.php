<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LedgerItem extends Model
{
    protected $fillable = [
        'ledger_id',
        'product_id',
        'location_id',
        'product_name',
        'location_name',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(fn (LedgerItem $item) => $item->applyStockMovement(1));
        static::updated(function (LedgerItem $item): void {
            $movement = $item->getAttributes();
            $ledger = $item->ledger()->withTrashed()->first();
            if (! $ledger) {
                return;
            }

            if ($item->getOriginal('product_id') == $movement['product_id'] &&
                $item->getOriginal('location_id') == $movement['location_id']) {
                $direction = $ledger->type === 'expense' ? 1 : -1;
                $item->adjustStock($movement, ((int) $movement['quantity'] - (int) $item->getOriginal('quantity')) * $direction, $ledger);
                return;
            }

            $item->changeStock($item->getOriginal(), -1);
            $item->applyStockMovement(1);
        });
        static::deleted(fn (LedgerItem $item) => $item->applyStockMovement(-1));
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function applyStockMovement(int $multiplier): void
    {
        $this->changeStock($this->getAttributes(), $multiplier);
    }

    public function changeStock(array $movement, int $multiplier, ?string $ledgerType = null): void
    {
        if (empty($movement['product_id']) || empty($movement['location_id']) || empty($movement['quantity'])) {
            return;
        }

        $ledger = $this->ledger()->withTrashed()->first();
        if (! $ledger || ($ledger->trashed() && $ledgerType === null)) {
            return;
        }
        $ledgerType ??= $ledger->type;

        $delta = (int) $movement['quantity'] * ($ledgerType === 'expense' ? 1 : -1) * $multiplier;
        $this->adjustStock($movement, $delta, $ledger);
    }

    protected function adjustStock(array $movement, int $delta, Ledger $ledger): void
    {
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($movement, $delta, $ledger): void {
            $stock = Stock::where('product_id', $movement['product_id'])
                ->where('location_id', $movement['location_id'])
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                if ($delta < 0) {
                    throw new \RuntimeException('Stok tidak mencukupi untuk mutasi ini.');
                }

                $stock = Stock::create([
                    'product_id' => $movement['product_id'],
                    'location_id' => $movement['location_id'],
                    'quantity' => 0,
                    'user_id' => $ledger->user_id,
                ]);
            }

            if ($delta < 0 && $stock->quantity + $delta < 0) {
                throw new \RuntimeException('Stok tidak mencukupi untuk mutasi ini.');
            }

            $stock->increment('quantity', $delta);
        });
    }
}
