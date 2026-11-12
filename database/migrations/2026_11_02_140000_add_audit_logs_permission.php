<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSION = [
        'key' => 'audit_logs',
        'label' => 'Riwayat Aktivitas',
        'category' => 'admin',
        'description' => 'Lihat riwayat perubahan data dan pengguna',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $existingId = DB::table('permissions')->where('key', self::PERMISSION['key'])->value('id');

        if ($existingId) {
            DB::table('permissions')->where('id', $existingId)->update([
                'label' => self::PERMISSION['label'],
                'category' => self::PERMISSION['category'],
                'description' => self::PERMISSION['description'],
                'updated_at' => now(),
            ]);
        } else {
            DB::table('permissions')->insert(self::PERMISSION + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('app.permissions');
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('key', self::PERMISSION['key'])
            ->pluck('id');

        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Cache::forget('app.permissions');
    }
};
