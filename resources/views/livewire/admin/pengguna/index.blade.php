<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public bool   $showForm = false;
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public bool   $is_admin = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->is_admin, 403, 'Halaman ini khusus admin.');
    }

    public function getPenggunaProperty()
    {
        return User::latest()->get();
    }

    public function simpanUser(): void
    {
        $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['boolean'],
        ]);

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'is_admin' => $this->is_admin,
        ]);

        if (! $user->is_admin) {
            session()->flash('success', "Pengguna '{$user->name}' berhasil ditambahkan. Silakan atur hak aksesnya di bawah ini.");
            $this->redirectRoute('admin.pengguna.hak-akses', ['id' => $user->id], navigate: true);
            return;
        }

        $nama = $this->name;
        $this->reset(['name', 'email', 'password', 'is_admin', 'showForm']);
        session()->flash('success', "Admin '{$nama}' berhasil ditambahkan.");
    }

    public function hapusUser(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'Tidak bisa menghapus akun sendiri.');
            return;
        }

        $user = User::findOrFail($id);
        $nama = $user->name;
        $user->delete();
        session()->flash('success', "Pengguna '{$nama}' berhasil dihapus.");
    }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- Flash Messages (for Livewire actions) --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="p-3 bg-green-50/90 border border-green-200 text-green-700 text-sm rounded-xl flex items-center justify-between shadow-sm">
            <span class="flex items-center gap-2"><span>✅</span> {{ session('success') }}</span>
            <button type="button" @click="show = false" class="text-green-400 hover:text-green-600 ml-3 text-lg leading-none">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="p-3 bg-red-50/90 border border-red-200 text-red-700 text-sm rounded-xl flex items-center justify-between shadow-sm">
            <span class="flex items-center gap-2"><span>❌</span> {{ session('error') }}</span>
            <button type="button" @click="show = false" class="text-red-400 hover:text-red-600 ml-3 text-lg leading-none">&times;</button>
        </div>
    @endif

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">Manajemen Pengguna</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $this->pengguna->count() }} pengguna terdaftar</p>
        </div>
        <button wire:click="$toggle('showForm')"
                @click="playClick()"
                class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150
                       {{ $showForm
                           ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                           : 'text-white shadow-lg shadow-blue-200/50 hover:opacity-90' }}"
                style="{{ $showForm ? '' : 'background: linear-gradient(135deg, #2563EB, #4F46E5);' }}">
            <span>{{ $showForm ? '✕' : '+' }}</span>
            <span class="hidden sm:inline">{{ $showForm ? 'Tutup' : 'Tambah Pengguna' }}</span>
        </button>
    </div>

    {{-- ── Form Tambah User ────────────────────────────────────── --}}
    @if($showForm)
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <div class="p-5 border-b border-slate-50"
             style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
            <h2 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs shadow">+</span>
                Tambah Pengguna Baru
            </h2>
        </div>
        <div class="p-5">
            <form wire:submit="simpanUser" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Nama</label>
                        <input wire:model="name" type="text" placeholder="Nama lengkap"
                               class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" />
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Email</label>
                        <input wire:model="email" type="email" placeholder="email@contoh.com"
                               class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" />
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Password</label>
                        <input wire:model="password" type="password" placeholder="Min 8 karakter"
                               class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" />
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50/50 border border-slate-200 cursor-pointer hover:border-blue-300 transition-colors mt-2">
                    <input wire:model="is_admin" id="is_admin_new" type="checkbox"
                           class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 mt-0.5 flex-shrink-0 shadow-sm" />
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Jadikan Admin</p>
                        <p class="text-xs text-slate-500 mt-0.5">Admin punya akses penuh ke semua fitur tanpa pembatasan</p>
                    </div>
                </label>

                <div class="flex items-center justify-end gap-3 pt-4 mt-2">
                    <button type="button" wire:click="$set('showForm', false)"
                            @click="playClick()"
                            class="btn-sound px-4 py-2 text-sm text-slate-500 hover:text-slate-700 transition-colors font-medium">
                        Batal
                    </button>
                    <button type="submit"
                            @click="playSuccess()"
                            class="btn-sound flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-sm font-semibold shadow-lg shadow-blue-200/50 hover:opacity-90 transition-all"
                            style="background: linear-gradient(135deg, #2563EB, #4F46E5);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Simpan & Atur Hak Akses
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Mobile: Card List ───────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @foreach($this->pengguna as $u)
        <div class="bg-white rounded-2xl border p-4 shadow-sm transition-all
                    {{ $u->id === auth()->id() ? 'border-blue-200/70' : 'border-slate-100' }}">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                    {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800 dark:text-white text-sm flex items-center gap-1.5 flex-wrap">
                        {{ $u->name }}
                        @if($u->id === auth()->id())
                            <span class="text-[10px] text-blue-500 font-medium bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded-full">kamu</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-400 truncate">{{ $u->email }}</p>
                </div>
                <span class="flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                             {{ $u->is_admin
                                 ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'
                                 : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                    {{ $u->is_admin ? '⚡ Admin' : 'Staf' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @unless($u->is_admin)
                <a href="{{ route('admin.pengguna.hak-akses', $u->id) }}" wire:navigate
                   @click="playClick()"
                   class="btn-sound flex-1 text-center px-3 py-2 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-xs font-semibold border border-blue-100 dark:border-blue-800/40 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    🔑 Atur Hak Akses
                </a>
                @endunless
                @if($u->id !== auth()->id())
                <button wire:click="hapusUser({{ $u->id }})"
                        wire:confirm="Hapus pengguna '{{ $u->name }}'? Semua hak aksesnya juga akan terhapus."
                        @click="playDanger()"
                        class="btn-sound flex-1 text-center px-3 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400 text-xs font-semibold border border-red-100 dark:border-red-800/40 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    🗑 Hapus
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Pengguna</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($this->pengguna as $u)
                <tr class="hover:bg-blue-50/30 transition-colors {{ $u->id === auth()->id() ? 'bg-blue-50/20' : '' }}">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0 shadow-sm">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 flex items-center gap-1.5">
                                    {{ $u->name }}
                                    @if($u->id === auth()->id())
                                        <span class="text-[10px] text-blue-500 bg-blue-50 px-1.5 py-0.5 rounded-full font-medium">kamu</span>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                     {{ $u->is_admin
                                         ? 'bg-purple-100 text-purple-700'
                                         : 'bg-slate-100 text-slate-500' }}">
                            {{ $u->is_admin ? '⚡ Admin' : 'Staf' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @unless($u->is_admin)
                            <a href="{{ route('admin.pengguna.hak-akses', $u->id) }}" wire:navigate
                               @click="playClick()"
                               class="btn-sound inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 text-xs font-semibold border border-blue-100/60 hover:bg-blue-100 transition-colors">
                                🔑 Atur Hak Akses
                            </a>
                            @endunless
                            @if($u->id !== auth()->id())
                            <button wire:click="hapusUser({{ $u->id }})"
                                    wire:confirm="Hapus pengguna '{{ $u->name }}'? Semua hak aksesnya juga akan terhapus."
                                    @click="playDanger()"
                                    class="btn-sound inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 text-red-500 text-xs font-semibold border border-red-100/60 hover:bg-red-100 transition-colors">
                                🗑 Hapus
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
