<?php

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $eventFilter = '';
    public string $typeFilter = '';

    private const SUBJECT_TYPES = [
        'produk' => \App\Models\Product::class,
        'kategori' => \App\Models\Category::class,
        'lokasi' => \App\Models\Location::class,
        'pelanggan' => \App\Models\Customer::class,
        'supplier' => \App\Models\Supplier::class,
        'transaksi' => \App\Models\Ledger::class,
        'detail-transaksi' => \App\Models\LedgerItem::class,
        'purchase-request' => \App\Models\PurchaseRequest::class,
        'pengguna' => \App\Models\User::class,
        'hak-akses' => \App\Models\UserPermission::class,
    ];

    public function mount(): void
    {
        $this->authorize('view-audit-logs');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function getLogsProperty()
    {
        return AuditLog::query()
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $searchQuery) => $searchQuery
                ->where('subject_label', 'like', '%'.$this->search.'%')
                ->orWhere('actor_name', 'like', '%'.$this->search.'%')
                ->orWhere('subject_id', 'like', '%'.$this->search.'%')))
            ->when($this->eventFilter, fn (Builder $query) => $query->where('event', $this->eventFilter))
            ->when(self::SUBJECT_TYPES[$this->typeFilter] ?? null, fn (Builder $query, string $type) => $query->where('subject_type', $type))
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function formatValue(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($field, ['is_active', 'is_admin', 'can_view', 'can_create', 'can_edit', 'can_delete'], true)) {
            return in_array($value, [true, 1, '1'], true) ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string) $value;
    }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">
    <div>
        <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb] text-[#315b50] rounded-lg">Audit Trail</span>
        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1">Riwayat Aktivitas</h1>
        <p class="text-xs sm:text-sm font-medium text-[#64747a] mt-1">Lihat siapa yang membuat, mengubah, menghapus, atau memulihkan data beserta perbandingan nilainya.</p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari data, ID, atau nama pengguna..."
               class="flex-1 px-4 py-2.5 bg-white border border-[#dce1de] rounded-lg text-sm text-[#344950]" />
        <select wire:model.live="typeFilter" class="px-3 py-2.5 bg-white border border-[#dce1de] rounded-lg text-sm text-[#344950]">
            <option value="">Semua jenis data</option>
            <option value="produk">Produk</option>
            <option value="kategori">Kategori</option>
            <option value="lokasi">Lokasi</option>
            <option value="pelanggan">Pelanggan</option>
            <option value="supplier">Supplier</option>
            <option value="transaksi">Transaksi</option>
            <option value="detail-transaksi">Detail transaksi</option>
            <option value="purchase-request">Purchase Request</option>
            <option value="pengguna">Pengguna</option>
            <option value="hak-akses">Hak akses</option>
        </select>
        <select wire:model.live="eventFilter" class="px-3 py-2.5 bg-white border border-[#dce1de] rounded-lg text-sm text-[#344950]">
            <option value="">Semua aktivitas</option>
            <option value="created">Dibuat</option>
            <option value="updated">Diedit</option>
            <option value="deleted">Dihapus</option>
            <option value="restored">Dipulihkan</option>
        </select>
    </div>

    @php($logs = $this->logs)
    <div class="space-y-2">
        @forelse($logs as $log)
            <details wire:key="audit-{{ $log->id }}" class="group bg-white rounded-lg border border-[#dce1de] overflow-hidden">
                <summary class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-4 py-3 bg-white hover:bg-[#f5f6f3]/70 cursor-pointer list-none">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-extrabold text-[#172c35]">{{ $log->actor_name }}</span>
                        <span class="text-[#64747a]">{{ strtolower($log->eventLabel()) }} {{ strtolower($log->subjectTypeLabel()) }}</span>
                        <span class="font-bold text-[#315b50]">{{ $log->subject_label }}</span>
                        <span class="text-xs text-[#71817f]">#{{ $log->subject_id }}</span>
                        <span class="text-[10px] text-[#71817f]">· {{ count($log->changes ?? []) }} perubahan</span>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-3">
                        <time class="text-xs text-[#71817f] shrink-0" datetime="{{ $log->created_at->toIso8601String() }}">
                            {{ $log->created_at->format('d M Y, H:i:s') }}
                        </time>
                        <span class="text-[#71817f] text-xs transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
                    </div>
                </summary>

                @if($log->changes)
                    <div class="border-t border-[#e6eae7] divide-y divide-[#eef0ed]">
                        @foreach($log->changes as $field => $change)
                            <div wire:key="audit-{{ $log->id }}-{{ $field }}" class="grid grid-cols-1 sm:grid-cols-[140px_1fr]">
                                <div class="px-4 py-1.5 text-xs font-bold text-[#52646a] bg-[#fafbf9]">{{ AuditLog::fieldLabel($field) }}</div>
                                <div class="min-w-0">
                                    @if($change['old'] !== null)
                                        <div class="px-4 py-1.5 text-xs font-mono text-[#9c5252] bg-[#fcf3f2] break-words">− {{ $this->formatValue($change['old'], $field) }}</div>
                                    @endif
                                    @if($change['new'] !== null)
                                        <div class="px-4 py-1.5 text-xs font-mono text-[#2f6b5c] bg-[#f0f7f3] break-words">+ {{ $this->formatValue($change['new'], $field) }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="border-t border-[#e6eae7] px-4 py-2.5 text-xs text-[#64747a]">Data {{ strtolower($log->eventLabel()) }}.</p>
                @endif
            </details>
        @empty
            <div class="bg-white rounded-lg border border-[#dce1de] px-5 py-12 text-center text-sm text-[#71817f]">
                Belum ada riwayat aktivitas untuk filter ini.
            </div>
        @endforelse
    </div>

    {{ $logs->links() }}
</div>
