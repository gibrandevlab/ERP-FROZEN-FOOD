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
            @click="playDanger()"
            class="btn-sound inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-[#f7eceb] text-[#8f4a4a] rounded-lg text-xs font-extrabold uppercase tracking-wider border border-[#ead6d4] hover:bg-[#ead6d4] transition-all w-full sm:w-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <span>Hapus Akun Permanen</span>
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6 bg-white rounded-lg space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#f7eceb] text-[#8f4a4a] border border-[#ead6d4] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-[#172c35]">
                        Apakah Anda yakin ingin menghapus akun?
                    </h2>
                    <p class="text-xs text-[#64747a] font-medium mt-0.5">
                        Tindakan ini permanen dan tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <p class="text-xs sm:text-sm text-[#52646a] font-medium leading-relaxed bg-[#f7eceb]/50 p-4 rounded-lg border border-[#ead6d4]/80">
                Setelah akun dihapus, seluruh data operasional dan riwayat transaksi akan terhapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi.
            </p>

            <div>
                <label for="password" class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Kata Sandi Konfirmasi <span class="text-[#9c5252]">*</span></label>
                <input wire:model="password" id="password" name="password" type="password" placeholder="Masukkan kata sandi akun"
                       class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#8f4a4a]/20 focus:border-[#8f4a4a] transition-all shadow-none" />
                @error('password') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                <button type="button" x-on:click="$dispatch('close')" @click="playClick()"
                        class="btn-sound px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">
                    Batal
                </button>

                <button type="submit" @click="playDanger()"
                        class="btn-sound inline-flex items-center gap-2 px-5 py-2.5 bg-[#8f4a4a] text-white rounded-lg text-xs font-bold uppercase tracking-wider shadow-none shadow-[#8f4a4a]/15 hover:bg-[#7a3d3d] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus Akun</span>
                </button>
            </div>
        </form>
    </x-modal>
</section>
