<?php

use App\Models\{Ledger, Product, Location, Supplier};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

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
            $this->authorize('edit-ledger');
            $this->modeEdit = true;
            $this->ledger   = Ledger::where('slug', $slug)->firstOrFail();
            $this->fill($this->ledger->only(['type', 'title', 'description', 'reference', 'product_id', 'location_id', 'stock_movement', 'customer_id', 'supplier_id', 'payment_status']));
            $this->quantity = (string) $this->ledger->quantity;
            $this->amount = (string) $this->ledger->amount;
            $this->date   = $this->ledger->date->format('Y-m-d');
            $this->due_date = $this->ledger->due_date ? $this->ledger->due_date->format('Y-m-d') : null;
        } else {
            $this->authorize('create-ledger');
            $this->date = now()->format('Y-m-d');
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
        $this->authorize('create-ledger'); // Using same auth for simplicity
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
        $this->authorize('create-ledger');
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
        $this->authorize('create-ledger');
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
        $this->authorize($this->modeEdit ? 'edit-ledger' : 'create-ledger');

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
            // Konversi ke webp
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

        if ($this->modeEdit) {
            $this->ledger->update($validated);
            session()->flash('success', 'Catatan transaksi berhasil diperbarui.');
        } else {
            $validated['user_id'] = auth()->id();
            $validated['updated_by'] = auth()->id();
            Ledger::create($validated);
            session()->flash('success', 'Transaksi berhasil dicatat.');
        }

        session()->forget(['new_product_id', 'new_location_id']);

        $this->redirectRoute('pembukuan.index', navigate: true);
    }
}; ?>

<div class="space-y-5 max-w-xl mx-auto lg:max-w-none lg:max-w-xl"
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

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
           class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">{{ $modeEdit ? 'Edit Transaksi' : 'Catat Transaksi' }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Lengkapi form transaksi keuangan</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);"
