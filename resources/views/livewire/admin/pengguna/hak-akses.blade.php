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

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Flash Messages ── --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs sm:text-sm text-emerald-800 shadow-sm flex items-center justify-between gap-3">
            <span class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </span>
            <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800 font-bold p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs sm:text-sm text-rose-800 shadow-sm flex items-center justify-between gap-3">
            <span class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ session('error') }}
            </span>
            <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-800 font-bold p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- ── Header Area ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('admin.pengguna.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:border-blue-200 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Konfigurasi Hak Akses</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 0121 9z"/></svg>
                    Hak Akses Pengguna
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">
                    Staf: <span class="font-bold text-slate-800">{{ $user->name }}</span> <span class="text-slate-400">({{ $user->email }})</span>
                </p>
            </div>
        </div>
    </div>

    {{-- ── Info Card ── --}}
    <div class="rounded-2xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm flex items-start gap-3.5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-800">Panduan Matriks Otorisasi</h3>
            <p class="text-xs font-medium text-slate-500 mt-1 leading-relaxed">
                Tandai kotak centang sesuai dengan wewenang yang ingin Anda berikan kepada staf. Anda dapat mengeklik <span class="font-bold text-slate-700">nama fitur/modul</span> untuk mengaktifkan atau menonaktifkan seluruh hak akses pada baris tersebut secara instan.
            </p>
        </div>
    </div>

    {{-- ── Matrix Table Container ── --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">

        {{-- Desktop Matrix Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 w-1/3">Fitur / Modul Aplikasi</th>
                        <th class="px-5 py-4 text-center">Lihat (Read)</th>
                        <th class="px-5 py-4 text-center">Tambah (Create)</th>
                        <th class="px-5 py-4 text-center">Edit (Update)</th>
                        <th class="px-5 py-4 text-center">Hapus (Delete)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                    @php $lastCategory = null; @endphp
                    @foreach($matrix as $key => $akses)
                        @if($akses['category'] !== $lastCategory)
                            <tr>
                                <td colspan="5" class="px-6 py-3 bg-slate-50/50 border-y border-slate-100">
                                    <span class="text-[10px] font-extrabold text-blue-600 uppercase tracking-widest flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                        {{ $akses['category'] }}
                                    </span>
                                </td>
                            </tr>
                            @php $lastCategory = $akses['category']; @endphp
                        @endif
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="px-6 py-4">
                                <button type="button" wire:click="tandaiSemua('{{ $key }}')" @click="playClick()"
                                        class="btn-sound font-bold text-slate-800 hover:text-blue-600 transition-colors text-left flex items-center gap-2 group">
                                    <svg class="w-3.5 h-3.5 text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>{{ $akses['label'] }}</span>
                                </button>
                            </td>

                            {{-- Checkboxes --}}
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.view" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.create" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.edit" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-blue-600 border-slate-300 focus:ring-2 focus:ring-blue-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                            <td class="px-5 py-4 text-center">
                                <input wire:model="matrix.{{ $key }}.delete" type="checkbox" @click="playClick()"
                                       class="btn-sound w-5 h-5 rounded-lg text-rose-600 border-slate-300 focus:ring-2 focus:ring-rose-500/20 cursor-pointer shadow-sm transition-all" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card Layout --}}
        <div class="sm:hidden divide-y divide-slate-100">
            @php $lastCategoryMobile = null; @endphp
            @foreach($matrix as $key => $akses)
                @if($akses['category'] !== $lastCategoryMobile)
                    <div class="px-5 py-3 bg-slate-50/70 border-y border-slate-100">
                        <span class="text-[10px] font-extrabold text-blue-600 uppercase tracking-widest flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            {{ $akses['category'] }}
                        </span>
                    </div>
                    @php $lastCategoryMobile = $akses['category']; @endphp
                @endif

                <div class="p-4 hover:bg-slate-50/50 transition-colors space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-extrabold text-slate-800 text-sm">{{ $akses['label'] }}</h3>
                        <button type="button" wire:click="tandaiSemua('{{ $key }}')" @click="playClick()"
                                class="btn-sound text-[10px] text-blue-600 font-bold px-2.5 py-1 bg-blue-50 border border-blue-100 rounded-lg uppercase tracking-wider">
                            Pilih Semua
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 bg-white cursor-pointer hover:border-blue-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.view" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded-md text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer" />
                            <span class="text-xs text-slate-700 font-bold">Lihat</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 bg-white cursor-pointer hover:border-blue-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.create" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded-md text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer" />
                            <span class="text-xs text-slate-700 font-bold">Tambah</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 bg-white cursor-pointer hover:border-blue-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.edit" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded-md text-blue-600 border-slate-300 focus:ring-blue-500/20 cursor-pointer" />
                            <span class="text-xs text-slate-700 font-bold">Edit</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 bg-white cursor-pointer hover:border-rose-200 transition-colors">
                            <input wire:model="matrix.{{ $key }}.delete" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded-md text-rose-600 border-slate-300 focus:ring-rose-500/20 cursor-pointer" />
                            <span class="text-xs text-slate-700 font-bold">Hapus</span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Action Footer --}}
        <div class="p-5 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50">
            <p class="text-xs text-slate-500 font-medium text-center sm:text-left flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Pengaturan hak akses staf akan langsung diterapkan setelah Anda menyimpan perubahan.
            </p>
            <button wire:click="simpan" @click="playSuccess()"
                    class="btn-sound w-full sm:w-auto px-6 py-3 rounded-2xl text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <span>Simpan Hak Akses</span>
            </button>
        </div>
    </div>
</div>
