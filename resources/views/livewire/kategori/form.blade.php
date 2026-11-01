<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?Category $kategori = null;
    public bool      $modeEdit = false;
    public string    $name        = '';
    public string    $description = '';

    public function mount(?string $slug = null): void
    {
        if ($slug) {
            $this->authorize('edit-kategori');
            $this->modeEdit    = true;
            $this->kategori    = Category::where('slug', $slug)->firstOrFail();
            $this->name        = $this->kategori->name;
            $this->description = $this->kategori->description ?? '';
        } else {
            $this->authorize('create-kategori');
        }
    }

    public function simpan(): void
    {
        $this->authorize($this->modeEdit ? 'edit-kategori' : 'create-kategori');
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->modeEdit) {
            $this->kategori->update($validated);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
            $validated['user_id'] = auth()->id();
            Category::create($validated);
            session()->flash('success', 'Kategori baru berhasil ditambahkan.');
        }
        $this->redirectRoute('kategori.index', navigate: true);
    }
}; ?>

<div class="space-y-5 max-w-lg mx-auto lg:max-w-none lg:max-w-lg">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('kategori.index') }}" wire:navigate @click="playClick()"
           class="btn-sound w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-[#dce1de] text-[#64747a] hover:text-[#344950] hover:bg-[#f5f6f3] shadow-none transition-all shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-extrabold" style="color: #172c35;">{{ $modeEdit ? 'Edit Kategori' : 'Tambah Kategori' }}</h1>
            <p class="text-xs text-[#64747a] mt-0.5">Lengkapi formulir di bawah ini</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-lg border border-[#e6eae7] overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <form wire:submit="simpan" class="p-6 space-y-5">

            <div>
                <label class="block text-sm font-semibold text-[#344950] mb-1.5">Nama Kategori <span class="text-[#9c5252]">*</span></label>
                <input wire:model="name" type="text" placeholder="Contoh: Makanan Beku"
                       class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-sm text-[#344950] placeholder-[#9aa6a3] focus:outline-none focus:ring-2 focus:ring-[#527c70]/30 focus:border-[#b7d4ce] shadow-none transition-all" />
                @error('name') <p class="text-[#9c5252] text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-[#344950] mb-1.5">Deskripsi <span class="text-[#71817f] font-normal">(opsional)</span></label>
                <textarea wire:model="description" rows="3" placeholder="Penjelasan singkat..."
                          class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-sm text-[#344950] placeholder-[#9aa6a3] focus:outline-none focus:ring-2 focus:ring-[#527c70]/30 focus:border-[#b7d4ce] shadow-none transition-all resize-none"></textarea>
                @error('description') <p class="text-[#9c5252] text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            @if($modeEdit)
            <div class="text-xs text-[#64747a] bg-[#f5f6f3] rounded-lg px-4 py-3 border border-[#e6eae7]">
                Slug: <code class="font-mono text-[#344950] bg-white px-1.5 py-0.5 rounded border border-[#dce1de]">{{ $kategori->slug }}</code>
                <span class="ml-1">(otomatis)</span>
            </div>
            @endif

            <div class="flex items-center justify-between pt-5 border-t border-[#e6eae7] mt-6">
                <a href="{{ route('kategori.index') }}" wire:navigate @click="playClick()" class="btn-sound text-sm font-semibold text-[#64747a] hover:text-[#344950] transition-colors">Batal</a>
                <button type="submit" @click="playSuccess()"
                        class="btn-sound px-6 py-2.5 rounded-lg text-sm font-semibold text-white shadow-none  hover:opacity-90 transition-all"
                        style="background: linear-gradient(135deg, #244e49, #17343d);">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                </button>
            </div>
        </form>
    </div>
</div>
