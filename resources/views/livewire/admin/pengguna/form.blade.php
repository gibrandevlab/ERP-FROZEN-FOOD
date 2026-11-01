<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public ?User $user = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $recovery_phrase = '';
    public string $recovery_phrase_confirmation = '';

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->is_admin, 403, 'Halaman ini khusus admin.');

        if ($id) {
            $this->user = User::findOrFail($id);
            $this->name = $this->user->name;
            $this->email = $this->user->email;
        }
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()->is_admin, 403, 'Halaman ini khusus admin.');
        $creating = $this->user === null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
        ];
        if ($creating || filled($this->password)) {
            $rules['password'] = ['required', 'string', Rules\Password::defaults(), 'confirmed'];
        }
        if ($creating || filled($this->recovery_phrase)) {
            $rules['recovery_phrase'] = ['required', 'string', 'min:6', 'max:255', 'confirmed'];
        }

        $validated = $this->validate($rules);

        if ($creating) {
            $validated['is_admin'] = false;
            User::create($validated);
            session()->flash('success', 'Pengguna baru berhasil ditambahkan.');
        } else {
            $this->user->update($validated);
            session()->flash('success', 'Data pengguna berhasil diperbarui.');
        }

        $this->redirectRoute('admin.pengguna.index', navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate class="text-sm font-semibold text-[#64747a] hover:text-[#344950]">Kembali ke pengguna</a>
        <h1 class="mt-3 text-2xl font-extrabold text-[#172c35]">{{ $user ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h1>
    </div>

    <form wire:submit="simpan" class="space-y-5 rounded-lg border border-[#dce1de] bg-white p-6 shadow-none">
        <div>
            <label for="name" class="block text-sm font-semibold text-[#344950]">Nama lengkap</label>
            <input id="name" wire:model="name" type="text" autocomplete="name" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" />
            @error('name')<p class="mt-1 text-xs text-[#9c5252]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-[#344950]">Email</label>
            <input id="email" wire:model="email" type="email" autocomplete="email" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" />
            @error('email')<p class="mt-1 text-xs text-[#9c5252]">{{ $message }}</p>@enderror
        </div>

        <div class="border-t border-[#e6eae7] pt-5">
            <h2 class="text-sm font-bold text-[#344950]">{{ $user ? 'Ganti password (opsional)' : 'Password' }}</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-semibold text-[#344950]">Password</label>
                    <input id="password" wire:model="password" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" />
                    @error('password')<p class="mt-1 text-xs text-[#9c5252]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-[#344950]">Konfirmasi password</label>
                    <input id="password_confirmation" wire:model="password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" />
                    @error('password_confirmation')<p class="mt-1 text-xs text-[#9c5252]">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="border-t border-[#e6eae7] pt-5">
            <h2 class="text-sm font-bold text-[#344950]">{{ $user ? 'Ganti recovery phrase (opsional)' : 'Recovery phrase' }}</h2>
            <p class="mt-1 text-xs text-[#64747a]">Minimal 6 karakter. Nilai disimpan dalam bentuk hash dan diperlukan untuk pemulihan akun.</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="recovery_phrase" class="block text-sm font-semibold text-[#344950]">Recovery phrase</label>
                    <input id="recovery_phrase" wire:model="recovery_phrase" type="password" autocomplete="off" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" />
                    @error('recovery_phrase')<p class="mt-1 text-xs text-[#9c5252]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="recovery_phrase_confirmation" class="block text-sm font-semibold text-[#344950]">Konfirmasi recovery phrase</label>
                    <input id="recovery_phrase_confirmation" wire:model="recovery_phrase_confirmation" type="password" autocomplete="off" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" />
                    @error('recovery_phrase_confirmation')<p class="mt-1 text-xs text-[#9c5252]">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-[#e6eae7] pt-4">
            <a href="{{ route('admin.pengguna.index') }}" wire:navigate class="rounded-lg px-4 py-2 text-sm font-semibold text-[#52646a]">Batal</a>
            <button type="submit" class="rounded-lg bg-[#244e49] px-5 py-2 text-sm font-bold text-white hover:bg-[#17343d]">Simpan</button>
        </div>
    </form>
</div>
