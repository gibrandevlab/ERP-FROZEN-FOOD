<?php

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending';
    public bool $showRejectForm = false;
    public bool $showDetail = false;
    public ?int $actionId = null;
    public ?int $detailId = null;
    public string $rejection_reason = '';

    public function mount(): void
    {
        $this->authorize('view-purchase_approval');
    }

    public function getRequestsProperty()
    {
        return PurchaseRequest::with(['product', 'location', 'supplier', 'requestedBy', 'reviewedBy'])
            ->when($this->search, fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('ticket_number', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('requestedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$this->search}%"))))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    public function getStatsProperty(): array
    {
        $pending = PurchaseRequest::where('status', 'pending')->with('product')->get();

        return [
            'pending_count'  => $pending->count(),
            'pending_value'  => round($pending->sum(fn (PurchaseRequest $request) =>
                (float) ($request->product?->cost ?? 0) * (int) $request->requested_qty
            ), 2),
            'approved_today' => PurchaseRequest::where('status', 'approved')->whereDate('reviewed_at', today('Asia/Jakarta'))->count(),
            'rejected_today' => PurchaseRequest::where('status', 'rejected')->whereDate('reviewed_at', today('Asia/Jakarta'))->count(),
        ];
    }

    public function getDetailProperty(): ?PurchaseRequest
    {
        if (! $this->detailId) {
            return null;
        }

        return PurchaseRequest::with(['product', 'location', 'supplier', 'requestedBy', 'reviewedBy', 'purchasedBy', 'receivedBy'])
            ->find($this->detailId);
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function approve(int $id): void
    {
        $this->authorize('edit-purchase_approval');

        DB::transaction(function () use ($id): void {
            $request = PurchaseRequest::lockForUpdate()->findOrFail($id);
            abort_unless($request->status === 'pending', 403, 'Hanya permintaan pending yang dapat disetujui.');

            $request->update([
                'status'           => 'approved',
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
                'rejection_reason' => null,
            ]);
        });

        session()->flash('success', 'Permintaan pembelian disetujui dan diteruskan ke proses purchasing.');
    }

    public function openReject(int $id): void
    {
        $this->authorize('edit-purchase_approval');

        $request = PurchaseRequest::findOrFail($id);
        abort_unless($request->status === 'pending', 403, 'Hanya permintaan pending yang dapat ditolak.');

        $this->actionId = $id;
        $this->rejection_reason = '';
        $this->showDetail = false;
        $this->showRejectForm = true;
    }

    public function reject(): void
    {
        $this->authorize('edit-purchase_approval');

        $validated = $this->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min'      => 'Alasan penolakan minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($validated): void {
            $request = PurchaseRequest::lockForUpdate()->findOrFail($this->actionId);
            abort_unless($request->status === 'pending', 403, 'Hanya permintaan pending yang dapat ditolak.');

            $request->update([
                'status'           => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
            ]);
        });

        $this->showRejectForm = false;
        $this->actionId = null;
        $this->rejection_reason = '';
        session()->flash('success', 'Permintaan pembelian ditolak.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6 max-w-6xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#f5f6f3] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#17343d] rounded-lg">Approval Workflow</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#f7f1e4]/80 text-[#5e4820] rounded-lg">Pemisahan Tugas</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-[#315b50] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Persetujuan Pembelian
            </h1>
            <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-1 max-w-2xl">
                Tinjau pengajuan dari gudang, lalu setujui atau tolak. Modul ini memakai RBAC terpisah dari pembuatan PR.
            </p>
        </div>

        <div class="shrink-0 rounded-lg bg-white border border-[#ddcba7]/80 px-4 py-3 shadow-none text-center">
            <p class="text-[10px] uppercase font-extrabold text-[#6b5226] tracking-wider">Menunggu</p>
            <p class="text-2xl font-extrabold text-[#5e4820]">{{ $this->stats['pending_count'] }}</p>
            <p class="text-[10px] font-semibold text-[#71817f]">permintaan</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-[#b7d4ce] bg-[#e8efeb] p-3 text-sm font-semibold text-[#2f6b5c]">{{ session('success') }}</div>
    @endif

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="bg-white rounded-lg border border-[#ddcba7]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Menunggu</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#5e4820] tracking-tight">{{ $this->stats['pending_count'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f7f1e4] text-[#5e4820]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Perlu keputusan</p>
        </div>

        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Nilai Pengajuan</span>
                    <p class="mt-2 text-lg sm:text-2xl font-extrabold text-[#17343d] tracking-tight">Rp {{ number_format($this->stats['pending_value'], 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f5f6f3] text-[#315b50]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Estimasi qty × HPP</p>
        </div>

        <div class="bg-white rounded-lg border border-[#b7d4ce]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Disetujui Hari Ini</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#2f6b5c] tracking-tight">{{ $this->stats['approved_today'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#e8efeb] text-[#2f6b5c]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Diteruskan ke purchasing</p>
        </div>

        <div class="bg-white rounded-lg border border-[#ead6d4]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Ditolak Hari Ini</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#9c5252] tracking-tight">{{ $this->stats['rejected_today'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f7eceb] text-[#9c5252]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Dikembalikan ke gudang</p>
        </div>
    </div>

    {{-- ── Filter ── --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari tiket, produk, atau pemohon"
               class="min-w-0 flex-1 rounded-lg border-[#cbd3d0] text-sm focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]" />
        <select wire:model.live="statusFilter" class="rounded-lg border-[#cbd3d0] text-sm sm:w-52 focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]">
            <option value="">Semua status</option>
            <option value="pending">Menunggu Persetujuan</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
            <option value="purchased">Sudah Dibeli</option>
            <option value="completed">Selesai</option>
        </select>
    </div>

    {{-- ── Tabel Persetujuan ── --}}
    <div class="overflow-hidden rounded-lg border border-[#dce1de] bg-white shadow-none">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-[#f5f6f3] text-[10px] uppercase text-[#64747a]">
                    <tr>
                        <th class="px-4 py-3">Tiket / Produk</th>
                        <th class="px-4 py-3">Pemohon</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Estimasi</th>
                        <th class="px-4 py-3">Diajukan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y border-[#e6eae7]">
                    @forelse ($this->requests as $request)
                        <tr wire:key="approval-{{ $request->id }}" class="hover:bg-[#f5f6f3]/60">
                            <td class="px-4 py-3">
                                <div class="font-bold text-[#172c35]">{{ $request->ticket_number }}</div>
                                <div class="text-[#52646a]">{{ $request->product?->name ?? 'Produk dihapus' }}</div>
                                @if ($request->request_notes)
                                    <div class="mt-1 max-w-sm text-xs text-[#71817f] line-clamp-2">{{ $request->request_notes }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[#52646a]">{{ $request->requestedBy?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-[#52646a]">{{ $request->location?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold text-[#344950]">
                                {{ number_format($request->requested_qty, 0, ',', '.') }}
                                <div class="text-[10px] font-medium text-[#71817f]">{{ $request->product?->unit }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-[#344950]">
                                Rp {{ number_format((float) ($request->product?->cost ?? 0) * (int) $request->requested_qty, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-[#64747a] text-xs">
                                {{ $request->created_at?->translatedFormat('d M Y') }}
                                <div class="text-[10px] text-[#71817f]">{{ $request->created_at?->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide',
                                    'bg-[#f7f1e4] text-[#5e4820]' => $request->status === 'pending',
                                    'bg-[#f5f6f3] text-[#17343d]' => $request->status === 'approved',
                                    'bg-[#f7eceb] text-[#9c5252]' => $request->status === 'rejected',
                                    'bg-[#f5f6f3] text-[#17343d]' => $request->status === 'purchased',
                                    'bg-[#e8efeb] text-[#2f6b5c]' => $request->status === 'completed',
                                ])>{{ $request->status === 'pending' ? 'Menunggu' : ucfirst($request->status) }}</span>
                                @if ($request->status === 'rejected' && $request->rejection_reason)
                                    <div class="mt-1 max-w-[14rem] text-[10px] text-[#9c5252]">{{ $request->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2 whitespace-nowrap">
                                    <button wire:click="openDetail({{ $request->id }})" class="text-xs font-bold text-[#52646a] hover:underline">Detail</button>
                                    @if ($request->status === 'pending')
                                        @can('edit-purchase_approval')
                                            <button wire:click="approve({{ $request->id }})" wire:confirm="Setujui permintaan ini?" class="text-xs font-bold text-[#2f6b5c] hover:underline">Setujui</button>
                                            <button wire:click="openReject({{ $request->id }})" class="text-xs font-bold text-[#9c5252] hover:underline">Tolak</button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-[#71817f]">
                                Tidak ada permintaan pembelian pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $this->requests->links() }}

    {{-- ── Modal Detail ── --}}
    @if ($showDetail && $this->detail)
        @php $detail = $this->detail; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 p-4">
            <div class="max-h-[90vh] w-full max-w-lg space-y-4 overflow-y-auto rounded-lg bg-white p-6 shadow-none">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] uppercase font-extrabold tracking-wider text-[#71817f]">Detail Permintaan</p>
                        <h2 class="text-lg font-extrabold text-[#172c35]">{{ $detail->ticket_number }}</h2>
                    </div>
                    <button type="button" wire:click="$set('showDetail', false)" class="rounded-lg p-2 text-[#71817f] hover:bg-[#eef2ee]">✕</button>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div class="col-span-2 rounded-lg bg-[#f5f6f3] p-3">
                        <dt class="text-[10px] uppercase font-bold text-[#71817f]">Produk</dt>
                        <dd class="font-bold text-[#172c35]">{{ $detail->product?->name ?? 'Produk dihapus' }}</dd>
                    </div>
                    <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Jumlah</dt><dd class="font-semibold text-[#344950]">{{ number_format($detail->requested_qty, 0, ',', '.') }} {{ $detail->product?->unit }}</dd></div>
                    <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Estimasi Biaya</dt><dd class="font-semibold text-[#344950]">Rp {{ number_format((float) ($detail->product?->cost ?? 0) * (int) $detail->requested_qty, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Lokasi</dt><dd class="font-semibold text-[#344950]">{{ $detail->location?->name ?? '-' }}</dd></div>
                    <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Supplier</dt><dd class="font-semibold text-[#344950]">{{ $detail->supplier?->name ?? 'Belum ditentukan' }}</dd></div>
                    <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Pemohon</dt><dd class="font-semibold text-[#344950]">{{ $detail->requestedBy?->name ?? '-' }}</dd></div>
                    <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Diajukan</dt><dd class="font-semibold text-[#344950]">{{ $detail->created_at?->translatedFormat('d M Y H:i') }}</dd></div>
                    @if ($detail->reviewedBy)
                        <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Direview Oleh</dt><dd class="font-semibold text-[#344950]">{{ $detail->reviewedBy->name }}</dd></div>
                        <div><dt class="text-[10px] uppercase font-bold text-[#71817f]">Tanggal Review</dt><dd class="font-semibold text-[#344950]">{{ $detail->reviewed_at?->translatedFormat('d M Y H:i') }}</dd></div>
                    @endif
                </dl>

                @if ($detail->request_notes)
                    <div class="rounded-lg border border-[#dce1de] bg-[#f5f6f3] p-3">
                        <p class="text-[10px] uppercase font-bold text-[#71817f]">Catatan Pemohon</p>
                        <p class="mt-1 text-sm text-[#344950]">{{ $detail->request_notes }}</p>
                    </div>
                @endif

                @if ($detail->rejection_reason)
                    <div class="rounded-lg border border-[#ead6d4] bg-[#f7eceb] p-3">
                        <p class="text-[10px] uppercase font-bold text-[#9c5252]">Alasan Penolakan</p>
                        <p class="mt-1 text-sm text-[#9c5252]">{{ $detail->rejection_reason }}</p>
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-[#e6eae7] pt-4">
                    <button type="button" wire:click="$set('showDetail', false)" class="rounded-lg px-4 py-2 text-sm font-semibold text-[#52646a]">Tutup</button>
                    @if ($detail->status === 'pending')
                        @can('edit-purchase_approval')
                            <button type="button" wire:click="openReject({{ $detail->id }})" class="rounded-lg bg-[#7a3d3d] px-4 py-2 text-sm font-bold text-white">Tolak</button>
                            <button type="button" wire:click="approve({{ $detail->id }})" wire:confirm="Setujui permintaan ini?" class="rounded-lg bg-[#2f6b5c] px-4 py-2 text-sm font-bold text-white">Setujui</button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ── Modal Tolak ── --}}
    @if ($showRejectForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#17343d]/40 p-4">
            <form wire:submit="reject" class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-none">
                <h2 class="text-lg font-extrabold text-[#172c35]">Tolak Permintaan</h2>
                <label class="block text-sm font-semibold text-[#344950]">Alasan penolakan
                    <textarea wire:model="rejection_reason" rows="4" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm" placeholder="Contoh: stok masih cukup, anggaran belum tersedia"></textarea>
                    @error('rejection_reason')<span class="text-xs text-[#9c5252]">{{ $message }}</span>@enderror
                </label>
                <p class="text-xs text-[#64747a]">Alasan akan terlihat oleh pemohon pada modul Permintaan Pembelian.</p>
                <div class="flex justify-end gap-3 border-t border-[#e6eae7] pt-4">
                    <button type="button" wire:click="$set('showRejectForm', false)" class="rounded-lg px-4 py-2 text-sm font-semibold text-[#52646a]">Batal</button>
                    <button type="submit" class="rounded-lg bg-[#7a3d3d] px-4 py-2 text-sm font-bold text-white">Tolak PR</button>
                </div>
            </form>
        </div>
    @endif

</div>
