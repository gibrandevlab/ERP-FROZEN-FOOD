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
        $this->validate([
            'newCatName' => 'required|string|max:255',
            'newCatDesc' => 'nullable|string',
        ]);
        
        $cat = Category::create([
            'name' => $this->newCatName,
            'description' => $this->newCatDesc,
            'is_active' => true
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
        // Cek ulang izin saat submit — menutup celah bypass Livewire AJAX langsung
        $this->authorize($this->modeEdit ? 'edit-stok' : 'create-stok');

        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:50',
                              $this->modeEdit
                                  ? Rule::unique('products', 'sku')->ignore($this->product->id)
                                  : Rule::unique('products', 'sku'),
                             ],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'              => ['required', 'numeric', 'min:0'],
            'wholesale_price'    => ['nullable', 'numeric', 'min:0'],
            'wholesale_min_qty'  => ['nullable', 'integer', 'min:1'],
            'cost'               => ['required', 'numeric', 'min:0'],
            'unit'               => ['required', 'string', 'max:20'],
            'is_active'          => ['boolean'],
            'image'              => ['nullable', 'image', 'max:2048'],
            'lead_time'          => ['required', 'integer', 'min:0'],
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
<div class="space-y-5 max-w-xl mx-auto lg:max-w-none lg:max-w-xl"
     x-data="{ showCatModal: false }"
     @open-modal.window="if ($event.detail[0] === 'modal-category') showCatModal = true;"
     @close-modal.window="showCatModal = false;">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
        @if(session()->has('pembukuan_return_url'))
            <a href="{{ session('pembukuan_return_url') }}" wire:navigate class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
        @else
            <a href="{{ route('stok.index') }}" wire:navigate class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
        @endif
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">{{ $modeEdit ? "Edit: {$product->name}" : 'Tambah Produk Baru' }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Lengkapi form detail produk</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <form wire:submit="simpan" class="p-6 space-y-5">

            {{-- Nama & SKU --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Produk <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" placeholder="Misal: Nugget Ayam 500gr"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    @error('name') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">SKU <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="sku" type="text" placeholder="Misal: PRD-001"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    @error('sku') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Kategori --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                <select wire:model.live="category_id"
                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all">
                    <option value="">— Tanpa Kategori —</option>
                    <option value="new" class="font-bold text-blue-600">➕ Tambah Kategori Baru</option>
                    @foreach($this->kategoriList as $k)
                        <option value="{{ $k->id }}">{{ $k->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Harga Jual & Modal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Harga Jual (Rp) <span class="text-red-500">*</span></label>
                    <input wire:model="price" type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    @error('price') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Harga Modal (Rp) <span class="text-red-500">*</span></label>
                    <input wire:model="cost" type="number" min="0" step="100"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    @error('cost') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Harga Grosir & Min. Beli --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Harga Grosir (Rp) <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="wholesale_price" type="number" min="0" step="100" placeholder="Misal: 38000"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    <p class="text-[10px] text-slate-400 mt-1">Harga khusus untuk pembelian grosir/partai besar</p>
                    @error('wholesale_price') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Min. Beli Grosir <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="wholesale_min_qty" type="number" min="1" placeholder="Misal: 10"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    <p class="text-[10px] text-slate-400 mt-1">Jumlah minimum agar harga grosir berlaku</p>
                    @error('wholesale_min_qty') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Satuan, Lead Time & Status Aktif --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Satuan</label>
                    <select wire:model="unit"
                            class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all">
                        @foreach(['pcs','kg','gram','liter','ml','pack','lusin','karton','dus'] as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Lead Time Supplier (Hari) <span class="text-red-500">*</span></label>
                    <input wire:model="lead_time" type="number" min="0" placeholder="Misal: 3"
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                    @error('lead_time') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                </div>
                <div class="h-[46px] flex items-center">
                    <label class="flex items-center gap-3 w-full px-4 py-2.5 border border-slate-200 rounded-xl cursor-pointer hover:border-slate-300 transition-colors">
                        <input wire:model="is_active" type="checkbox"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm font-semibold text-slate-700">Produk Aktif</span>
                    </label>
                </div>
            </div>

            {{-- Deskripsi --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                <textarea wire:model="description" rows="3" placeholder="Catatan atau deskripsi produk..."
                          class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all resize-none"></textarea>
            </div>

            {{-- Foto --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Foto Produk <span class="text-slate-400 font-normal">(max 2MB, otomatis WebP)</span></label>
                @if($modeEdit && $product->image)
                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                         class="w-24 h-24 object-cover rounded-xl border border-slate-200 mb-3 shadow-sm" />
                @endif
                <input wire:model="image" type="file" accept="image/*"
                       class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 transition-colors" />
                @error('image') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-5 border-t border-slate-100 mt-6">
                @if(session()->has('pembukuan_return_url'))
                    <a href="{{ session('pembukuan_return_url') }}" wire:navigate class="btn-sound text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors">Batal</a>
                @else
                    <a href="{{ route('stok.index') }}" wire:navigate class="btn-sound text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors">Batal</a>
                @endif
                <button type="submit"
                        class="btn-sound px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 shadow-lg shadow-blue-200/50 hover:bg-blue-700 hover:shadow-blue-300/50 transition-all">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Produk' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Modal Tambah Kategori --}}
    <div x-show="showCatModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div x-show="showCatModal" @click.outside="showCatModal = false" class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Kategori Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Kategori</label>
                    <input wire:model="newCatName" type="text" placeholder="Misal: Frozen Food" class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('newCatName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi <span class="text-slate-400 font-normal">(ops)</span></label>
                    <textarea wire:model="newCatDesc" rows="2" class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                    <button type="button" @click="showCatModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
                    <button type="button" wire:click="saveNewCategory" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg shadow-blue-200/50 transition-all">Simpan Kategori</button>
                </div>
            </div>
        </div>
    </div>
</div>
