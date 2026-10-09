<?php

use App\Models\Supplier;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    // ── Modal: Tambah ─────────────────────────────
    public bool   $showModal   = false;
    public string $newSupName  = '';
    public string $newSupPhone = '';
    public string $newSupAddress = '';
    public string $newSupDesc    = '';

    // ── Modal: Edit ───────────────────────────────
    public bool   $showEditModal  = false;
    public int    $editId         = 0;
    public string $editSupName    = '';
    public string $editSupPhone   = '';
    public string $editSupAddress = '';
    public string $editSupDesc    = '';

    public function mount(): void
    {
        $this->authorize('view-supplier');
    }

    public function getSuppliersProperty()
    {
        return Supplier::when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                                                         ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-supplier');
        $s = Supplier::findOrFail($id);
        $s->delete();
        session()->flash('success', "Supplier '{$s->name}' berhasil dihapus.");
    }

    public function saveSupplier(): void
    {
        $this->authorize('create-supplier');
        $this->validate([
            'newSupName'    => 'required|string|max:255',
            'newSupPhone'   => 'nullable|string|max:20',
            'newSupAddress' => 'nullable|string',
            'newSupDesc'    => 'nullable|string',
        ]);
        Supplier::create([
            'name'        => $this->newSupName,
            'phone'       => $this->newSupPhone,
            'address'     => $this->newSupAddress,
            'description' => $this->newSupDesc,
        ]);
        $this->reset(['newSupName', 'newSupPhone', 'newSupAddress', 'newSupDesc']);
        $this->showModal = false;
        $this->resetPage();
        session()->flash('success', 'Supplier berhasil ditambahkan.');
    }

    public function openEdit(int $id): void
    {
        $this->authorize('edit-supplier');
        $s = Supplier::findOrFail($id);
        $this->editId         = $s->id;
        $this->editSupName    = $s->name;
        $this->editSupPhone   = $s->phone ?? '';
        $this->editSupAddress = $s->address ?? '';
        $this->editSupDesc    = $s->description ?? '';
        $this->showEditModal  = true;
    }

    public function updateSupplier(): void
    {
        $this->authorize('edit-supplier');
        $this->validate([
            'editSupName'    => 'required|string|max:255',
            'editSupPhone'   => 'nullable|string|max:20',
            'editSupAddress' => 'nullable|string',
            'editSupDesc'    => 'nullable|string',
        ]);
        Supplier::findOrFail($this->editId)->update([
            'name'        => $this->editSupName,
            'phone'       => $this->editSupPhone ?: null,
            'address'     => $this->editSupAddress ?: null,
            'description' => $this->editSupDesc ?: null,
        ]);
        $this->showEditModal = false;
        session()->flash('success', 'Data supplier berhasil diperbarui.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">
    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0V7m0 4h4m-4 0H7"/></svg>
                <span>Data Supplier</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">
                Mengelola {{ $this->suppliers->total() }} supplier dan mitra pemasok terdaftar
            </p>
        </div>

        <button wire:click="$set('showModal', true)" @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider text-white bg-sky-600 hover:bg-sky-700 shadow-md shadow-sky-600/20 transition-all self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Supplier</span>
        </button>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 text-xs sm:text-sm font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ── Filter Bar ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau nomor kontak supplier..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm" />
        </div>
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @forelse($this->suppliers as $s)
        <div class="bg-white rounded-3xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 text-sm truncate">{{ $s->name }}</p>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $s->phone ?? '-' }}</p>
                </div>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-100 text-xs">
                @if($s->address)
                <div class="flex items-start gap-1.5 text-slate-600">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="font-medium line-clamp-2">{{ $s->address }}</span>
                </div>
                @endif

                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100">
                    <p class="text-[10px] uppercase font-extrabold text-slate-400">Deskripsi / Produk</p>
                    <p class="text-xs font-semibold text-slate-700 mt-0.5 truncate">{{ $s->description ?? '-' }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button wire:click="openEdit({{ $s->id }})" @click="playClick()"
                        class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 text-xs font-bold hover:bg-blue-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Edit</span>
                </button>
                <button wire:click="hapus({{ $s->id }})" wire:confirm="Hapus supplier '{{ $s->name }}'?"
                        @click="playDanger()"
                        class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 text-xs font-bold hover:bg-rose-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-3xl border border-slate-200/80 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-xs font-medium">Belum ada data supplier.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3.5">Nama Supplier</th>
                    <th class="px-5 py-3.5">Nomor Kontak</th>
                    <th class="px-5 py-3.5">Alamat</th>
                    <th class="px-5 py-3.5">Deskripsi / Barang</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @forelse($this->suppliers as $s)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 font-bold text-slate-900">{{ $s->name }}</td>
                    <td class="px-5 py-4 text-slate-500 font-mono text-xs">{{ $s->phone ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-500 text-xs truncate max-w-[220px]" title="{{ $s->address }}">{{ $s->address ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-500 text-xs truncate max-w-[220px]" title="{{ $s->description }}">{{ $s->description ?? '-' }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <button wire:click="openEdit({{ $s->id }})" @click="playClick()" title="Edit Supplier"
                                    class="btn-sound w-8 h-8 rounded-xl bg-slate-50 hover:bg-blue-50 text-slate-400 hover:text-blue-600 border border-slate-200/60 hover:border-blue-100 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="hapus({{ $s->id }})" wire:confirm="Hapus supplier '{{ $s->name }}'?"
                                    @click="playDanger()" title="Hapus Supplier"
                                    class="btn-sound w-8 h-8 rounded-xl bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200/60 hover:border-rose-100 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-slate-400 text-xs font-medium">
                        Belum ada data supplier yang sesuai dengan pencarian.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->suppliers->links() }}</div>

    {{-- ── Modal: Tambah Supplier ──────────────────────────────────────────── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-5"
             x-data @click.outside="$wire.set('showModal', false)">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Tambah Supplier Baru</h3>
                <button wire:click="$set('showModal', false)" @click="playClick()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Supplier <span class="text-rose-500">*</span></label>
                    <input wire:model="newSupName" type="text" placeholder="Misal: PT Aneka Frozen Food" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm" />
                    @error('newSupName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nomor Kontak <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input wire:model="newSupPhone" type="text" placeholder="Misal: 081234567890" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm" />
                    @error('newSupPhone') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Alamat <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <textarea wire:model="newSupAddress" rows="2" placeholder="Alamat kantor atau gudang pemasok" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Deskripsi / Barang Bawaan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <textarea wire:model="newSupDesc" rows="2" placeholder="Misal: Distributor resmi merk Fiesta & Champ" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showModal', false)" @click="playClick()" class="btn-sound px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveSupplier" @click="playSuccess()" class="btn-sound px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-sky-600 hover:bg-sky-700 rounded-2xl shadow-md shadow-sky-600/20 transition-all">Simpan Supplier</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Modal: Edit Supplier ────────────────────────────────────────────── --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-5"
             x-data @click.outside="$wire.set('showEditModal', false)">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Data Supplier</h3>
                <button wire:click="$set('showEditModal', false)" @click="playClick()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Supplier <span class="text-rose-500">*</span></label>
                    <input wire:model="editSupName" type="text" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm" />
                    @error('editSupName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nomor Kontak <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input wire:model="editSupPhone" type="text" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm" />
                    @error('editSupPhone') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Alamat <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <textarea wire:model="editSupAddress" rows="2" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Deskripsi / Barang Bawaan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <textarea wire:model="editSupDesc" rows="2" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showEditModal', false)" @click="playClick()" class="btn-sound px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="updateSupplier" @click="playSuccess()" class="btn-sound px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-sky-600 hover:bg-sky-700 rounded-2xl shadow-md shadow-sky-600/20 transition-all">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
