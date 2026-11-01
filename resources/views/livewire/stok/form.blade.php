<?php

use App\Models\{Product, Category};
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public ?Product $product  = null;
    public bool     $modeEdit = false;

    public string  $name        = '';
    public string  $sku         = '';
    public ?string $category_id = null;
    public string  $description = '';
    public string  $min_stock   = '10';

    public string $newCatName = '';
    public string $newCatDesc = '';

    public function updatedCategoryId($value)
    {
        if ($value === 'new') {
            $this->category_id = null;
            $this->dispatch('open-modal', 'modal-category');
        }
    }

    public function saveNewCategory()
    {
        $this->authorize('create-kategori');
        $this->validate([
            'newCatName' => 'required|string|max:255',
            'newCatDesc' => 'nullable|string',
        ]);

        $cat = Category::create([
            'name' => $this->newCatName,
            'description' => $this->newCatDesc,
            'user_id' => auth()->id(),
        ]);

        $this->category_id = (string) $cat->id;
        $this->newCatName = '';
        $this->newCatDesc = '';
        $this->dispatch('close-modal');
    }

    public string  $price              = '0';
    public string  $wholesale_price    = '';
    public string  $wholesale_min_qty  = '';
    public string  $cost               = '0';
    public string  $unit               = 'pcs';
    public bool    $is_active          = true;
    public string  $lead_time          = '0';
    public         $image              = null;

    public function mount(?string $slug = null): void
    {
        if ($slug) {
            $this->authorize('edit-stok');
            $this->modeEdit = true;
            $this->product  = Product::where('slug', $slug)->firstOrFail();
            $this->fill($this->product->only(['name', 'sku', 'category_id', 'description', 'unit', 'is_active']));
            $this->price             = (string) $this->product->price;
            $this->cost              = (string) $this->product->cost;
            $this->wholesale_price   = (string) ($this->product->wholesale_price ?? '');
            $this->wholesale_min_qty = (string) ($this->product->wholesale_min_qty ?? '');
            $this->lead_time         = (string) ($this->product->lead_time ?? '0');
            $this->min_stock         = (string) ($this->product->min_stock ?? '10');
        } else {
            $this->authorize('create-stok');
        }
    }

    public function getKategoriListProperty()
    {
        return Category::orderBy('name')->get();
    }

    public function simpan(): void
    {
        $this->authorize($this->modeEdit ? 'edit-stok' : 'create-stok');

        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:255',
                              $this->modeEdit
                                  ? Rule::unique('products', 'sku')->ignore($this->product->id)
                                  : Rule::unique('products', 'sku'),
                             ],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'              => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'wholesale_price'    => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'wholesale_min_qty'  => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'cost'               => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'min_stock'          => ['required', 'integer', 'min:0'],
            'unit'               => ['required', 'string', 'max:255'],
            'is_active'          => ['boolean'],
            'image'              => ['nullable', 'image', 'max:2048'],
            'lead_time'          => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        if ($this->image) {
            $imageObj = imagecreatefromstring(file_get_contents($this->image->getRealPath()));
            $filename = \Illuminate\Support\Str::uuid() . '.webp';
            $path = storage_path('app/public/products');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            imagewebp($imageObj, $path . '/' . $filename, 80);
            imagedestroy($imageObj);
            $validated['image'] = 'products/' . $filename;
        } elseif ($this->modeEdit) {
            unset($validated['image']);
        }

        if ($this->modeEdit) {
            $this->product->update($validated);
            session()->flash('success', "Produk '{$this->product->name}' berhasil diperbarui.");
        } else {
            $validated['user_id'] = auth()->id();
            $validated['updated_by'] = auth()->id();
            $prod = Product::create($validated);
            session()->flash('success', "Produk '{$validated['name']}' berhasil ditambahkan.");

            if (session()->has('pembukuan_return_url')) {
                session()->put('new_product_id', $prod->id);
                $this->redirect(session()->pull('pembukuan_return_url'), navigate: true);
                return;
            }
        }

        $this->redirectRoute('stok.index', navigate: true);
    }
}; ?>

