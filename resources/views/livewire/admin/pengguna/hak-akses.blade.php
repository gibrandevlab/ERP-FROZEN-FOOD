<?php

use App\Models\{User, Permission, UserPermission};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public User  $user;
    public array $matrix = [];

    public function mount(int $id): void
    {
        abort_unless(auth()->user()->is_admin, 403, 'Halaman ini khusus admin.');

        $this->user    = User::findOrFail($id);
        $permissions   = Permission::orderBy('category')->orderBy('label')->get();
        $userPerms     = UserPermission::where('user_id', $this->user->id)->get()->keyBy('permission_id');

        foreach ($permissions as $perm) {
            $existing = $userPerms->get($perm->id);
            $this->matrix[$perm->key] = [
                'label'    => $perm->label,
                'category' => $perm->category,
                'view'     => (bool) ($existing?->can_view   ?? false),
                'create'   => (bool) ($existing?->can_create ?? false),
                'edit'     => (bool) ($existing?->can_edit   ?? false),
                'delete'   => (bool) ($existing?->can_delete ?? false),
            ];
        }
    }

    public function tandaiSemua(string $key): void
    {
        abort_unless(auth()->user()->is_admin, 403);
        $semua = ! ($this->matrix[$key]['view'] && $this->matrix[$key]['create'] && $this->matrix[$key]['edit'] && $this->matrix[$key]['delete']);
        $this->matrix[$key] = array_merge($this->matrix[$key], [
            'view' => $semua, 'create' => $semua, 'edit' => $semua, 'delete' => $semua,
        ]);
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()->is_admin, 403);
        foreach ($this->matrix as $key => $akses) {
            $perm = Permission::where('key', $key)->first();
            if (! $perm) continue;
            UserPermission::updateOrCreate(
                ['user_id' => $this->user->id, 'permission_id' => $perm->id],
                ['can_view' => $akses['view'], 'can_create' => $akses['create'], 'can_edit' => $akses['edit'], 'can_delete' => $akses['delete']]
            );
        }
        session()->flash('success', "Hak akses untuk {$this->user->name} berhasil disimpan.");
    }
}; ?>

