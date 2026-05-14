<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Permission;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;

class UserPermissionSeeder extends Seeder
{
    /**
     * Seed tabel user_permissions dengan akses awal per user.
     *
     * Admin akan mendapat semua akses.
     * User regular hanya dapat view pada modul-modul tertentu.
     *
     * Jalankan dengan: php artisan db:seed --class=UserPermissionSeeder
     */
    public function run(): void
    {
        // Ambil user sara
        $adminUser = User::where('email', 'sara@gmail.com')->first();
        $allPermissions = Permission::all();

        // ─── Admin: Akses penuh ke semua fitur ────────────────
        if ($adminUser) {
            foreach ($allPermissions as $permission) {
                UserPermission::updateOrCreate(
                    [
                        'user_id'       => $adminUser->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'can_view'      => true,
                        'can_create'    => true,
                        'can_edit'      => true,
                        'can_delete'    => true,
                    ]
                );
            }
        }

        $this->command->info('✅ User permissions berhasil di-seed.');
    }
}
