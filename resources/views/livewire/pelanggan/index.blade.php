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
            'newCustPhone' => 'nullable|string|max:255',
            'newCustType'  => 'required|in:seller,non_seller',
        ]);
        Customer::create([
            'name'  => $this->newCustName,
            'phone' => $this->newCustPhone,
            'type'  => $this->newCustType,
            'user_id' => auth()->id(),
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
            'editCustPhone' => 'nullable|string|max:255',
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
            <h1 class="text-xl sm:text-2xl font-extrabold text-[#172c35] tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-[#5e4820] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Data Pelanggan</span>
            </h1>
            <p class="text-xs sm:text-sm text-[#64747a] font-medium mt-0.5">
                Mengelola {{ $this->customers->total() }} pelanggan terdaftar dalam sistem
            </p>
        </div>

        <button wire:click="$set('showModal', true)" @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider text-white bg-[#6b5226] hover:bg-[#6b5226] shadow-none  transition-all self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Pelanggan</span>
        </button>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="p-4 bg-[#e8efeb] text-[#2f6b5c] rounded-lg border border-[#b7d4ce] text-xs sm:text-sm font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-[#2f6b5c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ── Filter Bar ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#71817f]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau nomor HP pelanggan..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none" />
        </div>

        <div class="w-full sm:w-48">
            <select wire:model.live="filterType"
                    class="w-full px-4 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none">
                <option value="">Semua Tipe</option>
                <option value="seller">Seller</option>
                <option value="non_seller">Non Seller (Umum)</option>
            </select>
        </div>
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @forelse($this->customers as $c)
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-4 shadow-none space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-extrabold text-[#172c35] text-sm truncate">{{ $c->name }}</p>
                    <p class="text-xs text-[#71817f] font-mono mt-0.5">{{ $c->phone ?? '-' }}</p>
                </div>
                <span class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $c->type == 'seller' ? 'bg-[#e8efeb] text-[#315b50] border border-[#b7d4ce]' : 'bg-[#eef2ee] text-[#52646a] border border-[#dce1de]' }}">
                    {{ $c->type == 'seller' ? 'Seller' : 'Umum' }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#e6eae7] text-xs">
                <div class="bg-[#f5f6f3] p-2.5 rounded-lg border border-[#e6eae7]">
                    <p class="text-[10px] uppercase font-extrabold text-[#71817f]">Total Dibeli</p>
                    <p class="text-xs font-extrabold text-[#344950] mt-0.5">{{ $c->totalItemsBought() }} item</p>
                </div>
                <div class="bg-[#e8efeb]/50 p-2.5 rounded-lg border border-[#b7d4ce]/60">
                    <p class="text-[10px] uppercase font-extrabold text-[#2f6b5c]">Keuntungan</p>
                    <p class="text-xs font-extrabold text-[#2f6b5c] mt-0.5">Rp {{ number_format($c->totalProfit(), 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button wire:click="openEdit({{ $c->id }})" @click="playClick()"
                        class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#f5f6f3] text-[#315b50] border border-[#dce1de] text-xs font-bold hover:bg-[#e8efeb] transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Edit</span>
                </button>
                <button wire:click="hapus({{ $c->id }})" wire:confirm="Hapus pelanggan '{{ $c->name }}'?"
                        @click="playDanger()"
                        class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#f7eceb] text-[#9c5252] border border-[#ead6d4] text-xs font-bold hover:bg-[#f7eceb] transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-8 text-center shadow-none">
            <p class="text-[#71817f] text-xs font-medium">Belum ada data pelanggan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#f5f6f3]/80 border-b border-[#dce1de]/80 text-[10px] font-extrabold uppercase tracking-wider text-[#64747a]">
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Nomor HP</th>
                    <th class="px-5 py-3.5 text-center">Tipe</th>
                    <th class="px-5 py-3.5 text-right">Total Dibeli</th>
                    <th class="px-5 py-3.5 text-right">Keuntungan</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y border-[#e6eae7] text-xs sm:text-sm font-medium text-[#344950]">
                @forelse($this->customers as $c)
                <tr class="hover:bg-[#f5f6f3]/50 transition-colors">
                    <td class="px-5 py-4 font-bold text-[#172c35]">{{ $c->name }}</td>
                    <td class="px-5 py-4 text-[#64747a] font-mono text-xs">{{ $c->phone ?? '-' }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $c->type == 'seller' ? 'bg-[#e8efeb] text-[#315b50] border border-[#b7d4ce]' : 'bg-[#eef2ee] text-[#52646a] border border-[#dce1de]' }}">
                            {{ $c->type == 'seller' ? 'Seller' : 'Umum' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right text-[#344950] font-bold">{{ $c->totalItemsBought() }} item</td>
                    <td class="px-5 py-4 text-right font-extrabold text-[#2f6b5c]">Rp {{ number_format($c->totalProfit(), 0, ',', '.') }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <button wire:click="openEdit({{ $c->id }})" @click="playClick()" title="Edit Pelanggan"
                                    class="btn-sound w-8 h-8 rounded-lg bg-[#f5f6f3] hover:bg-[#f5f6f3] text-[#71817f] hover:text-[#315b50] border border-[#dce1de]/60 hover:border-[#dce1de] flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="hapus({{ $c->id }})" wire:confirm="Hapus pelanggan '{{ $c->name }}'?"
                                    @click="playDanger()" title="Hapus Pelanggan"
                                    class="btn-sound w-8 h-8 rounded-lg bg-[#f5f6f3] hover:bg-[#f7eceb] text-[#71817f] hover:text-[#9c5252] border border-[#dce1de]/60 hover:border-[#ead6d4] flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-[#71817f] text-xs font-medium">
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-none p-6 space-y-5"
             x-data @click.outside="$wire.set('showModal', false)">
            <div class="flex items-center justify-between border-b border-[#e6eae7] pb-3">
                <h3 class="text-base font-extrabold text-[#172c35]">Tambah Pelanggan Baru</h3>
                <button wire:click="$set('showModal', false)" @click="playClick()" class="text-[#71817f] hover:text-[#52646a] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Pelanggan <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="newCustName" type="text" placeholder="Misal: Budi Santoso" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none" />
                    @error('newCustName') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nomor Telepon <span class="text-[#71817f] font-normal lowercase">(opsional)</span></label>
                    <input wire:model="newCustPhone" type="text" placeholder="Misal: 081234567890" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none" />
                    @error('newCustPhone') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Tipe Pelanggan <span class="text-[#9c5252]">*</span></label>
                    <select wire:model="newCustType" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                    <button type="button" wire:click="$set('showModal', false)" @click="playClick()" class="btn-sound px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveCustomer" @click="playSuccess()" class="btn-sound px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#6b5226] hover:bg-[#6b5226] rounded-lg shadow-none  transition-all">Simpan Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Modal: Edit Pelanggan ───────────────────────────────────────────── --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-none p-6 space-y-5"
             x-data @click.outside="$wire.set('showEditModal', false)">
            <div class="flex items-center justify-between border-b border-[#e6eae7] pb-3">
                <h3 class="text-base font-extrabold text-[#172c35]">Edit Data Pelanggan</h3>
                <button wire:click="$set('showEditModal', false)" @click="playClick()" class="text-[#71817f] hover:text-[#52646a] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Pelanggan <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="editCustName" type="text" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none" />
                    @error('editCustName') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nomor Telepon <span class="text-[#71817f] font-normal lowercase">(opsional)</span></label>
                    <input wire:model="editCustPhone" type="text" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none" />
                    @error('editCustPhone') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Tipe Pelanggan <span class="text-[#9c5252]">*</span></label>
                    <select wire:model="editCustType" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                    <button type="button" wire:click="$set('showEditModal', false)" @click="playClick()" class="btn-sound px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="updateCustomer" @click="playSuccess()" class="btn-sound px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#6b5226] hover:bg-[#6b5226] rounded-lg shadow-none  transition-all">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
