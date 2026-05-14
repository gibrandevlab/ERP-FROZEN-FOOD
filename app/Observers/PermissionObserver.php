<?php

namespace App\Observers;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

/**
 * Observer untuk model Permission.
 *
 * Setiap kali admin mengubah/menambah/menghapus data di tabel permissions,
 * cache 'app.permissions' otomatis di-clear agar gate yang terdaftar
 * di AppServiceProvider kembali fresh di request berikutnya.
 *
 * Tanpa observer ini, perubahan permission baru terasa SETELAH 24 jam (saat cache expired).
 */
class PermissionObserver
{
    /**
     * Clear cache saat permission baru dibuat.
     */
    public function created(Permission $permission): void
    {
        Cache::forget('app.permissions');
    }

    /**
     * Clear cache saat permission diperbarui (misal: ganti label/key).
     */
    public function updated(Permission $permission): void
    {
        Cache::forget('app.permissions');
    }

    /**
     * Clear cache saat permission dihapus.
     */
    public function deleted(Permission $permission): void
    {
        Cache::forget('app.permissions');
    }
}
