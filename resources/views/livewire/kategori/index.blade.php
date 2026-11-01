<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('view-kategori');
    }

    public function getKategoriProperty()
    {
        return Category::withCount('products')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->get();
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-kategori');
        $k = Category::withCount('products')->findOrFail($id);
        if ($k->products_count > 0) {
            session()->flash('error', "Kategori '{$k->name}' masih punya {$k->products_count} produk. Pindahkan produknya dulu.");
            return;
        }
        $k->delete();
        session()->flash('success', "Kategori '{$k->name}' berhasil dihapus.");
    }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold" style="color: #172c35;">Kategori Produk</h1>
            <p class="text-xs text-[#64747a] mt-0.5">{{ $this->kategori->count() }} kategori terdaftar</p>
        </div>
        <a href="{{ route('kategori.tambah') }}" wire:navigate @click="playClick()"
           class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white shadow-none  transition-all hover:opacity-90"
           style="background: linear-gradient(135deg, #244e49, #17343d);">
            <span>+</span><span class="hidden sm:inline">Tambah Kategori</span>
        </a>
    </div>

    {{-- ── Search ──────────────────────────────────────────────────────────── --}}
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari nama kategori..."
           class="w-full px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-sm text-[#344950] placeholder-[#9aa6a3] focus:outline-none focus:ring-2 focus:ring-[#527c70]/30 focus:border-[#b7d4ce] transition-all shadow-none" />

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg border border-[#e6eae7] overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(245,246,243,0.95), rgba(238,242,238,0.95));">
                <tr class="border-b border-[#e6eae7]">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-[#64747a] uppercase tracking-wider">Nama</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-[#64747a] uppercase tracking-wider hidden md:table-cell">Slug</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-[#64747a] uppercase tracking-wider">Produk</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-[#64747a] uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y border-[#e6eae7]">
                @forelse($this->kategori as $k)
                <tr class="hover:bg-[#f5f6f3]/30 transition-colors">
                    <td class="px-5 py-4 font-semibold text-[#344950]">{{ $k->name }}</td>
                    <td class="px-5 py-4 text-[#71817f] font-mono text-xs hidden md:table-cell">{{ $k->slug }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                                     {{ $k->products_count > 0 ? 'bg-[#f5f6f3] text-[#315b50]' : 'bg-[#eef2ee] text-[#71817f]' }}">
                            {{ $k->products_count }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('kategori.edit', $k->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound px-2.5 py-1 rounded-lg bg-[#f5f6f3] text-[#315b50] text-xs font-medium border border-[#dce1de]/60 hover:bg-[#e8efeb] transition-colors">Edit</a>
                            <button wire:click="hapus({{ $k->id }})" wire:confirm="Hapus kategori '{{ $k->name }}'?"
                                    @click="playDanger()"
                                    class="btn-sound px-2.5 py-1 rounded-lg bg-[#f7eceb] text-[#9c5252] text-xs font-medium border border-[#ead6d4]/60 hover:bg-[#f7eceb] transition-colors">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center">
                        <p class="text-[#71817f] text-sm">
                            @if($search) Tidak ada kategori yang cocok dengan "{{ $search }}".
                            @else Belum ada kategori. Tambahkan yang pertama!
                            @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
