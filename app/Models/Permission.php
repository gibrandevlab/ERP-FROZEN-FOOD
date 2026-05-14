<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    // ─── Mass Assignment ─────────────────────────────────────

    protected $fillable = [
        'key',
        'label',
        'category',
        'description',
    ];

    // ─── Relasi ──────────────────────────────────────────────

    /**
     * Semua entri hak akses yang menggunakan permission ini.
     */
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }
}