>
        <form wire:submit="simpan" class="p-6 space-y-5">

            {{-- Tipe Transaksi --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tipe Transaksi <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 px-4 py-3 border rounded-xl cursor-pointer transition-colors
                                  {{ $type === 'income' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:border-slate-300' }}">
                        <input wire:model.live="type" type="radio" value="income" class="sr-only" @click="playClick()" />
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $type === 'income' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="text-sm font-bold {{ $type === 'income' ? 'text-emerald-700' : 'text-slate-500' }}">Pemasukan</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 px-4 py-3 border rounded-xl cursor-pointer transition-colors
                                  {{ $type === 'expense' ? 'border-red-400 bg-red-50' : 'border-slate-200 hover:border-slate-300' }}">
                        <input wire:model.live="type" type="radio" value="expense" class="sr-only" @click="playClick()" />
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $type === 'expense' ? 'bg-red-100 text-red-500' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="text-sm font-bold {{ $type === 'expense' ? 'text-red-600' : 'text-slate-500' }}">Pengeluaran</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Judul (Jenis Transaksi) --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Jenis Transaksi <span class="text-red-500">*</span></label>
                <select wire:model.live="title"
                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all">
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
                @error('title') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            @if(in_array($title, ['Penjualan', 'Pembelian stok']))
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl space-y-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Detail Stok</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Produk Terkait</label>
                            <select wire:model.live="product_id"
                                    class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
                                <option value="">— Pilih Produk —</option>
                                <option value="new" class="font-bold text-blue-600">➕ Tambah Produk Baru</option>
                                @foreach($this->productList as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Lokasi Stok</label>
                            <select wire:model.live="location_id" 
                                    class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
                                <option value="">— Pilih Lokasi —</option>
                                <option value="new" class="font-bold text-blue-600">➕ Tambah Lokasi Baru</option>
                                @foreach($this->locationList as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kuantitas</label>
                            <input wire:model="quantity" type="number" min="1" placeholder="Misal: 5"
                                   class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm" />
                            <p x-show="insufficientStock" x-cloak class="text-red-500 text-[10px] mt-1.5 font-bold flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Stok tidak mencukupi! (Tersisa: <span x-text="availableStock"></span> di lokasi ini).
                            </p>
                            @error('quantity') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mutasi Stok</label>
                            <select wire:model="stock_movement" disabled
                                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-500 cursor-not-allowed focus:outline-none shadow-sm">
                                <option value="">— Pilih Mutasi —</option>
                                <option value="in">Masuk (Stok Bertambah)</option>
                                <option value="out">Keluar (Stok Berkurang)</option>
                            </select>
                        </div>
                    </div>
                    @if($title === 'Penjualan')
                        <div class="border-t border-slate-100 pt-4 mt-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Pelanggan <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <select wire:model.live="customer_id"
                                    class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
                                <option value="">— Pelanggan Umum —</option>
                                <option value="new" class="font-bold text-blue-600">➕ Tambah Pelanggan Baru</option>
                                @foreach($this->customerList as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->type == 'seller' ? 'Seller' : 'Non Seller' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($title === 'Pembelian stok')
                        <div class="border-t border-slate-100 pt-4 mt-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Supplier <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <select wire:model="supplier_id"
                                    class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
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
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nominal (Rp) <span class="text-red-500">*</span></label>
                    <input wire:model="amount" type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    <p x-show="isCustomPrice" x-cloak class="text-amber-500 text-[10px] mt-1.5 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Perhatian: Nominal ini disesuaikan manual (berbeda dari harga standar stok).
                    </p>
                    @error('amount') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                    <input wire:model="date" type="date"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    @error('date') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Status Pembayaran --}}
            <div class="p-4 border rounded-xl space-y-4" :class="$wire.payment_status === 'unpaid' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-100 bg-slate-50/30'">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Status Pembayaran</p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 px-4 py-3 border rounded-xl cursor-pointer transition-colors
                                  {{ $payment_status === 'paid' ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200 hover:border-slate-300' }}">
                        <input wire:model.live="payment_status" type="radio" value="paid" class="sr-only" />
                        <span class="text-lg">✅</span>
                        <span class="text-sm font-bold {{ $payment_status === 'paid' ? 'text-emerald-700' : 'text-slate-500' }}">Lunas</span>
                    </label>
                    <label class="flex items-center gap-2 px-4 py-3 border rounded-xl cursor-pointer transition-colors
                                  {{ $payment_status === 'unpaid' ? 'border-amber-400 bg-amber-50' : 'border-slate-200 hover:border-slate-300' }}">
                        <input wire:model.live="payment_status" type="radio" value="unpaid" class="sr-only" />
                        <span class="text-lg">⏳</span>
                        <span class="text-sm font-bold {{ $payment_status === 'unpaid' ? 'text-amber-700' : 'text-slate-500' }}">Belum Lunas</span>
                    </label>
                </div>
                @if($payment_status === 'unpaid')
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Jatuh Tempo <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="due_date" type="date"
                               class="w-full px-4 py-2.5 bg-white border border-amber-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-400 shadow-sm transition-all" />
                        <p class="text-[10px] text-amber-600 mt-1.5 font-medium">⚠️ Transaksi ini tidak akan dihitung sebagai {{ $type === 'income' ? 'pemasukan' : 'pengeluaran' }} hingga dilunasi.</p>
                    </div>
                @endif
            </div>

            {{-- Optional Fields Toggle --}}
            <div>
                <button type="button" wire:click="$toggle('showOptional')" class="text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                    <svg class="w-4 h-4 transition-transform {{ $showOptional ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    {{ $showOptional ? 'Sembunyikan Informasi Tambahan' : 'Tampilkan Informasi Tambahan (Opsional)' }}
                </button>
            </div>

            @if($showOptional)
                <div class="space-y-5 p-4 border border-blue-100 bg-blue-50/30 rounded-xl animate-fade-in-up">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">No. Referensi <span class="text-slate-400 font-normal">(ops)</span></label>
                        <input wire:model="reference" type="text" placeholder="INV-001"
                               class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <textarea wire:model="description" rows="2" placeholder="Catatan tambahan..."
                                  class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Foto Bukti <span class="text-slate-400 font-normal">(max 2MB)</span></label>
                        @if($modeEdit && $ledger->proof_image)
                            <img src="{{ Storage::url($ledger->proof_image) }}" alt="Bukti" class="w-24 h-24 object-cover rounded-xl border border-slate-200 mb-3 shadow-sm" />
                        @endif
                        <input wire:model="proof_image" type="file" accept="image/*"
                               class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 transition-colors" />
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between pt-5 border-t border-slate-100 mt-6">
                <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()" class="btn-sound text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors">Batal</a>
                <button type="submit" @click="if(!insufficientStock) playSuccess()" :disabled="insufficientStock"
                        class="btn-sound px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg transition-all hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none
                               {{ $type === 'income' ? 'bg-emerald-500 shadow-emerald-200/50' : 'bg-red-500 shadow-red-200/50' }}">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Catat Transaksi' }}
                </button>
            </div>

        </form>
    </div>

    {{-- Modal Tambah Lokasi --}}
    <div x-show="showLocModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div x-show="showLocModal" @click.outside="showLocModal = false" class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Lokasi Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Lokasi</label>
                    <input wire:model="newLocName" type="text" placeholder="Misal: Gudang Depan" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    @error('newLocName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi <span class="text-slate-400 font-normal">(ops)</span></label>
                    <textarea wire:model="newLocDesc" rows="2" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" @click="showLocModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="saveNewLocation" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md">Simpan Lokasi</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Produk --}}
    <div x-show="showProdModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div x-show="showProdModal" @click.outside="showProdModal = false" class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Produk Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Produk</label>
                    <input wire:model="newProdName" type="text" placeholder="Misal: Nugget Ayam" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    @error('newProdName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Harga Jual</label>
                        <input wire:model="newProdPrice" type="number" step="100" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Harga Modal</label>
                        <input wire:model="newProdCost" type="number" step="100" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Satuan</label>
                    <select wire:model="newProdUnit" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="pcs">pcs</option>
                        <option value="pack">pack</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" @click="showProdModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="saveNewProduct" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md">Simpan Produk</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Pelanggan --}}
    <div x-show="showCustModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div x-show="showCustModal" @click.outside="showCustModal = false" class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Pelanggan Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Pelanggan</label>
                    <input wire:model="newCustName" type="text" placeholder="Misal: Budi" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    @error('newCustName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Telepon <span class="text-slate-400 font-normal">(ops)</span></label>
                    <input wire:model="newCustPhone" type="text" placeholder="Misal: 0812345678" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    @error('newCustPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tipe Pelanggan</label>
                    <select wire:model="newCustType" class="w-full px-4 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" @click="showCustModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="button" wire:click="saveNewCustomer" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md">Simpan Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
</div>
