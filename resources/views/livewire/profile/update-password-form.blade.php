<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <form wire:submit="updatePassword" class="space-y-4">
        <div>
            <label for="current_password" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Kata Sandi Saat Ini</label>
            <input wire:model="current_password" id="current_password" type="password"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" required />
            @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Kata Sandi Baru</label>
            <input wire:model="password" id="password" type="password"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" required />
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Konfirmasi Kata Sandi</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" required />
            @error('password_confirmation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-blue-200/50 hover:opacity-90 transition-all">
                Simpan Kata Sandi
            </button>

            <x-action-message class="text-sm text-emerald-600 font-semibold" on="password-updated">
                Berhasil disimpan.
            </x-action-message>
        </div>
    </form>
</section>
