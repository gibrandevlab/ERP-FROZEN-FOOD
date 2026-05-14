<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <button x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="px-6 py-2.5 bg-red-50 text-red-600 rounded-xl text-sm font-semibold border border-red-200 hover:bg-red-100 hover:text-red-700 transition-colors w-full sm:w-auto">
        Hapus Akun Permanen
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6 bg-white rounded-2xl">
            <h2 class="text-lg font-bold text-slate-800">
                Apakah Anda yakin ingin menghapus akun ini?
            </h2>

            <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                Setelah akun dihapus, semua data dan informasi akan hilang secara permanen. Silakan masukkan kata sandi Anda untuk mengonfirmasi penghapusan.
            </p>

            <div class="mt-6">
                <label for="password" class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Kata Sandi</label>
                <input wire:model="password" id="password" name="password" type="password" placeholder="Masukkan kata sandi"
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-400 transition-all shadow-sm" />
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')"
                        class="px-4 py-2 text-sm font-semibold text-slate-500 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    Batal
                </button>

                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 shadow-lg shadow-red-200/50 transition-all">
                    Hapus Akun
                </button>
            </div>
        </form>
    </x-modal>
</section>
