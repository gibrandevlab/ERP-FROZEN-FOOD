<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    // ─── Mass Assignment ─────────────────────────────────────

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'wholesale_price',
        'wholesale_min_qty',
        'cost',
        'unit',
        'image',
        'is_active',
    ];

    // ─── Casting ──────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'price'             => 'decimal:2',
            'wholesale_price'   => 'decimal:2',
            'cost'              => 'decimal:2',
            'wholesale_min_qty' => 'integer',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Auto Slug ───────────────────────────────────────────

    /**
     * Buat slug otomatis dari nama produk saat pertama dibuat.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // ─── Relasi ──────────────────────────────────────────────

    /**
     * Kategori produk ini.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Semua catatan pembukuan yang berhubungan dengan produk ini.
     */
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    /**
     * Stok produk di berbagai lokasi.
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Hitung total stok dari semua lokasi.
     */
    public function totalStock()
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Hitung stok di lokasi tertentu.
     */
    public function stockAt($locationId)
    {
        return $this->stocks()->where('location_id', $locationId)->sum('quantity');
    }

    /**
     * Hitung margin keuntungan dalam persen.
     * Contoh: cost=5000, price=8000 → margin = 60%
     */
    public function marginPercent(): float
    {
        if ($this->cost <= 0) {
            return 0;
        }

        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }
}
