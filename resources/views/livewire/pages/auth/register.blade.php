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

<div>
    <form wire:submit="register">

        {{-- Nama Lengkap --}}
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full"
                          type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full"
                          type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                          type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Konfirmasi Password --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation"
                          class="block mt-1 w-full" type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- ─── Kata Rahasia (Recovery Phrase) ───────────────────────────── --}}
        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg">
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-1">
                🔑 Kata Rahasia — Simpan Baik-Baik!
            </p>
            <p class="text-xs text-amber-700 dark:text-amber-400 mb-3 leading-relaxed">
                Kata rahasia ini <strong>pengganti link reset password via email</strong>.
                Jika kamu lupa password, kamu wajib memasukkan kata rahasia ini untuk bisa menggantinya.
                <br><strong>Jika lupa kata rahasia, akun tidak bisa dipulihkan.</strong>
            </p>

            <x-input-label for="recovery_phrase" :value="__('Kata Rahasia')" />
            <x-text-input wire:model="recovery_phrase" id="recovery_phrase"
                          class="block mt-1 w-full" type="text"
                          name="recovery_phrase" required autocomplete="off"
                          placeholder="Contoh: kucing hitam lari di taman belakang" />
            <x-input-error :messages="$errors->get('recovery_phrase')" class="mt-2" />

            <div class="mt-3">
                <x-input-label for="recovery_phrase_confirmation" :value="__('Konfirmasi Kata Rahasia')" />
                <x-text-input wire:model="recovery_phrase_confirmation" id="recovery_phrase_confirmation"
                              class="block mt-1 w-full" type="text"
                              name="recovery_phrase_confirmation" required autocomplete="off"
                              placeholder="Ketik ulang kata rahasia di atas" />
                <x-input-error :messages="$errors->get('recovery_phrase_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
               href="{{ route('login') }}" wire:navigate>
                {{ __('Sudah punya akun?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Daftar Sekarang') }}
            </x-primary-button>
        </div>

    </form>
</div>
