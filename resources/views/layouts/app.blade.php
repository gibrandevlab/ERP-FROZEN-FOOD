<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Inventory Dashboard') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .app-bg {
            background: linear-gradient(160deg, #F0F5FF 0%, #F8FAFC 60%, #F4F0FF 100%);
            min-height: 100vh;
        }
        .btn-sound {
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.15s ease;
        }
        .btn-sound:active {
            transform: scale(0.97);
        }
        .scrollbar-thin::-webkit-scrollbar { width: 5px; height: 5px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Styling Clean untuk Markdown Rendering di Chat AI */
        .markdown-content {
            font-size: 0.875rem;
            line-height: 1.625;
            color: #1e293b;
            word-break: break-word;
        }
        .markdown-content p {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .markdown-content p:first-child {
            margin-top: 0;
        }
        .markdown-content p:last-child {
            margin-bottom: 0;
        }
        .markdown-content ul, .markdown-content ol {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            padding-left: 1.25rem;
        }
        .markdown-content ul {
            list-style-type: disc;
        }
        .markdown-content ol {
            list-style-type: decimal;
        }
        .markdown-content li {
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .markdown-content strong {
            font-weight: 700;
            color: #0f172a;
        }
        .markdown-content h1, .markdown-content h2, .markdown-content h3, .markdown-content h4 {
            font-weight: 800;
            color: #0f172a;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .markdown-content h1 { font-size: 1.15rem; }
        .markdown-content h2 { font-size: 1.05rem; }
        .markdown-content h3 { font-size: 0.95rem; }
        .markdown-content code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8125rem;
            background-color: #f1f5f9;
            color: #0284c7;
            padding: 0.15rem 0.35rem;
            border-radius: 0.375rem;
            border: 1px solid #e2e8f0;
        }
        .markdown-content pre {
            background-color: #0f172a;
            color: #38bdf8;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            overflow-x: auto;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
            line-height: 1.5;
        }
        .markdown-content pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
            border: none;
        }
        .markdown-content blockquote {
            border-left: 3px solid #3b82f6;
            padding-left: 0.75rem;
            color: #475569;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            font-style: italic;
        }
        .markdown-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
        }
        .markdown-content th, .markdown-content td {
            border: 1px solid #cbd5e1;
            padding: 0.4rem 0.65rem;
            text-align: left;
        }
        .markdown-content th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
        }
        .markdown-content tr:nth-child(even) {
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="{{ request()->routeIs('chat.*') ? 'h-screen w-screen overflow-hidden bg-slate-50' : 'min-h-full app-bg' }} text-slate-800 antialiased" x-data="appShell()">

    @if (request()->routeIs('chat.*'))
        {{-- Fullscreen Mode Khusus Halaman Chat Main --}}
        <main class="h-screen w-screen overflow-hidden flex flex-col">
            {{ $slot }}
        </main>
    @else
        {{-- Normal App Layout untuk Halaman Lain --}}
        <div class="min-h-screen flex flex-col relative">
            {{-- Top Brand Bar --}}
            <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur-xl">
                <div class="mx-auto flex h-16 sm:h-20 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-3" @click="playClick()">
                        <span class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/20">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base sm:text-lg lg:text-xl font-extrabold tracking-tight text-slate-900">Inventory Dashboard</span>
                            <span class="hidden text-xs font-semibold text-blue-600 sm:block">Sistem Manajemen Inventaris</span>
                        </span>
                    </a>
                    <div class="hidden items-center gap-3 sm:flex">
                        <a href="{{ route('chat.index') }}" wire:navigate @click="playClick()" class="flex items-center gap-2 py-2 px-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-extrabold shadow-sm hover:shadow-md hover:from-blue-700 hover:to-indigo-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Asisten AI</span>
                        </a>
                        <div class="text-right">
                            <p class="text-xs sm:text-sm font-extrabold text-slate-800">{{ now('Asia/Jakarta')->isoFormat('dddd, D MMMM Y') }}</p>
                            <p class="text-[10px] text-slate-500">Ringkasan Sistem</p>
                        </div>
                    </div>
                </div>
            </header>

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mx-4 mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm text-emerald-800 shadow-sm sm:mx-6 lg:mx-8">
                    <div class="flex items-center justify-between gap-3"><span class="font-medium">✓ {{ session('success') }}</span><button @click="show = false" class="text-lg text-emerald-600">×</button></div>
                </div>
            @endif

            {{-- Main Content Container --}}
            <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 pb-28 pt-5 sm:px-6 sm:pt-7 lg:px-8 lg:pb-32">
                {{ $slot }}
            </main>

            {{-- Floating Mini AI Chat Widget --}}
            <livewire:chat.floating-widget />

            {{-- Floating Navigation Bar (Z-Index disesuaikan ke 40 agar tidak bentrok dengan floating chat) --}}
            <nav class="fixed inset-x-0 bottom-0 z-40 px-3 pb-[max(12px,env(safe-area-inset-bottom))] sm:px-4">
                <div class="mx-auto max-w-[500px] sm:max-w-[620px] rounded-3xl border border-slate-200/80 bg-white/95 p-1.5 shadow-[0_12px_36px_rgba(15,23,42,.12)] backdrop-blur-xl">
                    <div class="flex h-14 sm:h-16 items-center justify-around gap-1">
                        <a href="{{ route('dashboard') }}" wire:navigate @click="playClick()"
                           class="flex flex-1 flex-col items-center justify-center py-1.5 px-2 rounded-2xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-700 font-semibold' }}">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5M5.5 9v11h13V9M9 20v-6h6v6"/></svg>
                            <span class="text-[10px] sm:text-xs">Beranda</span>
                        </a>
                        <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
                           class="flex flex-1 flex-col items-center justify-center py-1.5 px-2 rounded-2xl transition-all duration-200 {{ request()->routeIs('stok.*') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-700 font-semibold' }}">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span class="text-[10px] sm:text-xs">Stok</span>
                        </a>
                        <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
                           class="flex flex-1 flex-col items-center justify-center py-1.5 px-2 rounded-2xl transition-all duration-200 {{ request()->routeIs('pembukuan.*') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-700 font-semibold' }}">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9h4v10H4zm6 0V5h4v14h-4zm6 0v-7h4v7h-4z"/></svg>
                            <span class="text-[10px] sm:text-xs">Transaksi</span>
                        </a>
                        @if(auth()->user()->is_admin ?? true)
                        <a href="{{ route('admin.pengguna.index') }}" wire:navigate @click="playClick()"
                           class="flex flex-1 flex-col items-center justify-center py-1.5 px-2 rounded-2xl transition-all duration-200 {{ request()->routeIs('admin.*') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-700 font-semibold' }}">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm10 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                            <span class="text-[10px] sm:text-xs">Admin</span>
                        </a>
                        @endif
                        <a href="{{ route('profile') }}" wire:navigate @click="playClick()"
                           class="flex flex-1 flex-col items-center justify-center py-1.5 px-2 rounded-2xl transition-all duration-200 {{ request()->routeIs('profile') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-700 font-semibold' }}">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z"/></svg>
                            <span class="text-[10px] sm:text-xs">Profil</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    @endif

    @livewireScripts
    @stack('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            let audioCtx = null;
            Alpine.data('appShell', () => ({
                initAudio() { if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)(); if (audioCtx.state === 'suspended') audioCtx.resume(); },
                playClick() { try { this.initAudio(); const osc = audioCtx.createOscillator(); const gainNode = audioCtx.createGain(); osc.type = 'sine'; osc.frequency.setValueAtTime(800, audioCtx.currentTime); osc.frequency.exponentialRampToValueAtTime(300, audioCtx.currentTime + 0.05); gainNode.gain.setValueAtTime(0.08, audioCtx.currentTime); gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.05); osc.connect(gainNode); gainNode.connect(audioCtx.destination); osc.start(); osc.stop(audioCtx.currentTime + 0.05); } catch(e) {} }
            }));
        });
    </script>
</body>
</html>
