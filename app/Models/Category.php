<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    // ─── Mass Assignment ─────────────────────────────────────

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // ─── Auto Slug ───────────────────────────────────────────

    /**
     * Buat slug otomatis dari nama saat data pertama kali dibuat.
     * Kamu tidak perlu mengisi 'slug' secara manual.
     */
    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ─── Relasi ──────────────────────────────────────────────

    /**
     * Semua produk dalam kategori ini.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
