<?php

use App\Models\Location;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public ?Location $location = null;
    public bool $modeEdit = false;

    public string $name = '';
    public string $description = '';
    public bool $is_active = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->authorize('edit-lokasi'); 
            $this->modeEdit = true;
            $this->location = Location::findOrFail($id);
            $this->name = $this->location->name;
            $this->description = $this->location->description ?? '';
            $this->is_active = (bool) $this->location->is_active;
        } else {
            $this->authorize('create-lokasi');
        }
    }

    public function simpan(): void
    {
        $this->authorize($this->modeEdit ? 'edit-lokasi' : 'create-lokasi');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if ($this->modeEdit) {
            $this->location->update($validated);
            session()->flash('success', "Lokasi '{$this->name}' berhasil diperbarui.");
        } else {
            $loc = Location::create($validated);
            session()->flash('success', "Lokasi '{$this->name}' berhasil ditambahkan.");

            if (session()->has('pembukuan_return_url')) {
                session()->put('new_location_id', $loc->id);
                $this->redirect(session()->pull('pembukuan_return_url'), navigate: true);
                return;
            }
        }

        $this->redirectRoute('lokasi.index', navigate: true);
    }
}; ?>

<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        @if(session()->has('pembukuan_return_url'))
            <a href="{{ session('pembukuan_return_url') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition-colors text-lg">←</a>
        @else
            <a href="{{ route('lokasi.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition-colors text-lg">←</a>
        @endif
        <h1 class="text-base font-semibold text-gray-900">
            {{ $modeEdit ? "Edit Lokasi: {$name}" : 'Tambah Lokasi Baru' }}
        </h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form wire:submit="simpan" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lokasi <span class="text-red-500">*</span></label>
                <input wire:model="name" type="text" placeholder="Misal: Gudang Depan / Kulkas A"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-300" />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi <span class="text-gray-400 font-normal">(opsional)</span></label>
                <textarea wire:model="description" rows="3" placeholder="Penjelasan lokasi..."
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-300 resize-none"></textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <input wire:model="is_active" id="is_active" type="checkbox"
                       class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-0" />
                <label for="is_active" class="text-sm text-gray-700">Lokasi aktif (bisa dipilih untuk stok)</label>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                @if(session()->has('pembukuan_return_url'))
                    <a href="{{ session('pembukuan_return_url') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700 transition-colors">Batal</a>
                @else
                    <a href="{{ route('lokasi.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700 transition-colors">Batal</a>
                @endif
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:opacity-80 transition-opacity">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Lokasi' }}
                </button>
            </div>
        </form>
    </div>
</div>
