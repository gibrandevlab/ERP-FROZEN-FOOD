<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_call_id',
        'tool_calls_json',
    ];

    /**
     * Casting atribut.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke sesi percakapan.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relasi ke catatan token usage untuk pesan ini (jika peran assistant).
     */
    public function tokenUsage()
    {
        return $this->hasOne(TokenUsage::class, 'message_id');
    }

    /**
     * Accessor session_id (alias untuk conversation_id).
     */
    public function getSessionIdAttribute(): string
    {
        return $this->conversation_id;
    }

    /**
     * Cek apakah role adalah user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Cek apakah role adalah assistant.
     */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Cek apakah role adalah system.
     */
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }
}
