<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 px-5 py-10 font-sans" x-data="adminAuth()">
    
    <div class="mb-10 text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full shadow-xl mb-4 border-4 border-blue-50">
            <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">
            Riza <span class="text-blue-600">Frozen Food</span>
        </h1>
        <p class="text-slate-400 text-sm font-semibold uppercase tracking-widest mt-1">Admin Panel</p>
    </div>

    <div class="w-full max-w-[400px] bg-white rounded-[3rem] shadow-[0_30px_100px_-20px_rgba(0,0,0,0.1)] border border-white overflow-hidden">
        
        <div class="p-8 sm:p-10">
            <x-auth-session-status class="mb-6 text-center text-sm font-bold text-blue-600 bg-blue-50 py-2 rounded-xl" :status="session('status')" />

            <form wire:submit="login" class="space-y-6">
                <div class="space-y-2">
                    <label for="email" class="text-xs font-black text-slate-400 uppercase ml-2">Email Admin</label>
                    <input 
                        wire:model="form.email" 
                        id="email" 
                        type="email" 
                        required 
                        autofocus
                        class="w-full px-6 py-4 bg-slate-50 border-none focus:ring-4 focus:ring-blue-100 rounded-2xl text-slate-700 font-bold placeholder:text-slate-300 transition-all duration-300"
                        placeholder="admin@rizafrozen.com"
                        @click="playPop()"
                    >
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-xs font-bold text-rose-500" />
                </div>

                <div class="space-y-2" x-data="{ show: false }">
                    <label for="password" class="text-xs font-black text-slate-400 uppercase ml-2">Kata Sandi</label>
                    <div class="relative">
                        <input 
                            wire:model="form.password" 
                            id="password" 
                            :type="show ? 'text' : 'password'" 
                            required 
                            class="w-full px-6 py-4 bg-slate-50 border-none focus:ring-4 focus:ring-blue-100 rounded-2xl text-slate-700 font-bold placeholder:text-slate-300 transition-all duration-300"
                            placeholder="••••••••"
                            @click="playPop()"
                        >
                        <button type="button" @click="show = !show; playPop()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 transition-colors">
                            <svg x-show="!show" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="show" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-xs font-bold text-rose-500" />
                </div>

                <div class="flex items-center justify-between px-2">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" wire:model="form.remember" class="w-5 h-5 rounded-lg border-slate-200 text-blue-600 focus:ring-blue-500/20 transition-all cursor-pointer" @click="playPop()">
                        <span class="ml-3 text-sm font-bold text-slate-500 group-hover:text-slate-700 transition-colors">Ingat Saya</span>
                    </label>
                </div>

                <div class="pt-4">
                    <button 
                        type="submit" 
                        @click="playSuccess()"
                        class="w-full py-5 bg-blue-600 hover:bg-blue-700 text-white text-lg font-black rounded-3xl shadow-2xl shadow-blue-200 transform transition-all duration-200 active:scale-95 flex items-center justify-center group"
                    >
                        <span>MASUK</span>
                        <svg class="w-6 h-6 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-slate-50/50 p-6 text-center">
            <a href="/" class="text-xs font-bold text-slate-400 hover:text-blue-600 transition-colors uppercase tracking-widest">
                &larr; Kembali ke Website
            </a>
        </div>
    </div>

    <p class="mt-10 text-center text-xs font-bold text-slate-300 uppercase tracking-widest">
        &copy; 2026 Riza Frozen Food &bull; Bekasi
    </p>
</div>

<script>
    function adminAuth() {
        return {
            // Suara klik ringan untuk input
            playPop() {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3');
                audio.volume = 0.3;
                audio.play();
            },
            // Suara klik mantap untuk tombol login
            playSuccess() {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3');
                audio.volume = 0.5;
                audio.play();
            }
        }
    }
</script>