<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TelegramService — Kirim pesan dan file ke Telegram Bot.
 */
class TelegramService
{
    protected string $botToken;
    protected string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->chatId   = config('services.telegram.chat_id', '');
    }

    /**
     * Kirim pesan teks ke Telegram.
     */
    public function sendMessage(string $text): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram: bot_token atau chat_id belum dikonfigurasi.');
            return false;
        }

        $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id'    => $this->chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);

        if (!$response->successful()) {
            Log::error('Telegram sendMessage gagal: ' . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Kirim dokumen (PDF) ke Telegram.
     */
    public function sendDocument(string $filePath, string $caption = ''): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram: bot_token atau chat_id belum dikonfigurasi.');
            return false;
        }

        if (!file_exists($filePath)) {
            Log::error("Telegram: File tidak ditemukan: {$filePath}");
            return false;
        }

        $response = Http::attach(
            'document',
            file_get_contents($filePath),
            basename($filePath)
        )->post("https://api.telegram.org/bot{$this->botToken}/sendDocument", [
            'chat_id'    => $this->chatId,
            'caption'    => $caption,
            'parse_mode' => 'HTML',
        ]);

        if (!$response->successful()) {
            Log::error('Telegram sendDocument gagal: ' . $response->body());
            return false;
        }

        return true;
    }
}
