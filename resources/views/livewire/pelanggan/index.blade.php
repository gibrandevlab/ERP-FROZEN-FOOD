<?php

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterType = '';

    // ── Modal: Tambah ────────────────────────────
    public bool $showModal = false;
    public string $newCustName = '';
    public string $newCustPhone = '';
    public string $newCustType = 'non_seller';

    // ── Modal: Edit ──────────────────────────────
    public bool   $showEditModal  = false;
    public int    $editId         = 0;
    public string $editCustName   = '';
    public string $editCustPhone  = '';
    public string $editCustType   = 'non_seller';

    public function mount(): void
    {
        $this->authorize('view-pelanggan');
    }

    public function getCustomersProperty()
    {
        return Customer::when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                                                          ->orWhere('phone', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->orderBy('name')
            ->paginate(15);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-pelanggan');
        $c = Customer::findOrFail($id);
        $c->delete();
        session()->flash('success', "Pelanggan '{$c->name}' berhasil dihapus.");
    }

    public function saveCustomer()
    {
        $this->authorize('create-pelanggan');
        $this->validate([
            'newCustName'  => 'required|string|max:255',
            'newCustPhone' => 'nullable|string|max:20',
            'newCustType'  => 'required|in:seller,non_seller',
        ]);
        Customer::create([
            'name'  => $this->newCustName,
            'phone' => $this->newCustPhone,
            'type'  => $this->newCustType,
        ]);
        $this->reset(['newCustName', 'newCustPhone']);
        $this->newCustType = 'non_seller';
        $this->showModal = false;
        $this->resetPage();
        session()->flash('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function openEdit(int $id): void
    {
        $this->authorize('edit-pelanggan');
        $c = Customer::findOrFail($id);
        $this->editId        = $c->id;
        $this->editCustName  = $c->name;
        $this->editCustPhone = $c->phone ?? '';
        $this->editCustType  = $c->type;
        $this->showEditModal = true;
    }

    public function updateCustomer(): void
    {
        $this->authorize('edit-pelanggan');
        $this->validate([
            'editCustName'  => 'required|string|max:255',
            'editCustPhone' => 'nullable|string|max:20',
            'editCustType'  => 'required|in:seller,non_seller',
        ]);
        Customer::findOrFail($this->editId)->update([
            'name'  => $this->editCustName,
            'phone' => $this->editCustPhone ?: null,
            'type'  => $this->editCustType,
        ]);
        $this->showEditModal = false;
        session()->flash('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function updatedSearch():     void { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">
    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">Data Pelanggan</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $this->customers->total() }} pelanggan terdaftar</p>
        </div>
        <button wire:click="$set('showModal', true)" @click="playClick()"
           class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-orange-200/50 transition-all hover:opacity-90"
           style="background: linear-gradient(135deg, #F97316, #EA580C);">
            <span>+</span><span class="hidden sm:inline">Tambah Pelanggan</span>
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
               class="flex-1 min-w-40 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/30 focus:border-orange-400 transition-all shadow-sm" />
        <select wire:model.live="filterType"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500/30 focus:border-orange-400 shadow-sm">
            <option value="">Semua Tipe</option>
            <option value="seller">Seller</option>
            <option value="non_seller">Non Seller (Umum)</option>
        </select>
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @forelse($this->customers as $c)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-800 text-sm truncate">{{ $c->name }}</p>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $c->phone ?? '-' }}</p>
                </div>
                <span class="flex-shrink-0 px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $c->type == 'seller' ? 'bg-purple-50 text-purple-600' : 'bg-slate-100 text-slate-500' }}">
                    {{ $c->type == 'seller' ? 'Seller' : 'Umum' }}
                </span>
            </div>
            <div class="flex items-center justify-between border-t border-slate-50 pt-3">
                <div>
                    <p class="text-[10px] text-slate-400">Total Dibeli</p>
                    <p class="text-xs font-bold text-slate-700">{{ $c->totalItemsBought() }} item</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-400">Keuntungan</p>
                    <p class="text-xs font-bold text-emerald-600">Rp {{ number_format($c->totalProfit(), 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="mt-3 flex justify-end gap-2">
                <button wire:click="openEdit({{ $c->id }})" @click="playClick()"
                        class="btn-sound px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-[10px] font-medium hover:bg-blue-100 transition-colors">Edit</button>
                <button wire:click="hapus({{ $c->id }})" wire:confirm="Hapus pelanggan '{{ $c->name }}'?"
                        @click="playDanger()"
                        class="btn-sound px-2.5 py-1 rounded-lg bg-red-50 text-red-500 text-[10px] font-medium hover:bg-red-100 transition-colors">Hapus</button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-sm">Belum ada data pelanggan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nama</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor HP</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Dibeli</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Keuntungan</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($this->customers as $c)
                <tr class="hover:bg-orange-50/30 transition-colors">
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $c->name }}</td>
                    <td class="px-5 py-4 text-slate-500 font-mono text-xs">{{ $c->phone ?? '-' }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $c->type == 'seller' ? 'bg-purple-50 text-purple-600' : 'bg-slate-100 text-slate-500' }}">
                            {{ $c->type == 'seller' ? 'Seller' : 'Umum' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right text-slate-700 font-medium">{{ $c->totalItemsBought() }} item</td>
                    <td class="px-5 py-4 text-right font-bold text-emerald-600">Rp {{ number_format($c->totalProfit(), 0, ',', '.') }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button wire:click="openEdit({{ $c->id }})" @click="playClick()"
                                    class="btn-sound w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="hapus({{ $c->id }})" wire:confirm="Hapus pelanggan '{{ $c->name }}'?"
                                    @click="playDanger()"
                                    class="btn-sound w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <p class="text-slate-400 text-sm">Belum ada data pelanggan.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->customers->links() }}</div>

    {{-- ── Modal: Tambah Pelanggan ─────────────────────────────────────────── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-data @click.outside="$wire.set('showModal', false)">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Pelanggan Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Pelanggan</label>
                    <input wire:model="newCustName" type="text" placeholder="Misal: Budi" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    @error('newCustName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Telepon <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="newCustPhone" type="text" placeholder="Misal: 0812345678" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    @error('newCustPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tipe Pelanggan</label>
                    <select wire:model="newCustType" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="saveCustomer" class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-md">Simpan Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Modal: Edit Pelanggan ───────────────────────────────────────────── --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-data @click.outside="$wire.set('showEditModal', false)">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Edit Pelanggan</h3>
                <button wire:click="$set('showEditModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Pelanggan</label>
                    <input wire:model="editCustName" type="text" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    @error('editCustName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Telepon <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="editCustPhone" type="text" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    @error('editCustPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tipe Pelanggan</label>
                    <select wire:model="editCustType" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="updateCustomer"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-lg shadow-md"
                            style="background:linear-gradient(135deg,#F97316,#EA580C);">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
