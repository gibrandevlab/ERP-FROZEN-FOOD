<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenLimit extends Model
{
    protected $table = 'token_limits';

    protected $fillable = [
        'user_id',
        'daily_token_limit',
        'monthly_token_limit',
        'is_enabled',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'daily_token_limit'   => 'integer',
            'monthly_token_limit' => 'integer',
            'is_enabled'          => 'boolean',
        ];
    }

    // ─── Relasi ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helper: Hitung token terpakai hari ini ────────────────

    /**
     * Total token yang dipakai user ini hari ini (UTC).
     */
    public function todayUsage(): int
    {
        return TokenUsage::whereHas('conversation', fn($q) => $q->where('user_id', $this->user_id))
            ->whereDate('created_at', today())
            ->sum('total_tokens');
    }

    /**
     * Total token yang dipakai user ini bulan ini.
     */
    public function monthUsage(): int
    {
        return TokenUsage::whereHas('conversation', fn($q) => $q->where('user_id', $this->user_id))
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_tokens');
    }

    /**
     * Sisa kuota harian. null = unlimited.
     */
    public function remainingDailyTokens(): ?int
    {
        if ($this->daily_token_limit === 0) return null;
        return max(0, $this->daily_token_limit - $this->todayUsage());
    }

    /**
     * Sisa kuota bulanan. null = unlimited.
     */
    public function remainingMonthlyTokens(): ?int
    {
        if ($this->monthly_token_limit === 0) return null;
        return max(0, $this->monthly_token_limit - $this->monthUsage());
    }

    /**
     * Apakah user masih punya sisa kuota untuk mengirim pesan?
     */
    public function canSendMessage(): bool
    {
        if (! $this->is_enabled) return false;
        if ($this->daily_token_limit > 0 && $this->todayUsage() >= $this->daily_token_limit) return false;
        if ($this->monthly_token_limit > 0 && $this->monthUsage() >= $this->monthly_token_limit) return false;
        return true;
    }
}