<div class="space-y-6 max-w-3xl mx-auto"
     x-data="{ showCatModal: false }"
     @open-modal.window="if ($event.detail[0] === 'modal-category') showCatModal = true;"
     @close-modal.window="showCatModal = false;">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#f5f6f3] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ session()->has('pembukuan_return_url') ? session('pembukuan_return_url') : route('stok.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-lg bg-white border border-[#dce1de]/80 text-[#52646a] hover:text-[#315b50] hover:border-[#dce1de] shadow-none transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#17343d] rounded-lg">Form Inventaris</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1">{{ $modeEdit ? "Edit Produk: {$product->name}" : 'Tambah Produk Baru' }}</h1>
                <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">Lengkapi formulir di bawah ini dengan data yang valid.</p>
            </div>
        </div>
    </div>

    {{-- ── Form Card ── --}}
    <div class="bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <form wire:submit="simpan" class="p-6 space-y-5">

            {{-- Nama & SKU --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Produk <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="name" type="text" placeholder="Misal: Nugget Ayam 500gr"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('name') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">SKU Produk <span class="text-[#71817f] font-normal">(opsional)</span></label>
                    <input wire:model="sku" type="text" placeholder="Misal: PRD-001"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('sku') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Kategori --}}
            <div>
                <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Kategori Produk</label>
                <select wire:model.live="category_id"
                        class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none">
                    <option value="">— Tanpa Kategori —</option>
                    @can('create-kategori')
                        <option value="new" class="font-bold text-[#315b50]">+ Tambah Kategori Baru...</option>
                    @endcan
                    @foreach($this->kategoriList as $k)
                        <option value="{{ $k->id }}">{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Harga Jual & Modal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Harga Jual (Rp) <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="price" type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-extrabold text-[#172c35] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('price') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Harga Modal (Rp) <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="cost" type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-extrabold text-[#172c35] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('cost') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Batas Minimum Stok <span class="text-[#9c5252]">*</span></label>
                <input wire:model="min_stock" type="number" min="0" step="1"
                       class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                @error('min_stock') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            {{-- Harga Grosir & Min. Beli --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Harga Grosir (Rp) <span class="text-[#71817f] font-normal">(opsional)</span></label>
                    <input wire:model="wholesale_price" type="number" min="0" step="100" placeholder="Misal: 38000"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-extrabold text-[#172c35] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    <p class="text-[10px] text-[#71817f] font-medium mt-1">Harga khusus untuk pembelian partai besar</p>
                    @error('wholesale_price') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Min. Beli Grosir <span class="text-[#71817f] font-normal">(opsional)</span></label>
                    <input wire:model="wholesale_min_qty" type="number" min="1" placeholder="Misal: 10"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-extrabold text-[#172c35] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    <p class="text-[10px] text-[#71817f] font-medium mt-1">Jumlah minimum agar harga grosir berlaku</p>
                    @error('wholesale_min_qty') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Satuan, Lead Time & Status Aktif --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Satuan</label>
                    <select wire:model="unit"
                            class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none">
                        @foreach(['pcs','kg','gram','liter','ml','pack','lusin','karton','dus'] as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Lead Time Supplier (Hari) <span class="text-[#9c5252]">*</span></label>
                    <input wire:model="lead_time" type="number" min="0" placeholder="Misal: 3"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
                    @error('lead_time') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div class="h-[46px] flex items-center">
                    <label class="flex items-center gap-3 w-full px-4 py-2.5 border border-[#dce1de]/80 bg-[#f5f6f3] rounded-lg cursor-pointer hover:border-[#cbd3d0] transition-colors">
                        <input wire:model="is_active" type="checkbox"
                               class="w-4 h-4 rounded-md text-[#315b50] border-[#cbd3d0] focus:ring-[#527c70]/20 cursor-pointer" />
                        <span class="text-xs sm:text-sm font-extrabold text-[#344950]">Status Produk Aktif</span>
                    </label>
                </div>
            </div>

            {{-- Deskripsi --}}
            <div>
                <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Deskripsi <span class="text-[#71817f] font-normal">(opsional)</span></label>
                <textarea wire:model="description" rows="3" placeholder="Catatan atau deskripsi rinci tentang produk..."
                          class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none resize-none"></textarea>
            </div>

            {{-- Foto --}}
            <div>
                <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Foto Produk <span class="text-[#71817f] font-normal">(Max 2MB)</span></label>
                @if($modeEdit && $product->image)
                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                         class="w-20 h-20 object-cover rounded-lg border border-[#dce1de] mb-3 shadow-none" />
                @endif
                <input wire:model="image" type="file" accept="image/*"
                       class="text-xs text-[#64747a] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#f5f6f3] file:text-[#17343d] hover:file:bg-[#e8efeb] transition-colors cursor-pointer" />
                @error('image') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-[#e6eae7]">
                <a href="{{ session()->has('pembukuan_return_url') ? session('pembukuan_return_url') : route('stok.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</a>

                <button type="submit" @click="playSuccess()"
                        class="btn-sound px-6 py-2.5 bg-[#244e49] text-white rounded-lg text-xs font-bold uppercase tracking-wider shadow-none  hover:bg-[#17343d] transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>{{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Produk' }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Modal Tambah Kategori --}}
    <div x-show="showCatModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 backdrop-blur-sm px-4">
        <div x-show="showCatModal" @click.outside="showCatModal = false" class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-none p-6 border border-[#dce1de]/80"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-base font-extrabold text-[#172c35] mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#315b50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                Tambah Kategori Baru
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Nama Kategori</label>
                    <input wire:model="newCatName" type="text" placeholder="Misal: Frozen Food"
                           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none">
                    @error('newCatName') <p class="text-[#9c5252] text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Deskripsi <span class="text-[#71817f] font-normal">(opsional)</span></label>
                    <textarea wire:model="newCatDesc" rows="2" class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none resize-none"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e6eae7]">
                    <button type="button" @click="showCatModal = false" class="px-4 py-2 text-xs font-bold text-[#64747a] hover:text-[#344950] uppercase tracking-wider">Batal</button>
                    <button type="button" wire:click="saveNewCategory" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#244e49] hover:bg-[#17343d] rounded-lg shadow-none  transition-all">Simpan Kategori</button>
                </div>
            </div>
        </div>
    </div>
</div>
