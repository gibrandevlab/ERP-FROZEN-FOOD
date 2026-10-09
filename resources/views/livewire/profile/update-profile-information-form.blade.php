<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <form wire:submit="updateProfileInformation" class="space-y-4">
        <div>
            <label for="name" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input wire:model="name" id="name" type="text"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" required autofocus />
            @error('name') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Alamat Email <span class="text-rose-500">*</span></label>
            <input wire:model="email" id="email" type="email"
                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" required />
            @error('email') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 bg-amber-50 border border-amber-200 p-3.5 rounded-2xl space-y-2">
                    <p class="text-xs text-amber-800 font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Email Anda belum diverifikasi.</span>
                        <button wire:click.prevent="sendVerification" class="font-bold underline text-amber-900 hover:text-amber-950 focus:outline-none ml-1">
                            Kirim ulang email verifikasi
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="font-extrabold text-xs text-emerald-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Tautan verifikasi baru telah dikirim ke alamat email Anda.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" @click="playSuccess()"
                    class="btn-sound inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-2xl text-xs font-bold uppercase tracking-wider shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <span>Simpan Profil</span>
            </button>

            <x-action-message class="text-xs text-emerald-600 font-extrabold flex items-center gap-1" on="profile-updated">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Berhasil disimpan.</span>
            </x-action-message>
        </div>
    </form>
</section>
