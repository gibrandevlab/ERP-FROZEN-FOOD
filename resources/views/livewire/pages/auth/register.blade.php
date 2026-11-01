<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name                         = '';
    public string $email                        = '';
    public string $password                     = '';
    public string $password_confirmation        = '';
    public string $recovery_phrase              = '';
    public string $recovery_phrase_confirmation = '';

    /**
     * Proses pendaftaran user baru.
     * Cast 'hashed' di model User akan otomatis mengenkripsi
     * password dan recovery_phrase — tidak perlu Hash::make manual.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name'                         => ['required', 'string', 'max:255'],
            'email'                        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'                     => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'recovery_phrase'              => ['required', 'string', 'min:6', 'max:255', 'confirmed'],
            'recovery_phrase_confirmation' => ['required', 'string'],
        ]);

        // Hapus field konfirmasi sebelum create (bukan kolom di tabel)
        unset($validated['password_confirmation'], $validated['recovery_phrase_confirmation']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<x-auth-shell title="Buat akun baru" subtitle="Daftarkan akun untuk mulai mengelola operasional frozen food.">
    <form wire:submit="register">

        {{-- Nama Lengkap --}}
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" />
            <x-text-input wire:model="name" id="name" class="block mt-1.5 w-full"
                          type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1.5 w-full"
                          type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1.5 w-full"
                          type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Konfirmasi Password --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation"
                          class="block mt-1.5 w-full" type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- ─── Kata Rahasia (Recovery Phrase) ───────────────────────────── --}}
        <div class="mt-6 rounded-lg border border-[#ddcba7] bg-[#f7f1e4] p-4">
            <p class="mb-1 text-sm font-semibold text-[#5e4820]">
                🔑 Kata Rahasia — Simpan Baik-Baik!
            </p>
            <p class="mb-3 text-xs leading-relaxed text-[#5e4820]">
                Kata rahasia ini <strong>pengganti link reset password via email</strong>.
                Jika kamu lupa password, kamu wajib memasukkan kata rahasia ini untuk bisa menggantinya.
                <br><strong>Jika lupa kata rahasia, akun tidak bisa dipulihkan.</strong>
            </p>

            <x-input-label for="recovery_phrase" :value="__('Kata Rahasia')" />
            <x-text-input wire:model="recovery_phrase" id="recovery_phrase"
                          class="block mt-1.5 w-full" type="text"
                          name="recovery_phrase" required autocomplete="off"
                          placeholder="Contoh: kucing hitam lari di taman belakang" />
            <x-input-error :messages="$errors->get('recovery_phrase')" class="mt-2" />

            <div class="mt-3">
                <x-input-label for="recovery_phrase_confirmation" :value="__('Konfirmasi Kata Rahasia')" />
                <x-text-input wire:model="recovery_phrase_confirmation" id="recovery_phrase_confirmation"
                              class="block mt-1.5 w-full" type="text"
                              name="recovery_phrase_confirmation" required autocomplete="off"
                              placeholder="Ketik ulang kata rahasia di atas" />
                <x-input-error :messages="$errors->get('recovery_phrase_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-[#e6eae7] pt-5">
            <a class="text-sm font-medium text-[#52646a] underline decoration-[#a9beb5] underline-offset-4 transition-colors hover:text-[#172c35] focus:outline-none focus:ring-2 focus:ring-[#527c70] focus:ring-offset-2"
               href="{{ route('login') }}" wire:navigate>
                {{ __('Sudah punya akun?') }}
            </a>

            <x-primary-button>
                {{ __('Daftar Sekarang') }}
            </x-primary-button>
        </div>

    </form>
</x-auth-shell>
