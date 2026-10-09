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
            <label for="current_password" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
            <input wire:model="current_password" id="current_password" type="password"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" required />
            @error('current_password') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Kata Sandi Baru <span class="text-rose-500">*</span></label>
            <input wire:model="password" id="password" type="password"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" required />
            @error('password') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" required />
            @error('password_confirmation') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" @click="playSuccess()"
                    class="btn-sound inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-2xl text-xs font-bold uppercase tracking-wider shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Simpan Kata Sandi</span>
            </button>

            <x-action-message class="text-xs text-emerald-600 font-extrabold flex items-center gap-1" on="password-updated">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Berhasil disimpan.</span>
            </x-action-message>
        </div>
    </form>
</section>
