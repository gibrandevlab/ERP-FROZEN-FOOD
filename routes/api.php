<?php

use App\Http\Controllers\Api\ChatSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Manajemen Sesi Chat & Tracking Token Usage
|--------------------------------------------------------------------------
|
| Endpoint untuk mengambil riwayat percakapan lama, mengirim pesan ke DeepSeek
| dengan context truncation, serta pemantauan token usage dan cache ratio.
|
*/

Route::middleware(['web', 'auth'])->prefix('chat')->group(function () {
    // 1. Fetch History Endpoints
    Route::get('/sessions', [ChatSessionController::class, 'index'])->name('api.chat.sessions.index');
    Route::get('/sessions/{id}', [ChatSessionController::class, 'show'])->name('api.chat.sessions.show');
    Route::delete('/sessions/{id}', [ChatSessionController::class, 'destroy'])->name('api.chat.sessions.destroy');

    // 2. Send Message & Token Logging Endpoint
    Route::post('/send', [ChatSessionController::class, 'sendMessage'])->name('api.chat.send');

    // 3. Token Analytics & Monitoring Endpoint
    Route::get('/token-summary', [ChatSessionController::class, 'tokenSummary'])->name('api.chat.token-summary');
});
