<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah RBAC untuk modul Forecast Restock dan Persetujuan Pembelian.
 *
 * Kedua modul memakai permission key berbeda:
 *   - forecast          -> halaman prakiraan restock
 *   - purchase_approval -> halaman persetujuan / penolakan PR
 *
 * Permission purchase_request yang sudah ada tetap dipakai untuk pembuatan PR,
 * pencatatan pembelian, dan penerimaan barang.
 */
return new class extends Migration
{
    private array $permissions = [
        [
            'key'         => 'forecast',
            'label'       => 'Forecast Restock',
            'category'    => 'analisis',
            'description' => 'Prakiraan permintaan, reorder point, dan draft restock otomatis',
        ],
        [
            'key'         => 'purchase_approval',
            'label'       => 'Persetujuan Pembelian',
            'category'    => 'inventori',
            'description' => 'Tinjau, setujui, atau tolak permintaan pembelian',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            $existingId = DB::table('permissions')->where('key', $permission['key'])->value('id');

            if ($existingId) {
                DB::table('permissions')->where('id', $existingId)->update([
                    'label'       => $permission['label'],
                    'category'    => $permission['category'],
                    'description' => $permission['description'],
                    'updated_at'  => $now,
                ]);

                continue;
            }

            DB::table('permissions')->insert($permission + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_column($this->permissions, 'key'))
            ->pluck('id', 'key');

        // Hak akses awal per role (admin otomatis lolos semua Gate).
        $roleGrants = [
            'gudang@rizafrozenfood.test' => [
                'forecast' => ['can_view' => true],
            ],
            'purchasing@rizafrozenfood.test' => [
                'forecast'          => ['can_view' => true],
                'purchase_approval' => ['can_view' => true, 'can_edit' => true],
            ],
        ];

        foreach ($roleGrants as $email => $grants) {
            $userId = DB::table('users')->where('email', $email)->value('id');

            if (! $userId) {
                continue;
            }

            foreach ($grants as $key => $actions) {
                $permissionId = $permissionIds[$key] ?? null;

                if (! $permissionId) {
                    continue;
                }

                DB::table('user_permissions')->updateOrInsert(
                    ['user_id' => $userId, 'permission_id' => $permissionId],
                    [
                        'can_view'   => (bool) ($actions['can_view'] ?? false),
                        'can_create' => (bool) ($actions['can_create'] ?? false),
                        'can_edit'   => (bool) ($actions['can_edit'] ?? false),
                        'can_delete' => (bool) ($actions['can_delete'] ?? false),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        Cache::forget('app.permissions');
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_column($this->permissions, 'key'))
            ->pluck('id');

        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Cache::forget('app.permissions');
    }
};
