<?php

use App\Models\{Ledger, Location, Product, PurchaseRequest, Supplier};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showForm = false;
    public bool $showPurchaseForm = false;
    public bool $showReceiveForm = false;
    public ?int $editingId = null;
    public ?int $actionId = null;

    public string $product_id = '';
    public string $location_id = '';
    public string $supplier_id = '';
    public string $requested_qty = '';
    public string $request_notes = '';
    public string $invoice_number = '';
    public string $total_cost = '';
    public $proof_image = null;
    public string $received_qty = '';

    public function mount(): void
    {
        $this->authorize('view-purchase_request');
    }

    public function getRequestsProperty()
    {
        return PurchaseRequest::with(['product', 'location', 'supplier', 'requestedBy'])
            ->when($this->search, fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('ticket_number', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$this->search}%"))))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    public function getProductsProperty()
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    public function getLocationsProperty()
    {
        return Location::where('is_active', true)->orderBy('name')->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->authorize('create-purchase_request');
        $this->resetRequestForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('edit-purchase_request');
        $request = PurchaseRequest::findOrFail($id);
        abort_unless($request->status === 'pending', 403, 'Hanya permintaan pending yang dapat diedit.');

        $this->editingId = $request->id;
        $this->product_id = (string) $request->product_id;
        $this->location_id = (string) $request->location_id;
        $this->supplier_id = (string) ($request->supplier_id ?? '');
        $this->requested_qty = (string) $request->requested_qty;
        $this->request_notes = $request->request_notes ?? '';
        $this->showForm = true;
    }

    public function saveRequest(): void
    {
        $this->authorize($this->editingId ? 'edit-purchase_request' : 'create-purchase_request');

        $validated = $this->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'requested_qty' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'request_notes' => ['nullable', 'string'],
        ]);

        if ($this->editingId) {
            $request = PurchaseRequest::findOrFail($this->editingId);
            abort_unless($request->status === 'pending', 403, 'Permintaan ini sudah diproses.');
            $request->update($validated);
            session()->flash('success', "Permintaan {$request->ticket_number} diperbarui.");
        } else {
            do {
                $ticketNumber = 'PR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
            } while (PurchaseRequest::where('ticket_number', $ticketNumber)->exists());

            $validated['ticket_number'] = $ticketNumber;
            $validated['requested_by'] = auth()->id();
            $validated['status'] = 'pending';
            $request = PurchaseRequest::create($validated);
            session()->flash('success', "Permintaan {$request->ticket_number} berhasil diajukan.");
        }

        $this->showForm = false;
        $this->resetRequestForm();
        $this->resetPage();
    }

    public function openPurchase(int $id): void
    {
        $this->authorize('edit-purchase_request');
        $request = PurchaseRequest::findOrFail($id);
        abort_unless($request->status === 'approved', 403, 'Permintaan belum disetujui atau sudah diproses.');

        $this->actionId = $id;
        $this->supplier_id = (string) ($request->supplier_id ?? '');
        $this->invoice_number = '';
        $this->total_cost = '';
        $this->proof_image = null;
        $this->showPurchaseForm = true;
    }

    public function markPurchased(): void
    {
        $this->authorize('edit-purchase_request');
        $validated = $this->validate([
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'total_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'proof_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $request = PurchaseRequest::findOrFail($this->actionId);
        abort_unless($request->status === 'approved', 403, 'Hanya permintaan approved yang dapat dicatat pembeliannya.');

        $proofPath = $this->proof_image ? $this->proof_image->store('purchase-requests', 'public') : null;
        $request->update([
            'supplier_id' => $validated['supplier_id'] ?: null,
            'invoice_number' => $validated['invoice_number'] ?: null,
            'total_cost' => $validated['total_cost'],
            'proof_image' => $proofPath,
            'status' => 'purchased',
            'purchased_by' => auth()->id(),
            'purchased_at' => now(),
        ]);

        $this->showPurchaseForm = false;
        $this->proof_image = null;
        session()->flash('success', "Pembelian {$request->ticket_number} berhasil dicatat.");
    }

    public function openReceive(int $id): void
    {
        $this->authorize('edit-purchase_request');
        $request = PurchaseRequest::findOrFail($id);
        abort_unless($request->status === 'purchased', 403, 'Hanya barang yang sudah dibeli yang dapat diterima.');
        $this->actionId = $id;
        $this->received_qty = (string) $request->requested_qty;
        $this->showReceiveForm = true;
    }

    public function receive(): void
    {
        $this->authorize('edit-purchase_request');
        $validated = $this->validate([
            'received_qty' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated): void {
            $request = PurchaseRequest::lockForUpdate()->findOrFail($this->actionId);
            abort_unless($request->status === 'purchased' && ! $request->ledger_id, 403, 'Permintaan ini sudah diterima atau belum dibeli.');
            abort_if((int) $validated['received_qty'] > $request->requested_qty, 422, 'Jumlah diterima tidak boleh melebihi jumlah permintaan.');

            $receiptAmount = $request->requested_qty > 0
                ? round((float) $request->total_cost * $validated['received_qty'] / $request->requested_qty, 2)
                : 0;
            $ledger = Ledger::create([
                'type' => 'expense',
                'title' => 'Pembelian stok ' . $request->ticket_number,
                'amount' => $receiptAmount,
                'payment_status' => 'paid',
                'date' => now()->toDateString(),
                'reference' => $request->invoice_number,
                'description' => 'Penerimaan Purchase Request ' . $request->ticket_number,
                'product_id' => $request->product_id,
                'supplier_id' => $request->supplier_id,
                'location_id' => $request->location_id,
                'quantity' => $validated['received_qty'],
                'stock_movement' => 'in',
                'proof_image' => $request->proof_image,
                'user_id' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $request->update([
                'status' => 'completed',
                'received_qty' => $validated['received_qty'],
                'received_by' => auth()->id(),
                'received_at' => now(),
                'ledger_id' => $ledger->id,
            ]);
        });

        $this->showReceiveForm = false;
        session()->flash('success', 'Barang diterima, ledger pembelian dibuat, dan stok diperbarui.');
    }

    public function hapus(int $id): void
    {
        $this->authorize('delete-purchase_request');
        $request = PurchaseRequest::findOrFail($id);
        abort_unless(in_array($request->status, ['pending', 'rejected'], true), 403, 'Permintaan yang sudah disetujui tidak dapat dihapus.');
        $request->delete();
        session()->flash('success', "Permintaan {$request->ticket_number} dihapus.");
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    private function resetRequestForm(): void
    {
        $this->reset(['editingId', 'product_id', 'location_id', 'supplier_id', 'requested_qty', 'request_notes']);
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">Permintaan Pembelian</h1>
            <p class="mt-1 text-sm text-slate-500">Ajukan kebutuhan stok, catat pembelian, dan konfirmasi penerimaan.</p>
            @can('view-purchase_approval')
                <p class="mt-2 text-xs font-semibold text-blue-700">
                    Persetujuan dilakukan di modul <a href="{{ route('purchase-approval.index') }}" wire:navigate class="underline">Persetujuan PR</a>.
                </p>
            @endcan
        </div>
        @can('create-purchase_request')
            <button wire:click="openCreate" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                <span aria-hidden="true">+</span> Ajukan PR
            </button>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nomor tiket atau produk" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm" />
        <select wire:model.live="statusFilter" class="rounded-xl border-slate-300 text-sm sm:w-52">
            <option value="">Semua status</option>
            @foreach (['pending', 'approved', 'rejected', 'purchased', 'completed'] as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tiket / Produk</th>
                    <th class="px-4 py-3">Lokasi</th>
                    <th class="px-4 py-3 text-right">Qty</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Pemohon</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->requests as $request)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900">{{ $request->ticket_number }}</div>
                            <div class="text-slate-600">{{ $request->product?->name ?? 'Produk dihapus' }}</div>
                            @if ($request->request_notes)<div class="mt-1 max-w-sm text-xs text-slate-400">{{ $request->request_notes }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $request->location?->name ?? 'Lokasi dihapus' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $request->received_qty ?? $request->requested_qty }} / {{ $request->requested_qty }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                'bg-amber-50 text-amber-700' => $request->status === 'pending',
                                'bg-blue-50 text-blue-700' => $request->status === 'approved',
                                'bg-rose-50 text-rose-700' => $request->status === 'rejected',
                                'bg-cyan-50 text-cyan-700' => $request->status === 'purchased',
                                'bg-emerald-50 text-emerald-700' => $request->status === 'completed',
                            ])>{{ ucfirst($request->status) }}</span>
                            @if ($request->status === 'rejected' && $request->rejection_reason)
                                <div class="mt-1 max-w-xs text-xs text-rose-600">{{ $request->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $request->requestedBy?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2 whitespace-nowrap">
                                @if ($request->status === 'pending')
                                    @can('edit-purchase_request')
                                        <button wire:click="openEdit({{ $request->id }})" class="text-xs font-bold text-blue-700 hover:underline">Edit</button>
                                    @endcan
                                @elseif ($request->status === 'approved')
                                    @can('edit-purchase_request')
                                        <button wire:click="openPurchase({{ $request->id }})" class="text-xs font-bold text-blue-700 hover:underline">Catat Pembelian</button>
                                    @endcan
                                @elseif ($request->status === 'purchased')
                                    @can('edit-purchase_request')
                                        <button wire:click="openReceive({{ $request->id }})" class="text-xs font-bold text-emerald-700 hover:underline">Terima Barang</button>
                                    @endcan
                                @endif
                                @if (in_array($request->status, ['pending', 'rejected'], true))
                                    @can('delete-purchase_request')
                                        <button wire:click="hapus({{ $request->id }})" wire:confirm="Hapus permintaan ini?" class="text-xs font-bold text-rose-700 hover:underline">Hapus</button>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">Belum ada permintaan pembelian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $this->requests->links() }}

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
            <form wire:submit="saveRequest" class="max-h-[90vh] w-full max-w-xl space-y-4 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-extrabold text-slate-900">{{ $editingId ? 'Edit Permintaan' : 'Ajukan Permintaan Pembelian' }}</h2>
                <label class="block text-sm font-semibold text-slate-700">Produk
                    <select wire:model="product_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Pilih produk</option>
                        @foreach ($this->products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach
                    </select>
                    @error('product_id')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Lokasi
                    <select wire:model="location_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Pilih lokasi</option>
                        @foreach ($this->locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
                    </select>
                    @error('location_id')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Supplier (opsional)
                    <select wire:model="supplier_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Belum ditentukan</option>
                        @foreach ($this->suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                    </select>
                    @error('supplier_id')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Jumlah diminta
                    <input wire:model="requested_qty" type="number" min="1" step="1" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    @error('requested_qty')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Catatan (opsional)
                    <textarea wire:model="request_notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                    @error('request_notes')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="$set('showForm', false)" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Batal</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    @if ($showPurchaseForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
            <form wire:submit="markPurchased" class="max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-extrabold text-slate-900">Catat Pembelian</h2>
                <label class="block text-sm font-semibold text-slate-700">Supplier
                    <select wire:model="supplier_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Tanpa supplier</option>
                        @foreach ($this->suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                    </select>
                    @error('supplier_id')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Nomor invoice (opsional)
                    <input wire:model="invoice_number" type="text" maxlength="255" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    @error('invoice_number')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Total biaya
                    <input wire:model="total_cost" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    @error('total_cost')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-semibold text-slate-700">Bukti pembelian (maks. 2 MB)
                    <input wire:model="proof_image" type="file" accept="image/*" class="mt-1 block w-full text-sm" />
                    @error('proof_image')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="$set('showPurchaseForm', false)" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Batal</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white">Simpan Pembelian</button>
                </div>
            </form>
        </div>
    @endif

    @if ($showReceiveForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
            <form wire:submit="receive" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-extrabold text-slate-900">Konfirmasi Penerimaan</h2>
                <label class="block text-sm font-semibold text-slate-700">Jumlah diterima
                    <input wire:model="received_qty" type="number" min="1" step="1" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    @error('received_qty')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <p class="text-xs text-slate-500">Konfirmasi akan membuat ledger pembelian dan menambah stok secara otomatis.</p>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="$set('showReceiveForm', false)" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Batal</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Terima Barang</button>
                </div>
            </form>
        </div>
    @endif
</div>
