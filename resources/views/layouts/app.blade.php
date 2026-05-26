<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@hasSection('meta_title') @yield('meta_title') — @endif {{ config('app.name', 'UMKM SaaS') }}</title>
    <meta name="description" content="@yield('meta_description', 'Sistem manajemen UMKM berbasis SaaS.')" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* ── Glassmorphism Sidebar ─────────────────────────────── */
        .sidebar-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.06);
        }
        .dark .sidebar-glass {
            background: rgba(15, 15, 20, 0.88);
            border-right: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.4);
        }

        /* ── App background ────────────────────────────────────── */
        .app-bg {
            background: #F0F4FF;
            background: linear-gradient(160deg, #EFF6FF 0%, #F8FAFC 50%, #F5F0FF 100%);
            min-height: 100vh;
        }
        .dark .app-bg {
            background: #0a0a14;
            background: linear-gradient(160deg, #080d1a 0%, #0d0d1a 50%, #0a0814 100%);
        }

        /* ── Sound ripple feedback ────────────────────────────── */
        .btn-sound {
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .btn-sound:active {
            transform: scale(0.96);
        }

        /* ── Hide scrollbar but keep scroll ─────────────────── */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-full app-bg font-[Inter] antialiased" x-data="appShell()" @keydown.escape="sidebarOpen = false">

<div class="flex min-h-screen">



    {{-- ─── Area Konten ──────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-h-screen w-full min-w-0">

        {{-- Flash Messages --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="mx-4 sm:mx-6 mt-4 p-3 bg-green-50/90 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm rounded-xl flex items-center justify-between shadow-sm backdrop-blur-sm">
                <span class="flex items-center gap-2"><span>✅</span> {{ session('success') }}</span>
                <button @click="show = false" class="text-green-400 hover:text-green-600 ml-3 text-lg leading-none">×</button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="mx-4 sm:mx-6 mt-4 p-3 bg-red-50/90 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-xl flex items-center justify-between shadow-sm backdrop-blur-sm">
                <span class="flex items-center gap-2"><span>❌</span> {{ session('error') }}</span>
                <button @click="show = false" class="text-red-400 hover:text-red-600 ml-3 text-lg leading-none">×</button>
            </div>
        @endif

        {{-- Konten Halaman --}}
        <main class="flex-1 p-4 sm:p-6 pb-24 lg:pb-6 w-full min-w-0">
            {{ $slot }}
        </main>

    </div>

</div>

{{-- ── Bottom Navigation Bar (Mobile & Desktop Dock) ──────────────────────────────── --}}
<nav class="fixed bottom-0 inset-x-0 z-50 sm:pb-4 pointer-events-none"
     style="filter: drop-shadow(0 -4px 32px rgba(0,0,0,0.08));">
    <div class="pointer-events-auto sm:max-w-md sm:mx-auto sm:rounded-2xl overflow-hidden bg-white/95 backdrop-blur-xl border-t sm:border border-slate-200/60"
         style="box-shadow: 0 -2px 32px rgba(0,0,0,0.04);">
        <div class="flex items-stretch h-[60px] sm:h-[65px]">

        {{-- Beranda --}}
        <a href="{{ route('dashboard') }}" wire:navigate @click="playClick()"
           class="flex-1 flex flex-col items-center justify-center gap-1 btn-sound transition-colors
                  {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('dashboard') ? '2.25' : '1.75' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] {{ request()->routeIs('dashboard') ? 'font-bold' : 'font-semibold' }}">Beranda</span>
        </a>

        <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
           class="flex-1 flex flex-col items-center justify-center gap-1 btn-sound transition-colors
                  {{ request()->routeIs('stok.*') ? 'text-blue-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('stok.*') ? '2.25' : '1.75' }}" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="text-[10px] {{ request()->routeIs('stok.*') ? 'font-bold' : 'font-semibold' }}">Stok</span>
        </a>

        {{-- Transaksi --}}
        <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
           class="flex-1 flex flex-col items-center justify-center gap-1 btn-sound transition-colors
                  {{ request()->routeIs('pembukuan.*') ? 'text-emerald-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('pembukuan.*') ? '2.25' : '1.75' }}" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="text-[10px] {{ request()->routeIs('pembukuan.*') ? 'font-bold' : 'font-semibold' }}">Transaksi</span>
        </a>

        {{-- Admin (Hanya untuk admin) --}}
        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate @click="playClick()"
           class="flex-1 flex flex-col items-center justify-center gap-1 btn-sound transition-colors
                  {{ request()->routeIs('admin.*') ? 'text-purple-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('admin.*') ? '2.25' : '1.75' }}" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="text-[10px] {{ request()->routeIs('admin.*') ? 'font-bold' : 'font-semibold' }}">Admin</span>
        </a>
        @endif

        {{-- Profil --}}
        <a href="{{ route('profile') }}" wire:navigate @click="playClick()"
           class="flex-1 flex flex-col items-center justify-center gap-1 btn-sound transition-colors
                  {{ request()->routeIs('profile') ? 'text-blue-600' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('profile') ? '2.25' : '1.75' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-[10px] {{ request()->routeIs('profile') ? 'font-bold' : 'font-semibold' }}">Profil</span>
        </a>

        </div>
    </div>
</nav>

@livewireScripts
@stack('scripts')

<script>
    document.addEventListener('alpine:init', () => {
        let audioCtx = null;
        
        Alpine.data('appShell', () => ({
            sidebarOpen: false,
            
            initAudio() {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
            },
            
            playClick() {
                try {
                    this.initAudio();
                    const osc = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();
                    
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(800, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(300, audioCtx.currentTime + 0.05);
                    
                    gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.05);
                    
                    osc.connect(gainNode);
                    gainNode.connect(audioCtx.destination);
                    
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.05);
                } catch(e) {
                    console.log('Audio error:', e);
                }
            },
            
            playDanger() {
                try {
                    this.initAudio();
                    const osc = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();
                    
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(150, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(80, audioCtx.currentTime + 0.15);
                    
                    gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);
                    
                    osc.connect(gainNode);
                    gainNode.connect(audioCtx.destination);
                    
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.15);
                } catch(e) {}
            },
            
            playSuccess() {
                try {
                    this.initAudio();
                    const osc = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();
                    
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(400, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(800, audioCtx.currentTime + 0.1);
                    
                    gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
                    
                    osc.connect(gainNode);
                    gainNode.connect(audioCtx.destination);
                    
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.1);
                } catch(e) {}
            }
        }));
    });
</script>

</body>
</html>