<div class="space-y-6 max-w-4xl mx-auto lg:max-w-none">

    {{-- Flash Messages --}}
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
    <div class="flex items-center gap-3.5 border-b border-slate-100 pb-4">
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate @click="playClick()"
           class="btn-sound w-11 h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50 hover:border-slate-300 shadow-sm transition-all shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-600 border border-blue-100 rounded-md">Konfigurasi Hak Akses</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-800 mt-1">🔑 Pengaturan Hak Akses</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Staf: <span class="font-extrabold text-slate-700">{{ $user->name }}</span> ({{ $user->email }})
            </p>
        </div>
    </div>

    {{-- ── Info Card (Gradient Blue Tint) ── --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50/50 border border-blue-100 rounded-3xl p-4 sm:p-5 flex gap-4 shadow-sm">
        <div class="w-10 h-10 rounded-2xl bg-blue-100/80 flex items-center justify-center shrink-0 text-xl shadow-inner">
            💡
        </div>
        <div>
            <h3 class="text-xs sm:text-sm font-extrabold text-blue-900 uppercase tracking-wide">Panduan Matriks Otorisasi</h3>
            <p class="text-xs text-blue-700/95 mt-1 leading-relaxed">
                Tandai kotak centang sesuai dengan wewenang yang ingin Anda berikan kepada staf. Anda dapat mengeklik **nama fitur/modul** untuk mengaktifkan atau menonaktifkan seluruh hak akses di baris tersebut secara instan.
            </p>
        </div>
    </div>

    {{-- ── Matrix Table Container ── --}}
    <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-lg shadow-slate-100/40"
         style="box-shadow: 0 10px 50px rgba(0,0,0,0.02), 0 2px 8px rgba(0,0,0,0.01);">
        
        {{-- Desktop Matrix Table --}}
        <div class="hidden sm:block">
            <table class="w-full text-sm">
                <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                    <tr class="border-b border-slate-100">
                        <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider w-1/3">Fitur / Modul Aplikasi</th>
                        <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">Lihat (Read)</th>
                        <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">Tambah (Create)</th>
                        <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">Edit (Update)</th>
                        <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase tracking-wider">Hapus (Delete)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @php $lastCategory = null; @endphp
                    @foreach($matrix as $key => $akses)
                        @if($akses['category'] !== $lastCategory)
                            <tr>
                                <td colspan="5" class="px-6 py-3 bg-slate-50/70 border-y border-slate-100">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                        📁 {{ $akses['category'] }}
                                    </span>
                                </td>
                            </tr>
                            @php $lastCategory = $akses['category']; @endphp
                        @endif
                        <tr class="hover:bg-blue-50/20 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <button type="button" wire:click="tandaiSemua('{{ $key }}')" @click="playClick()"
                                        class="btn-sound font-extrabold text-slate-700 hover:text-blue-600 transition-colors text-left w-full flex items-center gap-2 group">
                                    <span class="opacity-0 group-hover:opacity-100 text-[10px] text-blue-500 transition-opacity">⚡</span>
                                    <span>{{ $akses['label'] }}</span>
                                </button>
                            </td>
                            
                            {{-- Checkbox Cells with Subtle Glow effects --}}
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.view" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.create" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.edit" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.delete" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card Layout (Responsive Checkbox Grid) --}}
        <div class="sm:hidden divide-y divide-slate-100">
            @php $lastCategoryMobile = null; @endphp
            @foreach($matrix as $key => $akses)
                @if($akses['category'] !== $lastCategoryMobile)
                    <div class="px-5 py-3.5 bg-slate-50/70 border-y border-slate-100">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1">📁 {{ $akses['category'] }}</span>
                    </div>
                    @php $lastCategoryMobile = $akses['category']; @endphp
                @endif
                
                <div class="p-5 hover:bg-blue-50/10 transition-all duration-150">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-extrabold text-slate-800 text-sm">{{ $akses['label'] }}</h3>
                        <button type="button" wire:click="tandaiSemua('{{ $key }}')" @click="playClick()"
                                class="btn-sound text-[10px] text-blue-600 font-extrabold px-2.5 py-1 bg-blue-50 border border-blue-100 rounded-lg uppercase tracking-wider">
                            Pilih Semua
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 bg-slate-50/30 cursor-pointer hover:border-blue-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.view" type="checkbox" @click="playClick()" class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 shadow-sm cursor-pointer" />
                            <span class="text-xs text-slate-600 font-bold">Lihat</span>
                        </label>
                        <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 bg-slate-50/30 cursor-pointer hover:border-blue-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.create" type="checkbox" @click="playClick()" class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 shadow-sm cursor-pointer" />
                            <span class="text-xs text-slate-600 font-bold">Tambah</span>
                        </label>
                        <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 bg-slate-50/30 cursor-pointer hover:border-blue-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.edit" type="checkbox" @click="playClick()" class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-blue-500/20 shadow-sm cursor-pointer" />
                            <span class="text-xs text-slate-600 font-bold">Edit</span>
                        </label>
                        <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 bg-slate-50/30 cursor-pointer hover:border-red-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.delete" type="checkbox" @click="playClick()" class="btn-sound w-5 h-5 rounded-lg text-rose-500 border-slate-300 focus:ring-rose-500/20 shadow-sm cursor-pointer" />
                            <span class="text-xs text-slate-600 font-bold">Hapus</span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Action Footer --}}
        <div class="px-6 py-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gradient-to-r from-slate-50 to-slate-100/50">
            <p class="text-xs text-slate-500 font-medium text-center sm:text-left">
                ⚠️ Pengaturan hak akses staf akan langsung diterapkan setelah Anda menyimpan perubahan.
            </p>
            <button wire:click="simpan" @click="playSuccess()"
                    class="btn-sound w-full sm:w-auto px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-blue-200/50 hover:opacity-95 hover:shadow-xl transition-all"
                    style="background: linear-gradient(135deg, #1D4ED8, #4F46E5);">
                💾 Simpan Hak Akses
            </button>
        </div>
    </div>
</div>

