<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ─── Mass Assignment ─────────────────────────────────────

    protected $fillable = [
        'name',
        'email',
        'password',
        'recovery_phrase',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'recovery_phrase',
        'remember_token',
    ];

    // ─── Casting ──────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'password'        => 'hashed', // Otomatis di-hash saat di-set
            'recovery_phrase' => 'hashed', // Sama seperti password
            'is_admin'        => 'boolean',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────

    /**
     * Semua entri hak akses milik user ini.
     */
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Semua sesi percakapan chat milik user ini.
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Kuota token AI untuk user ini (bisa null jika belum diset = unlimited).
     */
    public function tokenLimit()
    {
        return $this->hasOne(TokenLimit::class);
    }

    /**
     * Cek apakah user boleh mengirim pesan ke AI (kuota & enable status).
     * Admin selalu boleh (tidak dibatasi).
     */
    public function canUseChatAI(): bool
    {
        if ($this->is_admin) return true;
        $limit = $this->tokenLimit;
        if (! $limit) return true; // belum ada record = unlimited
        return $limit->canSendMessage();
    }

    /**
     * Pesan error kuota (untuk ditampilkan ke user).
     */
    public function chatQuotaErrorMessage(): string
    {
        $limit = $this->tokenLimit;
        if (! $limit || ! $this->tokenLimit) return '';
        if (! $limit->is_enabled) return 'Akses AI Anda telah dinonaktifkan oleh administrator.';
        if ($limit->daily_token_limit > 0 && $limit->todayUsage() >= $limit->daily_token_limit) {
            return "Kuota harian Anda ({$limit->daily_token_limit} token) telah habis. Coba lagi besok.";
        }
        if ($limit->monthly_token_limit > 0 && $limit->monthUsage() >= $limit->monthly_token_limit) {
            return "Kuota bulanan Anda ({$limit->monthly_token_limit} token) telah habis.";
        }
        return '';
    }

    // ─── Helper Methods ──────────────────────────────────────

    /**
     * Cache in-memory semua permission user untuk request ini.
     * Diisi saat pertama kali canDo() dipanggil, reuse setelahnya.
     */
    private ?Collection $_permCache = null;

    /**
     * Cek apakah user punya akses tertentu ke sebuah fitur.
     *
     * Cara pakai:
     *   $user->canDo('products', 'view')   → true/false
     *   $user->canDo('ledger', 'delete')   → true/false
     *
     * Optimasi: semua permission user di-load SEKALI dengan eager loading,
     * lalu di-cache in-memory. Panggilan @can/@cannot berikutnya di request
     * yang sama tidak akan menyentuh DB lagi.
     *
     * @param string $permissionKey  Kode fitur, contoh: 'products', 'ledger'
     * @param string $action         Aksi: 'view' | 'create' | 'edit' | 'delete'
     */
    public function canDo(string $permissionKey, string $action): bool
    {
        // Admin selalu punya akses ke semua fitur tanpa cek DB
        if ($this->is_admin) {
            return true;
        }

        // Load semua permission user sekali → reuse untuk semua panggilan berikutnya
        $this->_permCache ??= $this->userPermissions()
            ->with('permission') // eager load: 1 query JOIN, bukan N subquery
            ->get();

        $column = 'can_' . $action; // → 'can_view', 'can_create', dst.

        return $this->_permCache
            ->first(fn($up) => $up->permission?->key === $permissionKey)
            ?->$column ?? false;
    }

    /**
     * Clear cache permission in-memory (dipanggil saat permission diubah live).
     * Berguna jika ada use case update permission di tengah request.
     */
    public function clearPermCache(): void
    {
        $this->_permCache = null;
    }

    /**
     * Verifikasi kata rahasia saat user ingin reset password.
     *
     * Cara pakai di Controller:
     *   if ($user->verifyRecoveryPhrase($request->recovery_phrase)) {
     *       // izinkan ganti password
     *   }
     *
     * @param string $plain Kata rahasia yang diinput user (belum di-hash)
     */
    public function verifyRecoveryPhrase(string $plain): bool
    {
        // Ambil nilai mentah dari DB (sudah berupa hash bcrypt)
        $hashed = $this->getAttributes()['recovery_phrase'] ?? null;

        if (! $hashed) {
            return false; // Kata rahasia belum pernah di-set
        }

        return Hash::check($plain, $hashed);
    }
}
