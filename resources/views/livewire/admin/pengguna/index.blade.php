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

<div class="space-y-6 max-w-4xl mx-auto lg:max-w-none">

    {{-- Flash Messages (Styled Toast style alerts) --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm rounded-2xl flex items-center justify-between shadow-md shadow-emerald-100/30">
            <span class="flex items-center gap-2.5 font-bold">
                <span class="text-emerald-500 text-base">🎉</span> 
                {{ session('success') }}
            </span>
            <button type="button" @click="show = false" class="text-emerald-400 hover:text-emerald-700 ml-3 text-lg leading-none font-bold">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm rounded-2xl flex items-center justify-between shadow-md shadow-rose-100/30">
            <span class="flex items-center gap-2.5 font-bold">
                <span class="text-rose-500 text-base">⚠️</span> 
                {{ session('error') }}
            </span>
            <button type="button" @click="show = false" class="text-rose-400 hover:text-rose-700 ml-3 text-lg leading-none font-bold">&times;</button>
        </div>
    @endif

    {{-- ── Header Area ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-800">👥 Manajemen Pengguna</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola staf operasional dan konfigurasi hak akses sistem.</p>
        </div>
        
        {{-- Premium Toggle Button --}}
        <button wire:click="$toggle('showForm')"
                @click="playClick()"
                class="btn-sound flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-xs sm:text-sm font-extrabold transition-all duration-300 shadow-md self-start sm:self-center
                       {{ $showForm
                           ? 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200 hover:text-slate-800 shadow-none'
                           : 'text-white bg-gradient-to-r from-blue-600 to-indigo-600 shadow-blue-200/50 hover:opacity-90 hover:shadow-lg' }}">
            <span class="text-base leading-none">{{ $showForm ? '✕' : '➕' }}</span>
            <span>{{ $showForm ? 'Tutup Form' : 'Tambah Pengguna' }}</span>
        </button>
    </div>

    {{-- ── Form Tambah User (Premium Styled Modal Card) ── --}}
    @if($showForm)
    <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0"
         class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-xl"
         style="box-shadow: 0 10px 50px rgba(0,0,0,0.04), 0 2px 8px rgba(0,0,0,0.02);">
        
        {{-- Form Header --}}
        <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs shadow-md font-bold">+</span>
                TAMBAH PENGGUNA BARU
            </h2>
            <button wire:click="$set('showForm', false)" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>
        
        {{-- Form Content --}}
        <div class="p-6">
            <form wire:submit="simpanUser" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Nama Lengkap</label>
                        <input wire:model="name" type="text" placeholder="Masukkan nama staf..."
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                        @error('name') <p class="text-rose-500 text-xs mt-1.5 font-bold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Alamat Email</label>
                        <input wire:model="email" type="email" placeholder="contoh@domain.com"
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                        @error('email') <p class="text-rose-500 text-xs mt-1.5 font-bold">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Kata Sandi (Password)</label>
                        <input wire:model="password" type="password" placeholder="Minimal 8 karakter unik..."
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                        @error('password') <p class="text-rose-500 text-xs mt-1.5 font-bold">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Admin Switch Container --}}
                <label class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50/50 border border-slate-200 hover:border-indigo-300 transition-colors duration-300 cursor-pointer mt-2">
                    <input wire:model="is_admin" id="is_admin_new" type="checkbox"
                           class="w-5 h-5 rounded-lg text-indigo-600 border-slate-300 focus:ring-indigo-500/20 mt-0.5 flex-shrink-0 shadow-sm cursor-pointer" />
                    <div>
                        <p class="text-sm font-extrabold text-slate-700 flex items-center gap-1.5">
                            ⚡ Jadikan Akun Admin
                        </p>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Admin memiliki hak akses penuh ke seluruh modul sistem (Keuangan, SPK, Stok, Kategori, Pelanggan, dan Supplier) tanpa batas.
                        </p>
                    </div>
                </label>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100 mt-3">
                    <button type="button" wire:click="$set('showForm', false)"
                            @click="playClick()"
                            class="btn-sound px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors uppercase tracking-wider">
                        Batal
                    </button>
                    <button type="submit"
                            @click="playSuccess()"
                            class="btn-sound flex items-center gap-2 px-6 py-3 text-white rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg shadow-blue-200/50 hover:opacity-95 hover:shadow-xl transition-all"
                            style="background: linear-gradient(135deg, #1D4ED8, #4F46E5);">
                        <span>💾</span>
                        <span>Simpan & Atur Hak Akses</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Mobile Layout: Card List (Responsive) ── --}}
    <div class="space-y-4 sm:hidden">
        @foreach($this->pengguna as $u)
        <div class="bg-white rounded-2xl border p-5 shadow-sm relative overflow-hidden transition-all duration-300
                    {{ $u->id === auth()->id() ? 'border-blue-200 bg-blue-50/10' : 'border-slate-100' }}">
            
            {{-- Top Row --}}
            <div class="flex items-center gap-3.5 mb-4">
                {{-- Initial Circle with Gradient --}}
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-md">
                    {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-extrabold text-slate-800 text-sm sm:text-base flex items-center gap-1.5 flex-wrap">
                        {{ $u->name }}
                        @if($u->id === auth()->id())
                            <span class="text-[9px] font-black uppercase tracking-wider text-blue-600 bg-blue-100 border border-blue-200 px-2 py-0.5 rounded-full">kamu</span>
                        @endif
                    </h3>
                    <p class="text-xs text-slate-500 font-medium truncate mt-0.5">{{ $u->email }}</p>
                </div>
                
                {{-- Badge Status --}}
                <span class="flex-shrink-0 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider
                             {{ $u->is_admin
                                 ? 'bg-purple-100 text-purple-700 border border-purple-200'
                                 : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                    {{ $u->is_admin ? '⚡ Admin' : '👤 Staf' }}
                </span>
            </div>
            
            {{-- Bottom Buttons --}}
            <div class="flex items-center gap-2 pt-3 border-t border-slate-50">
                @unless($u->is_admin)
                <a href="{{ route('admin.pengguna.hak-akses', $u->id) }}" wire:navigate
                   @click="playClick()"
                   class="btn-sound flex-1 text-center px-3.5 py-2.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100 hover:bg-blue-100 transition-all flex items-center justify-center gap-1.5">
                    <span>🔑</span>
                    <span>Hak Akses</span>
                </a>
                @endunless
                
                @if($u->id !== auth()->id())
                <button wire:click="hapusUser({{ $u->id }})"
                        wire:confirm="Hapus pengguna '{{ $u->name }}'? Seluruh data dan hak aksesnya juga akan terhapus."
                        @click="playDanger()"
                        class="btn-sound flex-1 text-center px-3.5 py-2.5 rounded-xl bg-rose-50 text-rose-600 text-xs font-bold border border-rose-100 hover:bg-rose-100 transition-all flex items-center justify-center gap-1.5">
                    <span>🗑</span>
                    <span>Hapus</span>
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Desktop Layout: Premium Table ── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-lg shadow-slate-100/40">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Detail Akun Pengguna</th>
                    <th class="px-6 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">Level Hak Akses</th>
                    <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-wider">Manajemen Akses & Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($this->pengguna as $u)
                <tr class="hover:bg-blue-50/10 transition-colors duration-200 {{ $u->id === auth()->id() ? 'bg-blue-50/5' : '' }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-4">
                            {{-- Circular avatar with premium gradient --}}
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-md">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-800 text-sm sm:text-base flex items-center gap-1.5">
                                    {{ $u->name }}
                                    @if($u->id === auth()->id())
                                        <span class="text-[9px] font-black uppercase tracking-wider text-blue-600 bg-blue-100 border border-blue-200 px-1.5 py-0.5 rounded-full">kamu</span>
                                    @endif
                                </h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider
                                     {{ $u->is_admin
                                         ? 'bg-purple-50 text-purple-700 border border-purple-100'
                                         : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            {{ $u->is_admin ? '⚡ Admin' : '👤 Staf' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2.5">
                            @unless($u->is_admin)
                            <a href="{{ route('admin.pengguna.hak-akses', $u->id) }}" wire:navigate
                               @click="playClick()"
                               class="btn-sound inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100/60 hover:bg-blue-100 transition-all shadow-sm">
                                <span>🔑</span>
                                <span>Atur Hak Akses</span>
                            </a>
                            @endunless
                            
                            @if($u->id !== auth()->id())
                            <button wire:click="hapusUser({{ $u->id }})"
                                    wire:confirm="Hapus pengguna '{{ $u->name }}'? Seluruh data dan hak aksesnya juga akan terhapus."
                                    @click="playDanger()"
                                    class="btn-sound inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 text-rose-600 text-xs font-bold border border-rose-100/60 hover:bg-rose-100 transition-all shadow-sm">
                                <span>🗑</span>
                                <span>Hapus</span>
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

