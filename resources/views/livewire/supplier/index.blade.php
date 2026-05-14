<?php

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    // Modal state
    public bool $showModal = false;
    public string $newSupName = '';
    public string $newSupPhone = '';
    public string $newSupAddress = '';
    public string $newSupDesc = '';

    public function mount(): void
    {
        // $this->authorize('view-products');
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
        // $this->authorize('delete-products');
        $s = Supplier::findOrFail($id);
        $s->delete();
        session()->flash('success', "Supplier '{$s->name}' berhasil dihapus.");
    }

    public function saveSupplier()
    {
        $this->validate([
            'newSupName' => 'required|string|max:255',
            'newSupPhone' => 'nullable|string|max:20',
            'newSupAddress' => 'nullable|string',
            'newSupDesc' => 'nullable|string',
        ]);
        
        Supplier::create([
            'name' => $this->newSupName,
            'phone' => $this->newSupPhone,
            'address' => $this->newSupAddress,
            'description' => $this->newSupDesc,
        ]);
        
        $this->reset(['newSupName', 'newSupPhone', 'newSupAddress', 'newSupDesc']);
        $this->showModal = false;
        $this->resetPage();
        session()->flash('success', 'Supplier berhasil ditambahkan.');
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
            <div class="mt-3 flex justify-end">
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
                        <button wire:click="hapus({{ $s->id }})" wire:confirm="Hapus supplier '{{ $s->name }}'?"
                                @click="playDanger()"
                                class="btn-sound px-2.5 py-1 rounded-lg bg-red-50 text-red-500 text-xs font-medium border border-red-100/60 hover:bg-red-100 transition-colors">Hapus</button>
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

    <div class="mt-4">
        {{ $this->suppliers->links() }}
    </div>

    {{-- Modal Tambah Supplier --}}
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
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Kontak <span class="text-slate-400 font-normal">(ops)</span></label>
                    <input wire:model="newSupPhone" type="text" placeholder="Misal: 0812345678" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    @error('newSupPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Alamat <span class="text-slate-400 font-normal">(ops)</span></label>
                    <textarea wire:model="newSupAddress" rows="2" placeholder="Alamat pemasok" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                    @error('newSupAddress') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi/Barang Bawaan <span class="text-slate-400 font-normal">(ops)</span></label>
                    <textarea wire:model="newSupDesc" rows="2" placeholder="Misal: Khusus merk Fiesta & Champ" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                    @error('newSupDesc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="saveSupplier" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-md">Simpan Supplier</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
