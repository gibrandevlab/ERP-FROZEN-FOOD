<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    // ─── Mass Assignment ─────────────────────────────────────

    protected $fillable = [
        'user_id',
        'permission_id',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
    ];

    // ─── Casting ──────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'can_view'   => 'boolean',
            'can_create' => 'boolean',
            'can_edit'   => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────

    /**
     * User pemilik hak akses ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fitur yang diatur aksesnya.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
