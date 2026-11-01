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

<main class="min-h-screen bg-[#f5f6f3] font-sans text-[#172c35] lg:grid lg:grid-cols-2" x-data>
    <section class="flex min-h-[11rem] flex-col justify-between bg-[#17343d] px-6 py-6 text-white sm:min-h-[13rem] sm:px-10 sm:py-8 lg:min-h-screen lg:px-14 lg:py-12 xl:px-20">
        <a href="/" class="inline-flex w-fit items-center gap-3 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#b7d4ce]" aria-label="KULKAS, kembali ke halaman utama">
            <span class="flex h-10 w-10 items-center justify-center border border-white/25 text-[#c4dfd8]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="square" stroke-linejoin="round" d="M12 2.75v18.5M4 7.25l16 9.5M4 16.75l16-9.5M9.5 4.2 12 6.7l2.5-2.5M9.5 19.8l2.5-2.5 2.5 2.5M3.8 10.3l3.4.9-.9 3.4m14-4.3-3.4.9.9 3.4" />
                </svg>
            </span>
            <span>
                <span class="block text-sm font-semibold tracking-[0.2em]">KULKAS</span>
                <span class="mt-0.5 block text-[10px] uppercase tracking-[0.16em] text-white/55">Operasional frozen food</span>
            </span>
        </a>

        <div class="hidden max-w-lg py-16 lg:block">
            <p class="mb-5 text-xs font-medium uppercase tracking-[0.2em] text-[#b7d4ce]">Ruang kerja usaha Anda</p>
            <h1 class="text-5xl font-medium leading-[1.12] tracking-tight xl:text-6xl">
                Tetap tertata,<br>
                <span class="text-[#b7d4ce]">setiap hari.</span>
            </h1>
            <p class="mt-6 max-w-md text-base leading-7 text-white/65">
                Satu tempat untuk mengelola stok, pesanan, dan operasional bisnis frozen food.
            </p>
        </div>

        <p class="hidden text-xs tracking-wide text-white/45 lg:block">KULKAS <span class="px-2 text-white/25">/</span> Bekasi</p>
        <p class="text-xs leading-5 text-white/60 lg:hidden">Kelola stok dan operasional bisnis frozen food.</p>
    </section>

    <section class="flex min-h-[calc(100vh-11rem)] items-center justify-center px-6 py-12 sm:min-h-[calc(100vh-13rem)] sm:px-10 lg:min-h-screen lg:px-12">
        <div class="w-full max-w-[25rem]">
            <div class="mb-9">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#55736f]">Area admin</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-[#172c35]">Masuk ke akun</h2>
                <p class="mt-2 text-sm leading-6 text-[#64747a]">Gunakan email dan kata sandi yang terdaftar.</p>
            </div>

            <x-auth-session-status class="mb-6 border-l-2 border-[#527c70] bg-[#e8efeb] px-4 py-3 text-sm text-[#315b50]" :status="session('status')" />

            <form wire:submit="login" class="space-y-5">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-[#344950]">Email</label>
                    <input
                        wire:model="form.email"
                        id="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="block w-full rounded-md border border-[#cbd3d0] bg-white px-3.5 py-3 text-sm text-[#172c35] placeholder:text-[#9aa6a3] shadow-none transition-colors focus:border-[#527c70] focus:outline-none focus:ring-2 focus:ring-[#527c70]/20"
                        placeholder="nama@perusahaan.com"
                    >
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-sm text-[#9c5252]" />
                </div>

                <div x-data="{ show: false }">
                    <label for="password" class="mb-2 block text-sm font-medium text-[#344950]">Kata sandi</label>
                    <div class="relative">
                        <input
                            wire:model="form.password"
                            id="password"
                            :type="show ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            class="block w-full rounded-md border border-[#cbd3d0] bg-white px-3.5 py-3 pr-12 text-sm text-[#172c35] placeholder:text-[#9aa6a3] shadow-none transition-colors focus:border-[#527c70] focus:outline-none focus:ring-2 focus:ring-[#527c70]/20"
                            placeholder="Masukkan kata sandi"
                        >
                        <button
                            type="button"
                            @click="show = !show"
                            :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-[#71817f] hover:text-[#315b50] focus-visible:outline focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-[#527c70]"
                        >
                            <svg x-show="!show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" />
                                <circle cx="12" cy="12" r="2.5" stroke-width="1.7" />
                            </svg>
                            <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a15 15 0 0 1-3 3.6M6.2 6.2C3.8 7.7 2.5 12 2.5 12s3.4 6 9.5 6c.9 0 1.8-.2 2.6-.5" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-sm text-[#9c5252]" />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <label for="remember" class="inline-flex items-center gap-2.5 text-sm text-[#52646a]">
                        <input id="remember" type="checkbox" wire:model="form.remember" class="h-4 w-4 rounded border-[#b9c5c1] text-[#315b50] focus:ring-[#527c70]/30">
                        Ingat saya
                    </label>
                    <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-[#315b50] underline decoration-[#a9beb5] underline-offset-4 hover:text-[#17343d]">
                        Pulihkan akun
                    </a>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="login"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-md bg-[#244e49] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#173d38] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#315b50] disabled:cursor-wait disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login">Memproses...</span>
                    <svg wire:loading.remove wire:target="login" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </button>
            </form>

            <div class="mt-8 border-t border-[#dce1de] pt-5">
                <a href="/" class="text-sm text-[#71817f] underline decoration-[#c8d0cc] underline-offset-4 hover:text-[#315b50]">
                    Kembali ke halaman utama
                </a>
            </div>
        </div>
    </section>
</main>
