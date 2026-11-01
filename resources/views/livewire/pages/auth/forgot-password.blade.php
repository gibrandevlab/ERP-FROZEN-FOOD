<?php

use App\Models\User;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email              = '';
    public string $recovery_phrase    = '';
    public string $password           = '';
    public string $password_confirmation = '';

    /**
     * Pulihkan akun menggunakan kata rahasia.
     *
     * Alur:
     * 1. Cari user berdasarkan email
     * 2. Verifikasi kata rahasia dengan Hash::check
     * 3. Jika cocok → update password lalu redirect ke login
     * 4. Jika tidak cocok → tampilkan pesan generik (tidak bocorkan info email)
     */
    public function pulihkanAkun(): void
    {
        $this->validate([
            'email'           => ['required', 'string', 'email'],
            'recovery_phrase' => ['required', 'string'],
            'password'        => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $this->email)->first();

        // Pesan error dibuat generik agar tidak bisa ditebak mana email yang terdaftar
        if (! $user || ! $user->verifyRecoveryPhrase($this->recovery_phrase)) {
            $this->addError('recovery_phrase', 'Email atau kata rahasia tidak cocok. Periksa kembali.');
            return;
        }

        // Cast 'hashed' di model User otomatis mengenkripsi password baru
        $user->update(['password' => $this->password]);

        session()->flash('status', 'Password berhasil diubah! Silakan login dengan password baru.');

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<x-auth-shell title="Pulihkan akun" subtitle="Gunakan email dan kata rahasia untuk mengganti password.">
    {{-- Penjelasan halaman --}}
    <div class="mb-6 rounded-lg border border-[#dce1de] bg-[#f5f6f3] px-4 py-3 text-sm leading-relaxed text-[#52646a]">
        <strong class="text-[#344950]">Lupa password?</strong> Masukkan email dan kata rahasia yang kamu buat saat daftar,
        lalu tentukan password baru. Tidak perlu menunggu email apa pun.
    </div>

    {{-- Pesan sukses setelah reset --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="pulihkanAkun">

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1.5 w-full"
                          type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Kata Rahasia --}}
        <div class="mt-4">
            <x-input-label for="recovery_phrase" :value="__('Kata Rahasia')" />
            <x-text-input wire:model="recovery_phrase" id="recovery_phrase"
                          class="block mt-1.5 w-full" type="text"
                          name="recovery_phrase" required autocomplete="off"
                          placeholder="Kata rahasia yang kamu simpan saat daftar" />
            <x-input-error :messages="$errors->get('recovery_phrase')" class="mt-2" />
        </div>

        {{-- Password Baru --}}
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password Baru')" />
            <x-text-input wire:model="password" id="password"
                          class="block mt-1.5 w-full" type="password"
                          name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Konfirmasi Password Baru --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation"
                          class="block mt-1.5 w-full" type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-[#e6eae7] pt-5">
            <a class="text-sm font-medium text-[#52646a] underline decoration-[#a9beb5] underline-offset-4 transition-colors hover:text-[#172c35] focus:outline-none focus:ring-2 focus:ring-[#527c70] focus:ring-offset-2"
               href="{{ route('login') }}" wire:navigate>
                {{ __('Kembali ke Login') }}
            </a>

            <x-primary-button>
                {{ __('Pulihkan Akun') }}
            </x-primary-button>
        </div>

    </form>
</x-auth-shell>
