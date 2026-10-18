<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'title',
    ];

    /**
     * Relasi ke pengguna pemilik percakapan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke seluruh pesan dalam percakapan ini (urut kronologis ASC).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Relasi ke catatan token usage untuk sesi ini.
     */
    public function tokenUsages(): HasMany
    {
        return $this->hasMany(TokenUsage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    /**
     * Hitung total token yang digunakan dalam sesi ini.
     */
    public function getTotalTokensAttribute(): int
    {
        return (int) $this->tokenUsages()->sum('total_tokens');
    }

    /**
     * Hitung total token cache hit dalam sesi ini.
     */
    public function getTotalCacheHitTokensAttribute(): int
    {
        return (int) $this->tokenUsages()->sum('cache_hit_tokens');
    }

    /**
     * Generate judul percakapan dari 5 kata pertama pesan awal.
     */
    public static function generateTitleFromContent(string $content, int $words = 5): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
        if (empty($clean)) {
            return 'Percakapan Baru';
        }

        $title = Str::words($clean, $words, '...');
        return Str::limit($title, 60);
    }

    /**
     * Scope percakapan untuk user tertentu diurutkan dari interaksi terakhir.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->orderBy('updated_at', 'desc');
    }

    /**
     * Label grup waktu untuk UI sidebar (Hari Ini, Kemarin, 7 Hari Terakhir, Lebih Lama).
     */
    public function getTimeGroupAttribute(): string
    {
        $date = Carbon::parse($this->updated_at);

        if ($date->isToday()) {
            return 'Hari Ini';
        }

        if ($date->isYesterday()) {
            return 'Kemarin';
        }

        if ($date->greaterThanOrEqualTo(now()->subDays(7)->startOfDay())) {
            return '7 Hari Terakhir';
        }

        if ($date->greaterThanOrEqualTo(now()->subDays(30)->startOfDay())) {
            return '30 Hari Terakhir';
        }

        return 'Lebih Lama';
    }
}
