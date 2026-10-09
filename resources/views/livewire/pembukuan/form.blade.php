<?php

use App\Models\{Ledger, Product, Location, Supplier};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public ?Ledger $ledger   = null;
    public bool    $modeEdit = false;

    public string  $type        = 'income';
    public string  $title       = 'Penjualan';
    public string  $amount      = '0';
    public string  $date        = '';
    public ?string $description = '';
    public ?string $reference   = '';
    public ?string $product_id      = null;
    public ?string $location_id     = null;
    public ?string $customer_id     = null;
    public ?string $supplier_id     = null;
    public string  $quantity        = '';
    public string  $stock_movement  = '';
    public string  $payment_status  = 'paid';
    public ?string $due_date        = null;
    public         $proof_image     = null;

    public bool    $showOptional = false;

    // --- Modal State ---
    public string $newLocName = '';
    public string $newLocDesc = '';

    public string $newProdName = '';
    public string $newProdPrice = '0';
    public string $newProdCost = '0';
    public string $newProdUnit = 'pcs';

    public string $newCustName = '';
    public string $newCustPhone = '';
    public string $newCustType = 'non_seller';

    public function mount(?string $slug = null): void
    {
        if ($slug) {
            $this->authorize('edit-pembukuan');
            $this->modeEdit = true;
            $this->ledger   = Ledger::where('slug', $slug)->firstOrFail();
            $this->fill($this->ledger->only(['type', 'title', 'description', 'reference', 'product_id', 'location_id', 'stock_movement', 'customer_id', 'supplier_id', 'payment_status']));
            $this->quantity = (string) $this->ledger->quantity;
            $this->amount = (string) $this->ledger->amount;
            $this->date   = $this->ledger->date->format('Y-m-d');
            $this->due_date = $this->ledger->due_date ? $this->ledger->due_date->format('Y-m-d') : null;
        } else {
            $this->authorize('create-pembukuan');
            $this->date = now()->format('Y-m-d');
            $this->product_id = request()->query('produk');
            if ($this->title === 'Penjualan') {
                $this->stock_movement = 'out';
            } elseif ($this->title === 'Pembelian stok') {
                $this->stock_movement = 'in';
            }
        }
    }

    public function getProductListProperty()
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    public function getCustomerListProperty()
    {
        return \App\Models\Customer::orderBy('name')->get();
    }

    public function getLocationListProperty()
    {
        return Location::where('is_active', true)->orderBy('name')->get();
    }

    public function getSupplierListProperty()
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    public function updatedType($value)
    {
        if ($value === 'income') {
            $this->title = 'Penjualan';
            $this->stock_movement = 'out';
        } elseif ($value === 'expense') {
            $this->title = 'Pembelian stok';
            $this->stock_movement = 'in';
        }
    }

    public function updatedProductId($value)
    {
        if ($value === 'new') {
            $this->product_id = null;
            $this->dispatch('open-modal', 'modal-product');
        }
    }

    public function updatedLocationId($value)
    {
        if ($value === 'new') {
            $this->location_id = null;
            $this->dispatch('open-modal', 'modal-location');
        }
    }

    public function updatedCustomerId($value)
    {
        if ($value === 'new') {
            $this->customer_id = null;
            $this->dispatch('open-modal', 'modal-customer');
        }
    }

    public function saveNewLocation()
    {
        $this->authorize('create-pembukuan');
        $this->validate([
            'newLocName' => 'required|string|max:255',
            'newLocDesc' => 'nullable|string',
        ]);

        $loc = Location::create([
            'name' => $this->newLocName,
            'description' => $this->newLocDesc,
            'is_active' => true
        ]);

        $this->location_id = (string) $loc->id;
        $this->newLocName = '';
        $this->newLocDesc = '';
        $this->dispatch('close-modal');
    }

    public function saveNewCustomer()
    {
        $this->authorize('create-pembukuan');
        $this->validate([
            'newCustName' => 'required|string|max:255',
            'newCustPhone' => 'nullable|string|max:20',
            'newCustType' => 'required|in:seller,non_seller',
        ]);

        $cust = \App\Models\Customer::create([
            'name' => $this->newCustName,
            'phone' => $this->newCustPhone,
            'type' => $this->newCustType,
        ]);

        $this->customer_id = (string) $cust->id;
        $this->newCustName = '';
        $this->newCustPhone = '';
        $this->newCustType = 'non_seller';
        $this->dispatch('close-modal');
    }

    public function saveNewProduct()
    {
        $this->authorize('create-pembukuan');
        $this->validate([
            'newProdName' => 'required|string|max:255',
            'newProdPrice' => 'required|numeric|min:0',
            'newProdCost' => 'required|numeric|min:0',
            'newProdUnit' => 'required|string',
        ]);

        $prod = Product::create([
            'name' => $this->newProdName,
            'price' => $this->newProdPrice,
            'cost' => $this->newProdCost,
            'unit' => $this->newProdUnit,
            'is_active' => true
        ]);

        $this->product_id = (string) $prod->id;
        $this->newProdName = '';
        $this->newProdPrice = '0';
        $this->newProdCost = '0';
        $this->dispatch('close-modal');
        $this->dispatch('product-added', id: $prod->id, price: $prod->price, cost: $prod->cost);
    }

    public function updatedTitle($value)
    {
        if ($value === 'Penjualan') {
            $this->stock_movement = 'out';
        } elseif ($value === 'Pembelian stok') {
            $this->stock_movement = 'in';
        } else {
            $this->stock_movement = '';
        }
    }

    public function simpan(): void
    {
        $this->authorize($this->modeEdit ? 'edit-pembukuan' : 'create-pembukuan');

        if (!in_array($this->title, ['Penjualan', 'Pembelian stok'])) {
            $this->product_id = null;
            $this->location_id = null;
            $this->quantity = null;
            $this->stock_movement = null;
            $this->customer_id = null;
            $this->supplier_id = null;
        }

        if ($this->payment_status === 'paid') {
            $this->due_date = null;
        }

        if ($this->stock_movement === '') {
            $this->stock_movement = null;
        }

        if ($this->stock_movement === 'out' && $this->product_id && $this->location_id && $this->quantity) {
            $currentStock = \App\Models\Stock::where('product_id', $this->product_id)
                ->where('location_id', $this->location_id)
                ->value('quantity') ?? 0;

            $originalQuantity = 0;
            if ($this->modeEdit && $this->ledger->product_id == $this->product_id && $this->ledger->location_id == $this->location_id && $this->ledger->stock_movement === 'out') {
                $originalQuantity = $this->ledger->quantity;
            }

            if ($this->quantity > ($currentStock + $originalQuantity)) {
                $this->addError('quantity', "Stok tidak mencukupi! Tersisa: ".($currentStock + $originalQuantity)." di lokasi ini.");
                return;
            }
        }

        $validated = $this->validate([
            'type'           => ['required', 'in:income,expense'],
            'title'          => ['required', 'string', 'max:255'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:paid,unpaid'],
            'due_date'       => ['nullable', 'date'],
            'date'           => ['required', 'date'],
            'description'    => ['nullable', 'string'],
            'reference'      => ['nullable', 'string', 'max:100'],
            'product_id'     => ['nullable', 'exists:products,id'],
            'location_id'    => ['nullable', 'exists:locations,id'],
            'customer_id'    => ['nullable', 'exists:customers,id'],
            'supplier_id'    => ['nullable', 'exists:suppliers,id'],
            'quantity'       => ['nullable', 'numeric', 'min:1'],
            'stock_movement' => ['nullable', 'in:in,out'],
            'proof_image'    => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->proof_image) {
            $image = imagecreatefromstring(file_get_contents($this->proof_image->getRealPath()));
            $filename = \Illuminate\Support\Str::uuid() . '.webp';
            $path = storage_path('app/public/bukti-transaksi');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            imagewebp($image, $path . '/' . $filename, 80);
            imagedestroy($image);
            $validated['proof_image'] = 'bukti-transaksi/' . $filename;
        }

        DB::transaction(function () use ($validated) {
            if ($this->modeEdit) {
                $this->ledger->update($validated);
            } else {
                $validated['user_id'] = auth()->id();
                $validated['updated_by'] = auth()->id();
                Ledger::create($validated);
            }
        });

        session()->flash('success', $this->modeEdit
            ? 'Catatan transaksi berhasil diperbarui.'
            : 'Transaksi berhasil dicatat.');

        session()->forget(['new_product_id', 'new_location_id']);

        $this->redirectRoute('pembukuan.index', navigate: true);
    }
}; ?>

<div class="space-y-6 max-w-3xl mx-auto"
     x-data="{ showLocModal: false, showProdModal: false, showCustModal: false,
            products: {{ $this->productList->mapWithKeys(fn($p) => [$p->id => ['price' => $p->price, 'cost' => $p->cost]]) }},
            stocks: {{ json_encode(\App\Models\Stock::all()->mapWithKeys(fn($s) => ["{$s->product_id}_{$s->location_id}" => $s->quantity])) }},
            isCustomPrice: false,
            insufficientStock: false,
            availableStock: 0,

            calcExpectedAmount() {
                if (!$wire.product_id || !$wire.quantity || $wire.quantity <= 0) return null;
                let p = this.products[$wire.product_id];
                if (!p) return null;
                return $wire.type === 'income' ? (p.price * $wire.quantity) : (p.cost * $wire.quantity);
            },

            autoCalculate() {
                let expected = this.calcExpectedAmount();
                if (expected !== null) {
                    $wire.amount = expected;
                    this.isCustomPrice = false;
                }
            },

            checkCustomPrice() {
                let expected = this.calcExpectedAmount();
                if (expected !== null && expected != $wire.amount) {
                    this.isCustomPrice = true;
                } else {
                    this.isCustomPrice = false;
                }
            },

            checkStockLimit() {
                this.insufficientStock = false;
                if ($wire.stock_movement === 'out' && $wire.product_id && $wire.location_id && $wire.quantity > 0) {
                    let key = $wire.product_id + '_' + $wire.location_id;
                    let available = parseFloat(this.stocks[key] || 0);

                    @if($modeEdit)
                    if ($wire.product_id == '{{ $ledger->product_id }}' && $wire.location_id == '{{ $ledger->location_id }}' && '{{ $ledger->stock_movement }}' === 'out') {
                        available += parseFloat('{{ $ledger->quantity }}');
                    }
                    @endif

                    if (parseFloat($wire.quantity) > available) {
                        this.insufficientStock = true;
                        this.availableStock = available;
                    }
                }
            }
         }"
         x-init="
            $watch('$wire.product_id', () => { autoCalculate(); checkStockLimit(); });
            $watch('$wire.location_id', () => checkStockLimit());
            $watch('$wire.quantity', () => { autoCalculate(); checkStockLimit(); });
            $watch('$wire.type', () => autoCalculate());
            $watch('$wire.stock_movement', () => checkStockLimit());
            $watch('$wire.amount', () => checkCustomPrice());
         "
     @open-modal.window="
        if ($event.detail[0] === 'modal-location') showLocModal = true;
        if ($event.detail[0] === 'modal-product') showProdModal = true;
        if ($event.detail[0] === 'modal-customer') showCustModal = true;
     "
     @close-modal.window="showLocModal = false; showProdModal = false; showCustModal = false;"
     @product-added.window="products[$event.detail.id] = { price: $event.detail.price, cost: $event.detail.cost }; autoCalculate();">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:border-blue-200 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Formulir Pembukuan</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1">{{ $modeEdit ? 'Edit Transaksi' : 'Catat Transaksi Baru' }}</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">Lengkapi entri arus kas transaksi keuangan Anda.</p>
            </div>
        </div>
    </div>

    {{-- ── Form Card ── --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <form wire:submit="simpan" class="p-6 space-y-5">

            {{-- Tipe Transaksi --}}
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Tipe Transaksi <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3.5 border rounded-2xl cursor-pointer transition-all
                                  {{ $type === 'income' ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200/80 hover:border-slate-300 bg-white' }}">
                        <input wire:model.live="type" type="radio" value="income" class="sr-only" @click="playClick()" />
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $type === 'income' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-extrabold {{ $type === 'income' ? 'text-emerald-800' : 'text-slate-700' }}">Pemasukan</span>
                            <span class="text-[10px] text-slate-400 font-medium">Uang masuk ke kas</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 border rounded-2xl cursor-pointer transition-all
                                  {{ $type === 'expense' ? 'border-rose-500 bg-rose-50/60 ring-2 ring-rose-500/20' : 'border-slate-200/80 hover:border-slate-300 bg-white' }}">
                        <input wire:model.live="type" type="radio" value="expense" class="sr-only" @click="playClick()" />
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $type === 'expense' ? 'bg-rose-500 text-white shadow-sm' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-extrabold {{ $type === 'expense' ? 'text-rose-800' : 'text-slate-700' }}">Pengeluaran</span>
                            <span class="text-[10px] text-slate-400 font-medium">Uang keluar dari kas</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Jenis Transaksi --}}
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Jenis Transaksi <span class="text-rose-500">*</span></label>
                <select wire:model.live="title"
                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm">
                    @if($type === 'income')
                        <option value="Penjualan">Penjualan</option>
                        <option value="Penambahan modal">Penambahan modal</option>
                        <option value="Pendapatan diluar usaha">Pendapatan diluar usaha</option>
                        <option value="Pendapatan lainnya">Pendapatan lainnya</option>
                        <option value="Pendapatan jasa/komisi">Pendapatan jasa/komisi</option>
                        <option value="Penagihan utang/cicilan">Penagihan utang/cicilan</option>
                    @else
                        <option value="Pembelian stok">Pembelian stok</option>
                        <option value="Pengeluaran di luar usaha">Pengeluaran di luar usaha</option>
                        <option value="Pembelian bahan baku">Pembelian bahan baku</option>
                        <option value="Biaya operasional">Biaya operasional</option>
                        <option value="Gaji/bonus karyawan">Gaji/bonus karyawan</option>
                        <option value="Pemberian utang">Pemberian utang</option>
                        <option value="Pembayaran utang/cicilan">Pembayaran utang/cicilan</option>
                        <option value="Pengeluaran lainnya">Pengeluaran lainnya</option>
                    @endif
                </select>
                @error('title') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            @if(in_array($title, ['Penjualan', 'Pembelian stok']))
                <div class="p-5 bg-slate-50/80 border border-slate-200/80 rounded-2xl space-y-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Detail Stok Barang</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Produk Terkait</label>
                            <select wire:model.live="product_id"
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm">
                                <option value="">— Pilih Produk —</option>
                                <option value="new" class="font-bold text-blue-600">+ Tambah Produk Baru...</option>
                                @foreach($this->productList as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Lokasi Stok</label>
                            <select wire:model.live="location_id"
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm">
                                <option value="">— Pilih Lokasi —</option>
                                <option value="new" class="font-bold text-blue-600">+ Tambah Lokasi Baru...</option>
                                @foreach($this->locationList as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Kuantitas</label>
                            <input wire:model="quantity" type="number" min="1" placeholder="Misal: 5"
                                   class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                            <p x-show="insufficientStock" x-cloak class="text-rose-600 text-[11px] mt-1.5 font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Stok tidak mencukupi! Tersisa: <span x-text="availableStock"></span> unit di lokasi ini.
                            </p>
                            @error('quantity') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Mutasi Stok</label>
                            <select wire:model="stock_movement" disabled
                                    class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-2xl text-xs sm:text-sm font-semibold text-slate-500 cursor-not-allowed shadow-sm">
                                <option value="">— Pilih Mutasi —</option>
                                <option value="in">Masuk (Stok Bertambah)</option>
                                <option value="out">Keluar (Stok Berkurang)</option>
                            </select>
                        </div>
                    </div>

                    @if($title === 'Penjualan')
                        <div class="border-t border-slate-200/80 pt-4 mt-2">
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Pelanggan <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <select wire:model.live="customer_id"
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm">
                                <option value="">— Pelanggan Umum —</option>
                                <option value="new" class="font-bold text-blue-600">+ Tambah Pelanggan Baru...</option>
                                @foreach($this->customerList as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->type == 'seller' ? 'Seller' : 'Non Seller' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($title === 'Pembelian stok')
                        <div class="border-t border-slate-200/80 pt-4 mt-2">
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Supplier <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <select wire:model="supplier_id"
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm">
                                <option value="">— Tanpa Supplier —</option>
                                @foreach($this->supplierList as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nominal Transaksi (Rp) <span class="text-rose-500">*</span></label>
                    <input wire:model="amount" type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-extrabold text-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                    <p x-show="isCustomPrice" x-cloak class="text-amber-600 text-[11px] mt-1.5 font-bold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Manual: Nominal disesuaikan khusus (berbeda dari standar produk).
                    </p>
                    @error('amount') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                    <input wire:model="date" type="date"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                    @error('date') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Status Pembayaran --}}
            <div class="p-5 border rounded-2xl space-y-3 transition-all {{ $payment_status === 'unpaid' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200/80 bg-slate-50/40' }}">
                <span class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Status Pembayaran</span>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2.5 p-3 border rounded-2xl cursor-pointer transition-all bg-white
                                  {{ $payment_status === 'paid' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 hover:border-slate-300' }}">
                        <input wire:model.live="payment_status" type="radio" value="paid" class="sr-only" />
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 {{ $payment_status === 'paid' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="text-xs sm:text-sm font-extrabold {{ $payment_status === 'paid' ? 'text-emerald-800' : 'text-slate-700' }}">Lunas</span>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 border rounded-2xl cursor-pointer transition-all bg-white
                                  {{ $payment_status === 'unpaid' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 hover:border-slate-300' }}">
                        <input wire:model.live="payment_status" type="radio" value="unpaid" class="sr-only" />
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 {{ $payment_status === 'unpaid' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-xs sm:text-sm font-extrabold {{ $payment_status === 'unpaid' ? 'text-amber-800' : 'text-slate-700' }}">Belum Lunas</span>
                    </label>
                </div>

                @if($payment_status === 'unpaid')
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-amber-800 mb-1.5 uppercase tracking-wider">Jatuh Tempo <span class="text-amber-600/70 font-normal">(opsional)</span></label>
                        <input wire:model="due_date" type="date"
                               class="w-full px-4 py-2.5 bg-white border border-amber-300 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all shadow-sm" />
                        <p class="text-[11px] text-amber-700 mt-1.5 font-bold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Transaksi piutang/utang ini tidak mempengaruhi arus kas lunas hingga diselesaikan.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Optional Fields Toggle --}}
            <div>
                <button type="button" wire:click="$toggle('showOptional')" class="text-xs font-extrabold text-blue-600 hover:text-blue-700 flex items-center gap-1.5">
                    <svg class="w-4 h-4 transition-transform {{ $showOptional ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <span>{{ $showOptional ? 'Sembunyikan Informasi Tambahan' : 'Tampilkan Informasi Tambahan (Opsional)' }}</span>
                </button>
            </div>

            @if($showOptional)
                <div class="space-y-4 p-5 border border-blue-100 bg-blue-50/30 rounded-2xl">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">No. Referensi <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="reference" type="text" placeholder="Misal: INV-2026-001"
                               class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Catatan Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <textarea wire:model="description" rows="2" placeholder="Detail catatan tambahan seputar transaksi..."
                                  class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Foto Bukti Transaksi <span class="text-slate-400 font-normal">(Max 2MB)</span></label>
                        @if($modeEdit && $ledger->proof_image)
                            <img src="{{ Storage::url($ledger->proof_image) }}" alt="Bukti Transaksi" class="w-24 h-24 object-cover rounded-2xl border border-slate-200 mb-3 shadow-sm" />
                        @endif
                        <input wire:model="proof_image" type="file" accept="image/*"
                               class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors cursor-pointer" />
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</a>

                <button type="submit" @click="if(!insufficientStock) playSuccess()" :disabled="insufficientStock"
                        class="btn-sound px-6 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-wider text-white shadow-md transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed
                               {{ $type === 'income' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20' : 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>{{ $modeEdit ? 'Simpan Perubahan' : 'Catat Transaksi' }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Modal Tambah Lokasi --}}
    <div x-show="showLocModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div x-show="showLocModal" @click.outside="showLocModal = false" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-xl p-6 border border-slate-200/80"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Tambah Lokasi Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Lokasi</label>
                    <input wire:model="newLocName" type="text" placeholder="Misal: Gudang Utamna" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                    @error('newLocName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="newLocDesc" rows="2" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm resize-none"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showLocModal = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewLocation" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 rounded-2xl shadow-md shadow-blue-600/20 transition-all">Simpan Lokasi</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Produk --}}
    <div x-show="showProdModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div x-show="showProdModal" @click.outside="showProdModal = false" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-xl p-6 border border-slate-200/80"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Tambah Produk Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Produk</label>
                    <input wire:model="newProdName" type="text" placeholder="Misal: Nugget Ayam 500gr" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                    @error('newProdName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Harga Jual</label>
                        <input wire:model="newProdPrice" type="number" step="100" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Harga Modal</label>
                        <input wire:model="newProdCost" type="number" step="100" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Satuan</label>
                    <select wire:model="newProdUnit" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                        <option value="pcs">pcs</option>
                        <option value="pack">pack</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showProdModal = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewProduct" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 rounded-2xl shadow-md shadow-blue-600/20 transition-all">Simpan Produk</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Pelanggan --}}
    <div x-show="showCustModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div x-show="showCustModal" @click.outside="showCustModal = false" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-xl p-6 border border-slate-200/80"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Tambah Pelanggan Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Pelanggan</label>
                    <input wire:model="newCustName" type="text" placeholder="Misal: Budi" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                    @error('newCustName') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Nomor Telepon <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="newCustPhone" type="text" placeholder="Misal: 081234567890" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                    @error('newCustPhone') <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Tipe Pelanggan</label>
                    <select wire:model="newCustType" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller / Reseller</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCustModal = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewCustomer" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 rounded-2xl shadow-md shadow-blue-600/20 transition-all">Simpan Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
</div>
