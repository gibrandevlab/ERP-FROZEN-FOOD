<?php

namespace App\Providers;

use App\Models\Permission;
use App\Observers\PermissionObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Daftarkan service ke container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap service aplikasi.
     */
    public function boot(): void
    {
        // Daftarkan observer — otomatis clear cache saat data permission berubah
        Permission::observe(PermissionObserver::class);

        $this->daftarkanGates();
    }

    /**
     * Daftarkan semua Laravel Gate secara dinamis berdasarkan tabel permissions.
     *
     * ─── Cara Kerja ─────────────────────────────────────────
     *
     * Untuk setiap fitur di tabel permissions, sistem akan mendaftarkan
     * 4 gate otomatis. Contoh untuk fitur 'products':
     *
     *   Gate::define('view-products',   fn($user) => $user->canDo('products', 'view'))
     *   Gate::define('create-products', fn($user) => $user->canDo('products', 'create'))
     *   Gate::define('edit-products',   fn($user) => $user->canDo('products', 'edit'))
     *   Gate::define('delete-products', fn($user) => $user->canDo('products', 'delete'))
     *
     * ─── Pemakaian di Blade ──────────────────────────────────
     *
     *   @can('view-products')
     *       <p>Kamu boleh lihat daftar produk</p>
     *   @endcan
     *
     * ─── Pemakaian di Livewire Volt ─────────────────────────
     *
     *   $this->authorize('create-products'); // Lempar 403 jika tidak punya akses
     *   Gate::allows('edit-products');       // Return true/false
     *
     * ─── Akses Admin ─────────────────────────────────────────
     *
     * User dengan is_admin = true akan selalu lolos semua gate
     * karena gate::before() dipanggil sebelum pengecekan individual.
     */
    private function daftarkanGates(): void
    {
        // Admin melewati SEMUA gate tanpa perlu dicek satu per satu
        Gate::before(function ($user) {
            if ($user->is_admin) {
                return true;
            }
        });

        // Jangan query DB saat menjalankan perintah artisan (migrate, seed, dll.)
        // agar tidak error ketika tabel belum ada
        if (app()->runningInConsole()) {
            return;
        }

        try {
            // Load semua permission dari cache (default: 24 jam)
            // Cache otomatis di-clear oleh PermissionObserver saat data permission diubah
            $permissions = Cache::remember(
                'app.permissions',
                now()->addDay(),
                fn () => Permission::all()
            );

            foreach ($permissions as $permission) {
                foreach (['view', 'create', 'edit', 'delete'] as $aksi) {
                    Gate::define("{$aksi}-{$permission->key}", function ($user) use ($permission, $aksi) {
                        return $user->canDo($permission->key, $aksi);
                    });
                }
            }
        } catch (\Exception) {
            // Tabel permissions belum ada (contoh: saat pertama kali migrate)
            // Sistem tetap berjalan, semua gate hanya tidak terdaftar
        }
    }
}
