<?php

use App\Models\Supplier;
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

    public function mount(): void {}

    public function getSuppliersProperty()
    {
        return Supplier::when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                                                         ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-suppliers');
        $s = Supplier::findOrFail($id);
        $s->delete();
        session()->flash('success', "Supplier '{$s->name}' berhasil dihapus.");
    }

    public function saveSupplier(): void
    {
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

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">
    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">Data Supplier</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $this->suppliers->total() }} supplier terdaftar</p>
        </div>
        <button wire:click="$set('showModal', true)" @click="playClick()"
           class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-sky-200/50 transition-all hover:opacity-90"
           style="background: linear-gradient(135deg, #0EA5E9, #0284C7);">
            <span>+</span><span class="hidden sm:inline">Tambah Supplier</span>
        </button>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Filter Bar ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari nama atau nomor..."
               class="flex-1 min-w-40 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400 transition-all shadow-sm" />
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @forelse($this->suppliers as $s)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-800 text-sm truncate">{{ $s->name }}</p>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $s->phone ?? '-' }}</p>
                </div>
            </div>
            <div class="flex items-center justify-between border-t border-slate-50 pt-3">
                <div>
                    <p class="text-[10px] text-slate-400">Deskripsi / Barang</p>
                    <p class="text-xs font-semibold text-slate-700 truncate max-w-[200px]">{{ $s->description ?? '-' }}</p>
                </div>
            </div>
            <div class="mt-3 flex justify-end gap-2">
                <button wire:click="openEdit({{ $s->id }})" @click="playClick()"
                        class="btn-sound px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-[10px] font-medium hover:bg-blue-100 transition-colors">Edit</button>
                <button wire:click="hapus({{ $s->id }})" wire:confirm="Hapus supplier '{{ $s->name }}'?"
                        @click="playDanger()"
                        class="btn-sound px-2.5 py-1 rounded-lg bg-red-50 text-red-500 text-[10px] font-medium hover:bg-red-100 transition-colors">Hapus</button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-sm">Belum ada data supplier.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Supplier</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor Kontak</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Alamat</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Deskripsi/Barang</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($this->suppliers as $s)
                <tr class="hover:bg-sky-50/30 transition-colors">
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $s->name }}</td>
                    <td class="px-5 py-4 text-slate-500 font-mono text-xs">{{ $s->phone ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-500 text-xs truncate max-w-[200px]" title="{{ $s->address }}">{{ $s->address ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-500 text-xs truncate max-w-[200px]" title="{{ $s->description }}">{{ $s->description ?? '-' }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button wire:click="openEdit({{ $s->id }})" @click="playClick()"
                                    class="btn-sound w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="hapus({{ $s->id }})" wire:confirm="Hapus supplier '{{ $s->name }}'?"
                                    @click="playDanger()"
                                    class="btn-sound w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center">
                        <p class="text-slate-400 text-sm">Belum ada data supplier.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->suppliers->links() }}</div>

    {{-- ── Modal: Tambah Supplier ──────────────────────────────────────────── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-data @click.outside="$wire.set('showModal', false)">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Supplier Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Supplier</label>
                    <input wire:model="newSupName" type="text" placeholder="Misal: PT Aneka Frozen" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    @error('newSupName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Kontak <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="newSupPhone" type="text" placeholder="Misal: 0812345678" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    @error('newSupPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Alamat <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="newSupAddress" rows="2" placeholder="Alamat pemasok" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi/Barang Bawaan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="newSupDesc" rows="2" placeholder="Misal: Khusus merk Fiesta & Champ" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="saveSupplier" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-md">Simpan Supplier</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Modal: Edit Supplier ────────────────────────────────────────────── --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-data @click.outside="$wire.set('showEditModal', false)">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Edit Supplier</h3>
                <button wire:click="$set('showEditModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Supplier</label>
                    <input wire:model="editSupName" type="text" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    @error('editSupName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Kontak <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="editSupPhone" type="text" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    @error('editSupPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Alamat <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="editSupAddress" rows="2" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi/Barang Bawaan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="editSupDesc" rows="2" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="updateSupplier"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-lg shadow-md"
                            style="background:linear-gradient(135deg,#0EA5E9,#0284C7);">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
