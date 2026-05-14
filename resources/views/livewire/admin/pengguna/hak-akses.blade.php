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

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate @click="playClick()"
           class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">Hak Akses</h1>
            <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $user->name }} — {{ $user->email }}</p>
        </div>
    </div>

    {{-- ── Info Card ──────────────────────────────────────────────────────── --}}
    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex gap-3 shadow-sm">
        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
            <span class="text-blue-600 text-lg leading-none">💡</span>
        </div>
        <div>
            <p class="text-sm font-semibold text-blue-900">Panduan Akses</p>
            <p class="text-xs text-blue-700 mt-0.5 leading-relaxed">Centang fitur yang boleh diakses pengguna. Klik nama fitur untuk langsung mencentang atau menghapus semua akses di baris tersebut.</p>
        </div>
    </div>

    {{-- ── Matrix Table ────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Fitur / Modul</th>
                    <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Lihat</th>
                    <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Tambah</th>
                    <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Edit</th>
                    <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Hapus</th>
                    {{-- Mobile view: TTEH (Tambah/Edit/Hapus) disingkat --}}
                    <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider sm:hidden">Aksi (T/E/H)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @php $lastCategory = null; @endphp
                @foreach($matrix as $key => $akses)
                    @if($akses['category'] !== $lastCategory)
                        <tr>
                            <td colspan="6" class="px-5 py-2.5 bg-slate-50/50">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $akses['category'] }}</span>
                            </td>
                        </tr>
                        @php $lastCategory = $akses['category']; @endphp
                    @endif
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="px-5 py-4">
                            <button type="button" wire:click="tandaiSemua('{{ $key }}')" @click="playClick()"
                                    class="btn-sound font-semibold text-slate-800 hover:text-blue-600 transition-colors text-left w-full">
                                {{ $akses['label'] }}
                            </button>
                        </td>
                        {{-- Desktop: Full columns --}}
                        <td class="px-4 py-4 text-center">
                            <input wire:model="matrix.{{ $key }}.view" type="checkbox" @click="playClick()"
                                   class="btn-sound w-5 h-5 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 cursor-pointer shadow-sm" />
                        </td>
                        <td class="px-4 py-4 text-center hidden sm:table-cell">
                            <input wire:model="matrix.{{ $key }}.create" type="checkbox" @click="playClick()"
                                   class="btn-sound w-5 h-5 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 cursor-pointer shadow-sm" />
                        </td>
                        <td class="px-4 py-4 text-center hidden sm:table-cell">
                            <input wire:model="matrix.{{ $key }}.edit" type="checkbox" @click="playClick()"
                                   class="btn-sound w-5 h-5 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 cursor-pointer shadow-sm" />
                        </td>
                        <td class="px-4 py-4 text-center hidden sm:table-cell">
                            <input wire:model="matrix.{{ $key }}.delete" type="checkbox" @click="playClick()"
                                   class="btn-sound w-5 h-5 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 cursor-pointer shadow-sm" />
                        </td>
                        {{-- Mobile: Condensed actions --}}
                        <td class="px-4 py-4 text-center sm:hidden">
                            <div class="flex items-center justify-center gap-2">
                                <input wire:model="matrix.{{ $key }}.create" title="Tambah" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 cursor-pointer" />
                                <input wire:model="matrix.{{ $key }}.edit" title="Edit" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500/30 cursor-pointer" />
                                <input wire:model="matrix.{{ $key }}.delete" title="Hapus" type="checkbox" @click="playClick()" class="btn-sound w-4 h-4 rounded text-red-500 border-slate-300 focus:ring-red-500/30 cursor-pointer" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Action Footer --}}
        <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50">
            <p class="text-xs text-slate-500">Akses akan langsung berubah setelah disimpan.</p>
            <button wire:click="simpan" @click="playSuccess()"
                    class="btn-sound w-full sm:w-auto px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-blue-200/50 transition-all hover:opacity-90"
                    style="background: linear-gradient(135deg, #2563EB, #4F46E5);">
                Simpan Hak Akses
            </button>
        </div>
    </div>
</div>
