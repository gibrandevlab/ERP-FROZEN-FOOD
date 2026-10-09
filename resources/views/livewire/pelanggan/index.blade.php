<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
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

<div class="space-y-6 max-w-5xl mx-auto">
    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Data Pelanggan</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">
                Mengelola {{ $this->customers->total() }} pelanggan terdaftar dalam sistem
            </p>
        </div>

        <button wire:click="$set('showModal', true)" @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider text-white bg-orange-600 hover:bg-orange-700 shadow-md shadow-orange-600/20 transition-all self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Pelanggan</span>
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
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau nomor HP pelanggan..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm" />
        </div>

        <div class="w-full sm:w-48">
            <select wire:model.live="filterType"
                    class="w-full px-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm">
                <option value="">Semua Tipe</option>
                <option value="seller">Seller</option>
                <option value="non_seller">Non Seller (Umum)</option>
            </select>
        </div>
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @forelse($this->customers as $c)
        <div class="bg-white rounded-3xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 text-sm truncate">{{ $c->name }}</p>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $c->phone ?? '-' }}</p>
                </div>
                <span class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $c->type == 'seller' ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                    {{ $c->type == 'seller' ? 'Seller' : 'Umum' }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100">
                    <p class="text-[10px] uppercase font-extrabold text-slate-400">Total Dibeli</p>
                    <p class="text-xs font-extrabold text-slate-800 mt-0.5">{{ $c->totalItemsBought() }} item</p>
                </div>
                <div class="bg-emerald-50/50 p-2.5 rounded-2xl border border-emerald-100/60">
                    <p class="text-[10px] uppercase font-extrabold text-emerald-600">Keuntungan</p>
                    <p class="text-xs font-extrabold text-emerald-700 mt-0.5">Rp {{ number_format($c->totalProfit(), 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button wire:click="openEdit({{ $c->id }})" @click="playClick()"
                        class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 text-xs font-bold hover:bg-blue-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Edit</span>
                </button>
                <button wire:click="hapus({{ $c->id }})" wire:confirm="Hapus pelanggan '{{ $c->name }}'?"
                        @click="playDanger()"
                        class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 text-xs font-bold hover:bg-rose-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-3xl border border-slate-200/80 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-xs font-medium">Belum ada data pelanggan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Nomor HP</th>
                    <th class="px-5 py-3.5 text-center">Tipe</th>
                    <th class="px-5 py-3.5 text-right">Total Dibeli</th>
                    <th class="px-5 py-3.5 text-right">Keuntungan</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @forelse($this->customers as $c)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 font-bold text-slate-900">{{ $c->name }}</td>
                    <td class="px-5 py-4 text-slate-500 font-mono text-xs">{{ $c->phone ?? '-' }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $c->type == 'seller' ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            {{ $c->type == 'seller' ? 'Seller' : 'Umum' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right text-slate-800 font-bold">{{ $c->totalItemsBought() }} item</td>
                    <td class="px-5 py-4 text-right font-extrabold text-emerald-600">Rp {{ number_format($c->totalProfit(), 0, ',', '.') }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <button wire:click="openEdit({{ $c->id }})" @click="playClick()" title="Edit Pelanggan"
                                    class="btn-sound w-8 h-8 rounded-xl bg-slate-50 hover:bg-blue-50 text-slate-400 hover:text-blue-600 border border-slate-200/60 hover:border-blue-100 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="hapus({{ $c->id }})" wire:confirm="Hapus pelanggan '{{ $c->name }}'?"
                                    @click="playDanger()" title="Hapus Pelanggan"
                                    class="btn-sound w-8 h-8 rounded-xl bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200/60 hover:border-rose-100 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-slate-400 text-xs font-medium">
                        Belum ada data pelanggan yang sesuai dengan pencarian.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->customers->links() }}</div>

    {{-- ── Modal: Tambah Pelanggan ─────────────────────────────────────────── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-5"
             x-data @click.outside="$wire.set('showModal', false)">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Tambah Pelanggan Baru</h3>
                <button wire:click="$set('showModal', false)" @click="playClick()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Pelanggan <span class="text-rose-500">*</span></label>
                    <input wire:model="newCustName" type="text" placeholder="Misal: Budi Santoso" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm" />
                    @error('newCustName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nomor Telepon <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input wire:model="newCustPhone" type="text" placeholder="Misal: 081234567890" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm" />
                    @error('newCustPhone') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Tipe Pelanggan <span class="text-rose-500">*</span></label>
                    <select wire:model="newCustType" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showModal', false)" @click="playClick()" class="btn-sound px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveCustomer" @click="playSuccess()" class="btn-sound px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-orange-600 hover:bg-orange-700 rounded-2xl shadow-md shadow-orange-600/20 transition-all">Simpan Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Modal: Edit Pelanggan ───────────────────────────────────────────── --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-5"
             x-data @click.outside="$wire.set('showEditModal', false)">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Data Pelanggan</h3>
                <button wire:click="$set('showEditModal', false)" @click="playClick()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Pelanggan <span class="text-rose-500">*</span></label>
                    <input wire:model="editCustName" type="text" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm" />
                    @error('editCustName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nomor Telepon <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input wire:model="editCustPhone" type="text" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm" />
                    @error('editCustPhone') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Tipe Pelanggan <span class="text-rose-500">*</span></label>
                    <select wire:model="editCustType" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all shadow-sm">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showEditModal', false)" @click="playClick()" class="btn-sound px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="updateCustomer" @click="playSuccess()" class="btn-sound px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-orange-600 hover:bg-orange-700 rounded-2xl shadow-md shadow-orange-600/20 transition-all">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
