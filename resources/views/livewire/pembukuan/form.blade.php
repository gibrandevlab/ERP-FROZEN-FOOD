<?php

use App\Models\{Category, Ledger, LedgerItem, Product, Location, Supplier};
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
    public ?string $customer_id     = null;
    public ?string $supplier_id     = null;
    public string  $stock_movement  = '';
    public array $items = [];
    public int $activeItem = 0;
    public string  $payment_status  = 'paid';
    public ?string $due_date        = null;
    public         $proof_image     = null;

    public bool    $showOptional = false;

    // --- Modal State ---
    public string $newLocName = '';
    public string $newLocDesc = '';
    public bool $newLocActive = true;

    public string $newProdName = '';
    public string $newProdSku = '';
    public string $newProdCategoryId = '';
    public string $newProdDescription = '';
    public string $newProdPrice = '0';
    public string $newProdCost = '0';
    public string $newProdUnit = 'pcs';
    public string $newProdMinStock = '10';
    public string $newProdWholesalePrice = '';
    public string $newProdWholesaleMinQty = '';
    public string $newProdLeadTime = '0';
    public bool $newProdActive = true;
    public $newProdImage = null;

    public string $newCustName = '';
    public string $newCustPhone = '';
    public string $newCustType = 'non_seller';

    public function mount(?string $slug = null): void
    {
        if ($slug) {
            $this->authorize('edit-pembukuan');
            $this->modeEdit = true;
            $this->ledger   = Ledger::where('slug', $slug)->firstOrFail();
            $this->fill([
                ...$this->ledger->only(['type', 'title', 'description', 'reference', 'stock_movement', 'customer_id', 'supplier_id', 'payment_status']),
                'stock_movement' => $this->ledger->stock_movement ?? '',
            ]);
            $this->amount = (string) $this->ledger->amount;
            $this->date   = $this->ledger->date->format('Y-m-d');
            $this->due_date = $this->ledger->due_date ? $this->ledger->due_date->format('Y-m-d') : null;
            $existingItems = $this->ledger->items;
            if ($existingItems->isNotEmpty()) {
                $this->items = $existingItems->map(fn (LedgerItem $item) => [
                    'product_id' => $item->product_id ? (string) $item->product_id : '',
                    'location_id' => $item->location_id ? (string) $item->location_id : '',
                    'quantity' => (string) $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                ])->all();
            } elseif ($this->ledger->product_id) {
                $this->items = [[
                    'product_id' => (string) $this->ledger->product_id,
                    'location_id' => (string) $this->ledger->location_id,
                    'quantity' => (string) $this->ledger->quantity,
                    'unit_price' => $this->ledger->quantity ? (string) ($this->ledger->amount / $this->ledger->quantity) : '0',
                ]];
            } else {
                $this->items = [];
            }
        } else {
            $this->authorize('create-pembukuan');
            $this->date = now()->format('Y-m-d');
            $this->items = [[
                'product_id' => (string) request()->query('produk', ''),
                'location_id' => '',
                'quantity' => '',
                'unit_price' => '0',
            ]];
            if ($this->items[0]['product_id']) {
                $product = Product::find($this->items[0]['product_id']);
                if ($product) {
                    $this->items[0]['unit_price'] = (string) ($this->type === 'income' ? $product->price : $product->cost);
                }
            }
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

    public function getCategoryListProperty()
    {
        return Category::orderBy('name')->get();
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

        foreach ($this->items as $index => $item) {
            $product = Product::find($item['product_id'] ?? null);
            if ($product) {
                $this->items[$index]['unit_price'] = (string) ($value === 'income' ? $product->price : $product->cost);
            }
        }
    }

    public function updatedItems($value, string $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $index = (int) $index;
        $this->activeItem = $index;

        if ($field === 'product_id') {
            if ($value === 'new') {
                $this->items[$index]['product_id'] = '';
                $this->dispatch('open-modal', 'modal-product');
            } elseif ($product = Product::find($value)) {
                $this->items[$index]['unit_price'] = (string) ($this->type === 'income' ? $product->price : $product->cost);
            }
        }

        if ($field === 'location_id' && $value === 'new') {
            $this->items[$index]['location_id'] = '';
            $this->dispatch('open-modal', 'modal-location');
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'location_id' => '', 'quantity' => '', 'unit_price' => '0'];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function getItemsTotalProperty(): float
    {
        return collect($this->items)->sum(fn (array $item) =>
            (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
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
        $this->authorize('create-lokasi');
        $this->validate([
            'newLocName' => 'required|string|max:255',
            'newLocDesc' => 'nullable|string',
            'newLocActive' => 'required|boolean',
        ]);

        $loc = Location::create([
            'name' => $this->newLocName,
            'description' => $this->newLocDesc,
            'is_active' => $this->newLocActive,
            'user_id' => auth()->id(),
        ]);

        $this->items[$this->activeItem]['location_id'] = (string) $loc->id;
        $this->newLocName = '';
        $this->newLocDesc = '';
        $this->newLocActive = true;
        $this->dispatch('close-modal');
    }

    public function saveNewCustomer()
    {
        $this->authorize('create-pelanggan');
        $this->validate([
            'newCustName' => 'required|string|max:255',
            'newCustPhone' => 'nullable|string|max:255',
            'newCustType' => 'required|in:seller,non_seller',
        ]);

        $cust = \App\Models\Customer::create([
            'name' => $this->newCustName,
            'phone' => $this->newCustPhone,
            'type' => $this->newCustType,
            'user_id' => auth()->id(),
        ]);

        $this->customer_id = (string) $cust->id;
        $this->newCustName = '';
        $this->newCustPhone = '';
        $this->newCustType = 'non_seller';
        $this->dispatch('close-modal');
    }

    public function saveNewProduct()
    {
        $this->authorize('create-stok');
        $this->validate([
            'newProdName' => 'required|string|max:255',
            'newProdSku' => 'nullable|string|max:255|unique:products,sku',
            'newProdCategoryId' => 'nullable|exists:categories,id',
            'newProdDescription' => 'nullable|string',
            'newProdPrice' => 'required|numeric|min:0|max:999999999999.99',
            'newProdCost' => 'required|numeric|min:0|max:999999999999.99',
            'newProdUnit' => 'required|string|max:255',
            'newProdMinStock' => 'required|integer|min:0|max:2147483647',
            'newProdWholesalePrice' => 'nullable|numeric|min:0|max:999999999999.99',
            'newProdWholesaleMinQty' => 'nullable|integer|min:1|max:2147483647',
            'newProdLeadTime' => 'required|integer|min:0|max:2147483647',
            'newProdActive' => 'required|boolean',
            'newProdImage' => 'nullable|image|max:2048',
        ]);

        $imagePath = $this->newProdImage?->store('products', 'public');
        $prod = Product::create([
            'category_id' => $this->newProdCategoryId ?: null,
            'name' => $this->newProdName,
            'sku' => $this->newProdSku ?: null,
            'description' => $this->newProdDescription ?: null,
            'price' => $this->newProdPrice,
            'wholesale_price' => $this->newProdWholesalePrice ?: null,
            'wholesale_min_qty' => $this->newProdWholesaleMinQty ?: null,
            'cost' => $this->newProdCost,
            'min_stock' => $this->newProdMinStock,
            'unit' => $this->newProdUnit,
            'image' => $imagePath,
            'is_active' => $this->newProdActive,
            'lead_time' => $this->newProdLeadTime,
            'user_id' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->items[$this->activeItem]['product_id'] = (string) $prod->id;
        $this->items[$this->activeItem]['unit_price'] = (string) ($this->type === 'income' ? $prod->price : $prod->cost);
        $this->newProdName = '';
        $this->newProdSku = '';
        $this->newProdCategoryId = '';
        $this->newProdDescription = '';
        $this->newProdPrice = '0';
        $this->newProdCost = '0';
        $this->newProdMinStock = '10';
        $this->newProdWholesalePrice = '';
        $this->newProdWholesaleMinQty = '';
        $this->newProdLeadTime = '0';
        $this->newProdActive = true;
        $this->newProdImage = null;
        $this->dispatch('close-modal');
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

        $tracksStock = in_array($this->title, ['Penjualan', 'Pembelian stok'], true);
        if (! $tracksStock) {
            $this->items = [];
            $this->stock_movement = '';
            $this->customer_id = null;
            $this->supplier_id = null;
        }

        $this->stock_movement = $this->title === 'Penjualan' ? 'out' : ($this->title === 'Pembelian stok' ? 'in' : '');

        if ($this->payment_status === 'paid') {
            $this->due_date = null;
        }

        $rules = [
            'type'           => ['required', 'in:income,expense'],
            'title'          => ['required', 'string', 'max:255'],
            'payment_status' => ['required', 'in:paid,unpaid'],
            'due_date'       => ['nullable', 'date'],
            'date'           => ['required', 'date'],
            'description'    => ['nullable', 'string'],
            'reference'      => [$tracksStock ? 'required' : 'nullable', 'string', 'max:255'],
            'customer_id'    => ['nullable', 'exists:customers,id'],
            'supplier_id'    => ['nullable', 'exists:suppliers,id'],
            'proof_image'    => ['nullable', 'image', 'max:2048'],
        ];

        if ($tracksStock) {
            $rules['items'] = ['required', 'array', 'min:1'];
            $rules['items.*.product_id'] = ['required', 'exists:products,id'];
            $rules['items.*.location_id'] = ['required', 'exists:locations,id'];
            $rules['items.*.quantity'] = ['required', 'integer', 'min:1', 'max:2147483647'];
            $rules['items.*.unit_price'] = ['required', 'numeric', 'min:0', 'max:999999999999.99'];
        } else {
            $rules['amount'] = ['required', 'numeric', 'min:0', 'max:999999999999.99'];
        }

        $validated = $this->validate($rules);
        $items = $tracksStock ? array_values($validated['items']) : [];
        unset($validated['items']);
        $validated['product_id'] = null;
        $validated['location_id'] = null;
        $validated['quantity'] = null;
        $validated['stock_movement'] = null;

        if ($tracksStock) {
            $validated['amount'] = number_format($this->itemsTotal, 2, '.', '');
            if ((float) $validated['amount'] > 999999999999.99) {
                $this->addError('items', 'Total transaksi melebihi batas nominal yang diizinkan.');
                return;
            }
        }

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
        } elseif ($this->modeEdit) {
            unset($validated['proof_image']);
        }

        try {
            DB::transaction(function () use ($validated, $items, $tracksStock): void {
                if ($tracksStock && $this->stock_movement === 'out') {
                    $requested = collect($items)->groupBy(fn (array $item) => $item['product_id'].'_'.$item['location_id'])->sortKeys();
                    $original = collect();
                    if ($this->modeEdit) {
                        $original = $this->ledger->items->groupBy(fn (LedgerItem $item) => $item->product_id.'_'.$item->location_id)
                        ->map(fn ($group) => $group->sum('quantity') * ($this->ledger->type === 'income' ? 1 : -1));
                        if ($this->ledger->stock_movement === 'out' && $this->ledger->product_id && $this->ledger->location_id) {
                            $key = $this->ledger->product_id.'_'.$this->ledger->location_id;
                            $original[$key] = ($original[$key] ?? 0) + (int) $this->ledger->quantity;
                    } elseif ($this->ledger->stock_movement === 'in' && $this->ledger->product_id && $this->ledger->location_id) {
                        $key = $this->ledger->product_id.'_'.$this->ledger->location_id;
                        $original[$key] = ($original[$key] ?? 0) - (int) $this->ledger->quantity;
                        }
                    }

                    foreach ($requested as $key => $group) {
                        [$productId, $locationId] = explode('_', $key, 2);
                        $stock = \App\Models\Stock::where('product_id', $productId)
                            ->where('location_id', $locationId)
                            ->lockForUpdate()
                            ->first();
                        $available = (int) ($stock?->quantity ?? 0) + (int) ($original[$key] ?? 0);
                        $quantity = $group->sum(fn (array $item) => (int) $item['quantity']);
                        if ($quantity > $available) {
                            $productName = Product::find($productId)?->name ?? 'Produk';
                            throw new \RuntimeException("Stok {$productName} tidak mencukupi di lokasi {$locationId}. Tersisa: {$available}.");
                        }
                    }
                }

                if ($this->modeEdit) {
                    $this->ledger->update($validated);
                    $ledger = $this->ledger;
                    $existingByLocation = $ledger->items()->get()->groupBy(fn (LedgerItem $item) =>
                        $item->product_id.'_'.$item->location_id
                    );
                } else {
                    $validated['user_id'] = auth()->id();
                    $validated['updated_by'] = auth()->id();
                    $ledger = Ledger::create($validated);
                    $existingByLocation = collect();
                }

                foreach ($items as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    $location = Location::findOrFail($itemData['location_id']);
                    $attributes = [
                        ...$itemData,
                        'product_name' => $product->name,
                        'location_name' => $location->name,
                    ];
                    $key = $product->id.'_'.$location->id;
                    $existing = $existingByLocation->get($key)?->shift();
                    if ($existing) {
                        $existing->update($attributes);
                    } else {
                        $ledger->items()->create($attributes);
                    }
                }

                foreach ($existingByLocation as $remainingItems) {
                    $remainingItems->each(fn (LedgerItem $item) => $item->delete());
                }
            });
        } catch (\RuntimeException $exception) {
            $this->addError('items', $exception->getMessage());
            return;
        }

        session()->flash('success', $this->modeEdit
            ? 'Catatan transaksi berhasil diperbarui.'
            : 'Transaksi berhasil dicatat.');

        session()->forget(['new_product_id', 'new_location_id']);

        $this->redirectRoute('pembukuan.index', navigate: true);
    }
}; ?>

<div class="space-y-6 max-w-3xl mx-auto"
     x-data="{ showLocModal: false, showProdModal: false, showCustModal: false }"
     @open-modal.window="
        if ($event.detail[0] === 'modal-location') showLocModal = true;
        if ($event.detail[0] === 'modal-product') showProdModal = true;
        if ($event.detail[0] === 'modal-customer') showCustModal = true;
     "
     @close-modal.window="showLocModal = false; showProdModal = false; showCustModal = false;"
     >

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#f5f6f3] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-lg bg-white border border-[#dce1de]/80 text-[#52646a] hover:text-[#315b50] hover:border-[#dce1de] shadow-none transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#17343d] rounded-lg">Formulir Pembukuan</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1">{{ $modeEdit ? 'Edit Transaksi' : 'Catat Transaksi Baru' }}</h1>
                <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">Lengkapi entri arus kas transaksi keuangan Anda.</p>
            </div>
        </div>
    </div>

    {{-- ── Form Card ── --}}
    <div class="bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <form wire:submit="simpan" class="p-6 space-y-5">

            {{-- Tipe Transaksi --}}
            <div>
                <label class="block text-xs font-bold text-[#52646a] mb-2 uppercase tracking-wider">Tipe Transaksi <span class="text-[#9c5252]">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3.5 border rounded-lg cursor-pointer transition-all
                                  {{ $type === 'income' ? 'border-[#527c70] bg-[#e8efeb]/60 ring-2 ring-[#2f6b5c]/20' : 'border-[#dce1de]/80 hover:border-[#cbd3d0] bg-white' }}">
                        <input wire:model.live="type" type="radio" value="income" class="sr-only" @click="playClick()" />
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $type === 'income' ? 'bg-[#527c70] text-white shadow-none' : 'bg-[#eef2ee] text-[#71817f]' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-extrabold {{ $type === 'income' ? 'text-[#2f6b5c]' : 'text-[#344950]' }}">Pemasukan</span>
                            <span class="text-[10px] text-[#71817f] font-medium">Uang masuk ke kas</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 border rounded-lg cursor-pointer transition-all
                                  {{ $type === 'expense' ? 'border-[#d9b8b5] bg-[#f7eceb]/60 ring-2 ring-[#9c5252]/20' : 'border-[#dce1de]/80 hover:border-[#cbd3d0] bg-white' }}">
                        <input wire:model.live="type" type="radio" value="expense" class="sr-only" @click="playClick()" />
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $type === 'expense' ? 'bg-[#9c5252] text-white shadow-none' : 'bg-[#eef2ee] text-[#71817f]' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-extrabold {{ $type === 'expense' ? 'text-[#9c5252]' : 'text-[#344950]' }}">Pengeluaran</span>
                            <span class="text-[10px] text-[#71817f] font-medium">Uang keluar dari kas</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Jenis Transaksi --}}
            <div>
                <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Jenis Transaksi <span class="text-[#9c5252]">*</span></label>
                <select wire:model.live="title"
                        class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none">
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
                @error('title') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            @if(in_array($title, ['Penjualan', 'Pembelian stok']))
                <div class="p-5 bg-[#f5f6f3]/80 border border-[#dce1de]/80 rounded-lg space-y-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#315b50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span class="text-xs font-extrabold text-[#344950] uppercase tracking-wider">Detail Barang per Lokasi</span>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" wire:click="addItem" class="text-xs font-extrabold text-[#315b50] hover:underline">+ Tambah baris barang</button>
                    </div>
                    @foreach($items as $index => $item)
                        <div wire:key="ledger-item-{{ $index }}" class="rounded-lg border border-[#dce1de] bg-white p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#64747a]">Barang {{ $index + 1 }}</span>
                                @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})" class="text-[11px] font-bold text-[#9c5252]">Hapus baris</button>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-[#52646a] mb-1.5">Produk</label>
                                    <select wire:model.live="items.{{ $index }}.product_id" class="w-full px-3 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs font-medium text-[#344950]">
                                        <option value="">— Pilih Produk —</option>
                                        @can('create-stok')<option value="new">+ Tambah Produk Baru...</option>@endcan
                                        @foreach($this->productList as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('items.'.$index.'.product_id') <p class="text-[#9c5252] text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[#52646a] mb-1.5">Lokasi Stok</label>
                                    <select wire:model.live="items.{{ $index }}.location_id" class="w-full px-3 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs font-medium text-[#344950]">
                                        <option value="">— Pilih Lokasi —</option>
                                        @can('create-lokasi')<option value="new">+ Tambah Lokasi Baru...</option>@endcan
                                        @foreach($this->locationList as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('items.'.$index.'.location_id') <p class="text-[#9c5252] text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-[#52646a] mb-1.5">Kuantitas</label>
                                    <input wire:model.live.debounce.300ms="items.{{ $index }}.quantity" type="number" min="1" placeholder="Misal: 5" class="w-full px-3 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs font-medium text-[#344950]" />
                                    @error('items.'.$index.'.quantity') <p class="text-[#9c5252] text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[#52646a] mb-1.5">Harga per unit (Rp)</label>
                                    <input wire:model.live.debounce.300ms="items.{{ $index }}.unit_price" type="number" min="0" step="0.01" class="w-full px-3 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs font-medium text-[#344950]" />
                                    @error('items.'.$index.'.unit_price') <p class="text-[#9c5252] text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <p class="flex items-end rounded-lg bg-[#f5f6f3] px-3 py-2.5 text-xs font-extrabold text-[#344950]">
                                    Subtotal: Rp {{ number_format((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                    @error('items') <p class="text-[#9c5252] text-xs font-semibold">{{ $message }}</p> @enderror
                    <div class="flex justify-end border-t border-[#dce1de] pt-3">
                        <p class="text-sm font-extrabold text-[#172c35]">Total barang: Rp {{ number_format($this->itemsTotal, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nomor Invoice <span class="text-[#9c5252]">*</span></label>
                        <input wire:model="reference" type="text" maxlength="255" placeholder="Contoh: INV-2026-001" required class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950]" />
                        @error('reference') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    @if($title === 'Penjualan')
                        <div class="border-t border-[#dce1de]/80 pt-4 mt-2">
                            <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Pelanggan <span class="text-[#71817f] font-normal">(opsional)</span></label>
                            <select wire:model.live="customer_id"
                                    class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none">
                                <option value="">— Pelanggan Umum —</option>
                                @can('create-pelanggan')
                                    <option value="new" class="font-bold text-[#315b50]">+ Tambah Pelanggan Baru...</option>
                                @endcan
                                @foreach($this->customerList as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->type == 'seller' ? 'Seller' : 'Non Seller' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($title === 'Pembelian stok')
                        <div class="border-t border-[#dce1de]/80 pt-4 mt-2">
                            <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Supplier <span class="text-[#71817f] font-normal">(opsional)</span></label>
                            <select wire:model="supplier_id"
                                    class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none">
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
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nominal Transaksi (Rp) <span class="text-[#9c5252]">*</span></label>
                    <input @if(in_array($title, ['Penjualan', 'Pembelian stok'])) value="{{ $this->itemsTotal }}" readonly @else wire:model="amount" @endif type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-extrabold text-[#172c35] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('amount') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Tanggal Transaksi <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="date" type="date"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('date') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Status Pembayaran --}}
            <div class="p-5 border rounded-lg space-y-3 transition-all {{ $payment_status === 'unpaid' ? 'border-[#ddcba7] bg-[#f7f1e4]/50' : 'border-[#dce1de]/80 bg-[#f5f6f3]/40' }}">
                <span class="block text-xs font-extrabold text-[#64747a] uppercase tracking-wider">Status Pembayaran</span>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer transition-all bg-white
                                  {{ $payment_status === 'paid' ? 'border-[#527c70] ring-2 ring-[#2f6b5c]/20' : 'border-[#dce1de]/80 hover:border-[#cbd3d0]' }}">
                        <input wire:model.live="payment_status" type="radio" value="paid" class="sr-only" />
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 {{ $payment_status === 'paid' ? 'bg-[#527c70] text-white' : 'bg-[#eef2ee] text-[#71817f]' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="text-xs sm:text-sm font-extrabold {{ $payment_status === 'paid' ? 'text-[#2f6b5c]' : 'text-[#344950]' }}">Lunas</span>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer transition-all bg-white
                                  {{ $payment_status === 'unpaid' ? 'border-[#a9823a] ring-2 ring-[#a9823a]/20' : 'border-[#dce1de]/80 hover:border-[#cbd3d0]' }}">
                        <input wire:model.live="payment_status" type="radio" value="unpaid" class="sr-only" />
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 {{ $payment_status === 'unpaid' ? 'bg-[#8a6a2f] text-white' : 'bg-[#eef2ee] text-[#71817f]' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-xs sm:text-sm font-extrabold {{ $payment_status === 'unpaid' ? 'text-[#5e4820]' : 'text-[#344950]' }}">Belum Lunas</span>
                    </label>
                </div>

                @if($payment_status === 'unpaid')
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-[#5e4820] mb-1.5 uppercase tracking-wider">Jatuh Tempo <span class="text-[#5e4820]/70 font-normal">(opsional)</span></label>
                        <input wire:model="due_date" type="date"
                               class="w-full px-4 py-2.5 bg-white border border-[#ddcba7] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#a9823a]/20 focus:border-[#a9823a] transition-all shadow-none" />
                        <p class="text-[11px] text-[#5e4820] mt-1.5 font-bold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Transaksi piutang/utang ini tidak mempengaruhi arus kas lunas hingga diselesaikan.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Optional Fields Toggle --}}
            <div>
                <button type="button" wire:click="$toggle('showOptional')" class="text-xs font-extrabold text-[#315b50] hover:text-[#17343d] flex items-center gap-1.5">
                    <svg class="w-4 h-4 transition-transform {{ $showOptional ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <span>{{ $showOptional ? 'Sembunyikan Informasi Tambahan' : 'Tampilkan Informasi Tambahan (Opsional)' }}</span>
                </button>
            </div>

            @if($showOptional)
                <div class="space-y-4 p-5 border border-[#dce1de] bg-[#f5f6f3]/30 rounded-lg">
                    @unless(in_array($title, ['Penjualan', 'Pembelian stok']))
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">No. Referensi <span class="text-[#71817f] font-normal">(opsional)</span></label>
                        <input wire:model="reference" type="text" placeholder="Misal: INV-2026-001"
                               class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    </div>
                    @endunless

                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Catatan Keterangan <span class="text-[#71817f] font-normal">(opsional)</span></label>
                        <textarea wire:model="description" rows="2" placeholder="Detail catatan tambahan seputar transaksi..."
                                  class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Foto Bukti Transaksi <span class="text-[#71817f] font-normal">(Max 2MB)</span></label>
                        @if($modeEdit && $ledger->proof_image)
                            <img src="{{ Storage::url($ledger->proof_image) }}" alt="Bukti Transaksi" class="w-24 h-24 object-cover rounded-lg border border-[#dce1de] mb-3 shadow-none" />
                        @endif
                        <input wire:model="proof_image" type="file" accept="image/*"
                               class="text-xs text-[#64747a] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#f5f6f3] file:text-[#17343d] hover:file:bg-[#e8efeb] transition-colors cursor-pointer" />
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-[#e6eae7]">
                <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</a>

                <button type="submit" @click="playSuccess()"
                        class="btn-sound px-6 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider text-white shadow-none transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed
                               {{ $type === 'income' ? 'bg-[#2f6b5c] hover:bg-[#2f6b5c] ' : 'bg-[#7a3d3d] hover:bg-[#7a3d3d] ' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>{{ $modeEdit ? 'Simpan Perubahan' : 'Catat Transaksi' }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Modal Tambah Lokasi --}}
    <div x-show="showLocModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 backdrop-blur-sm px-4">
        <div x-show="showLocModal" @click.outside="showLocModal = false" class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-[#dce1de]/80 bg-white p-6 shadow-none"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-[#172c35] mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#315b50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Tambah Lokasi Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Lokasi</label>
                    <input wire:model="newLocName" type="text" placeholder="Misal: Gudang Utamna" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                    @error('newLocName') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Deskripsi <span class="text-[#71817f] font-normal">(opsional)</span></label>
                    <textarea wire:model="newLocDesc" rows="2" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none resize-none"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-[#344950]">
                    <input wire:model="newLocActive" type="checkbox" class="rounded border-[#cbd3d0] text-[#315b50] focus:ring-[#527c70]" />
                    Lokasi aktif
                </label>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                    <button type="button" @click="showLocModal = false" class="px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewLocation" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#244e49] hover:bg-[#17343d] rounded-lg shadow-none  transition-all">Simpan Lokasi</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Produk --}}
    <div x-show="showProdModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 backdrop-blur-sm px-4">
        <div x-show="showProdModal" @click.outside="showProdModal = false" class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg border border-[#dce1de]/80 bg-white p-6 shadow-none"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-[#172c35] mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#315b50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Tambah Produk Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Produk</label>
                    <input wire:model="newProdName" type="text" placeholder="Misal: Nugget Ayam 500gr" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                    @error('newProdName') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Harga Jual</label>
                        <input wire:model="newProdPrice" type="number" min="0" step="0.01" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                        @error('newProdPrice') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Harga Modal</label>
                        <input wire:model="newProdCost" type="number" min="0" step="0.01" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                        @error('newProdCost') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Batas Minimum Stok</label>
                    <input wire:model="newProdMinStock" type="number" min="0" step="1" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                    @error('newProdMinStock') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Satuan</label>
                    <select wire:model="newProdUnit" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                        <option value="pcs">pcs</option>
                        <option value="pack">pack</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">SKU (opsional)</label>
                        <input wire:model="newProdSku" type="text" maxlength="255" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] shadow-none">
                        @error('newProdSku') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Kategori</label>
                        <select wire:model="newProdCategoryId" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] shadow-none">
                            <option value="">Tanpa kategori</option>
                            @foreach ($this->categoryList as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('newProdCategoryId') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Harga Grosir</label>
                        <input wire:model="newProdWholesalePrice" type="number" min="0" step="0.01" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] shadow-none">
                        @error('newProdWholesalePrice') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Min. Beli Grosir</label>
                        <input wire:model="newProdWholesaleMinQty" type="number" min="1" step="1" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] shadow-none">
                        @error('newProdWholesaleMinQty') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Lead Time (hari)</label>
                        <input wire:model="newProdLeadTime" type="number" min="0" step="1" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] shadow-none">
                        @error('newProdLeadTime') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 self-end pb-3 text-sm font-semibold text-[#344950]">
                        <input wire:model="newProdActive" type="checkbox" class="rounded border-[#cbd3d0] text-[#315b50] focus:ring-[#527c70]" />
                        Produk aktif
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Deskripsi</label>
                    <textarea wire:model="newProdDescription" rows="2" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] shadow-none"></textarea>
                    @error('newProdDescription') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Foto Produk (maks. 2 MB)</label>
                    <input wire:model="newProdImage" type="file" accept="image/*" class="block w-full text-sm text-[#64747a]">
                    @error('newProdImage') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                    <button type="button" @click="showProdModal = false" class="px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewProduct" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#244e49] hover:bg-[#17343d] rounded-lg shadow-none  transition-all">Simpan Produk</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Pelanggan --}}
    <div x-show="showCustModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 backdrop-blur-sm px-4">
        <div x-show="showCustModal" @click.outside="showCustModal = false" class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-none p-6 border border-[#dce1de]/80"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-[#172c35] mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#315b50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Tambah Pelanggan Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Pelanggan</label>
                    <input wire:model="newCustName" type="text" placeholder="Misal: Budi" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                    @error('newCustName') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nomor Telepon <span class="text-[#71817f] font-normal">(opsional)</span></label>
                    <input wire:model="newCustPhone" type="text" placeholder="Misal: 081234567890" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                    @error('newCustPhone') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Tipe Pelanggan</label>
                    <select wire:model="newCustType" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none">
                        <option value="non_seller">Non Seller (Umum)</option>
                        <option value="seller">Seller / Reseller</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                    <button type="button" @click="showCustModal = false" class="px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewCustomer" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#244e49] hover:bg-[#17343d] rounded-lg shadow-none  transition-all">Simpan Pelanggan</button>
                </div>
            </div>
        </div>
    </div>
</div>
