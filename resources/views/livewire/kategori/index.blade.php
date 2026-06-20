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
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">Kategori Produk</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $this->kategori->count() }} kategori terdaftar</p>
        </div>
        <a href="{{ route('kategori.tambah') }}" wire:navigate @click="playClick()"
           class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-blue-200/50 transition-all hover:opacity-90"
           style="background: linear-gradient(135deg, #2563EB, #4F46E5);">
            <span>+</span><span class="hidden sm:inline">Tambah Kategori</span>
        </a>
    </div>

    {{-- ── Search ──────────────────────────────────────────────────────────── --}}
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari nama kategori..."
           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" />

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nama</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Slug</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Produk</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($this->kategori as $k)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $k->name }}</td>
                    <td class="px-5 py-4 text-slate-400 font-mono text-xs hidden md:table-cell">{{ $k->slug }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                                     {{ $k->products_count > 0 ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-400' }}">
                            {{ $k->products_count }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('kategori.edit', $k->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-xs font-medium border border-blue-100/60 hover:bg-blue-100 transition-colors">Edit</a>
                            <button wire:click="hapus({{ $k->id }})" wire:confirm="Hapus kategori '{{ $k->name }}'?"
                                    @click="playDanger()"
                                    class="btn-sound px-2.5 py-1 rounded-lg bg-red-50 text-red-500 text-xs font-medium border border-red-100/60 hover:bg-red-100 transition-colors">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center">
                        <p class="text-slate-400 text-sm">
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
