<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenUsage extends Model
{
    use HasFactory;

    protected $table = 'token_usages';

    protected $fillable = [
        'conversation_id',
        'message_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cache_hit_tokens',
        'cache_miss_tokens',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens'     => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens'      => 'integer',
            'cache_hit_tokens'  => 'integer',
            'cache_miss_tokens' => 'integer',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    /**
     * Relasi ke sesi percakapan (Conversation / Session).
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Alias untuk relasi session.
     */
    public function session(): BelongsTo
    {
        return $this->conversation();
    }

    /**
     * Relasi ke pesan ChatMessage terkait.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    /**
     * Accessor untuk alias session_id.
     */
    public function getSessionIdAttribute(): string
    {
        return $this->conversation_id;
    }

    /**
     * Hitung estimasi persentase cache hit.
     */
    public function getCacheHitRatioAttribute(): float
    {
        if ($this->prompt_tokens <= 0) {
            return 0.0;
        }

        return round(($this->cache_hit_tokens / $this->prompt_tokens) * 100, 1);
    }
}
