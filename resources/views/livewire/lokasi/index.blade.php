<?php

use App\Models\Location;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public function getLokasiListProperty()
    {
        return Location::withCount('stocks')->orderBy('name')->paginate(20);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-lokasi');
        $loc = Location::findOrFail($id);
        if ($loc->stocks()->exists() || \App\Models\Ledger::where('location_id', $loc->id)->exists()) {
            session()->flash('error', 'Lokasi tidak dapat dihapus karena masih digunakan.');
            return;
        }
        $loc->delete();
        session()->flash('success', 'Lokasi berhasil dihapus.');
    }
}; ?>

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900">Manajemen Lokasi</h1>
            <p class="text-xs text-gray-500 mt-0.5">Daftar lokasi penyimpanan stok</p>
        </div>
        <a href="{{ route('lokasi.tambah') }}" wire:navigate
           class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-semibold hover:opacity-90">
            + Tambah Lokasi
        </a>
    </div>

    @if (session('error'))
        <div class="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium border border-red-100">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Nama Lokasi</th>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Deskripsi</th>
                    <th class="px-5 py-3 text-center font-semibold text-gray-600">Status</th>
                    <th class="px-5 py-3 text-right font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($this->lokasiList as $l)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-4 font-medium text-gray-900">{{ $l->name }}</td>
                    <td class="px-5 py-4 text-gray-500">{{ $l->description ?? '-' }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $l->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                            {{ $l->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('lokasi.edit', $l->id) }}" wire:navigate class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-medium hover:bg-blue-100">Edit</a>
                            <button wire:click="hapus({{ $l->id }})" wire:confirm="Hapus lokasi '{{ $l->name }}'?" class="px-2 py-1 bg-red-50 text-red-500 rounded text-xs font-medium hover:bg-red-100">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Belum ada lokasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $this->lokasiList->links() }}
</div>
