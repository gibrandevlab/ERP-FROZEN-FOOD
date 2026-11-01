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
            <h1 class="text-xl font-extrabold text-[#172c35]">Manajemen Lokasi</h1>
            <p class="text-xs text-[#64747a] mt-0.5">Daftar lokasi penyimpanan stok</p>
        </div>
        <a href="{{ route('lokasi.tambah') }}" wire:navigate
           class="px-4 py-2 bg-[#17343d] text-white rounded-lg text-sm font-semibold hover:opacity-90">
            + Tambah Lokasi
        </a>
    </div>

    @if (session('error'))
        <div class="p-3 bg-[#f7eceb] text-[#9c5252] rounded-lg text-sm font-medium border border-[#ead6d4]">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-[#e6eae7] overflow-hidden shadow-none">
        <table class="w-full text-sm">
            <thead class="bg-[#f5f6f3]">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold text-[#52646a]">Nama Lokasi</th>
                    <th class="px-5 py-3 text-left font-semibold text-[#52646a]">Deskripsi</th>
                    <th class="px-5 py-3 text-center font-semibold text-[#52646a]">Status</th>
                    <th class="px-5 py-3 text-right font-semibold text-[#52646a]">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y border-[#e6eae7]">
                @forelse($this->lokasiList as $l)
                <tr class="hover:bg-[#f5f6f3]/50">
                    <td class="px-5 py-4 font-medium text-[#172c35]">{{ $l->name }}</td>
                    <td class="px-5 py-4 text-[#64747a]">{{ $l->description ?? '-' }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $l->is_active ? 'bg-[#e8efeb] text-[#2f6b5c]' : 'bg-[#eef2ee] text-[#64747a]' }}">
                            {{ $l->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('lokasi.edit', $l->id) }}" wire:navigate class="px-2 py-1 bg-[#f5f6f3] text-[#315b50] rounded text-xs font-medium hover:bg-[#e8efeb]">Edit</a>
                            <button wire:click="hapus({{ $l->id }})" wire:confirm="Hapus lokasi '{{ $l->name }}'?" class="px-2 py-1 bg-[#f7eceb] text-[#9c5252] rounded text-xs font-medium hover:bg-[#f7eceb]">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-[#71817f]">Belum ada lokasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $this->lokasiList->links() }}
</div>
