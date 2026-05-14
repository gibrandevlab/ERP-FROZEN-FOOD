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
            $this->authorize('edit-categories');
            $this->modeEdit    = true;
            $this->kategori    = Category::where('slug', $slug)->firstOrFail();
            $this->name        = $this->kategori->name;
            $this->description = $this->kategori->description ?? '';
        } else {
            $this->authorize('create-categories');
        }
    }

    public function simpan(): void
    {
        $this->authorize($this->modeEdit ? 'edit-categories' : 'create-categories');
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->modeEdit) {
            $this->kategori->update($validated);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
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
           class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">{{ $modeEdit ? 'Edit Kategori' : 'Tambah Kategori' }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Lengkapi formulir di bawah ini</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <form wire:submit="simpan" class="p-6 space-y-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Kategori <span class="text-red-500">*</span></label>
                <input wire:model="name" type="text" placeholder="Contoh: Makanan Beku"
                       class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
                @error('name') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                <textarea wire:model="description" rows="3" placeholder="Penjelasan singkat..."
                          class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all resize-none"></textarea>
                @error('description') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
            </div>

            @if($modeEdit)
            <div class="text-xs text-slate-500 bg-slate-50 rounded-xl px-4 py-3 border border-slate-100">
                Slug: <code class="font-mono text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200">{{ $kategori->slug }}</code>
                <span class="ml-1">(otomatis)</span>
            </div>
            @endif

            <div class="flex items-center justify-between pt-5 border-t border-slate-100 mt-6">
                <a href="{{ route('kategori.index') }}" wire:navigate @click="playClick()" class="btn-sound text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors">Batal</a>
                <button type="submit" @click="playSuccess()"
                        class="btn-sound px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-blue-200/50 hover:opacity-90 transition-all"
                        style="background: linear-gradient(135deg, #2563EB, #4F46E5);">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                </button>
            </div>
        </form>
    </div>
</div>
