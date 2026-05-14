# 📋 Implementasi Plan: Setelah Migration & Model Selesai

> Dokumen ini adalah panduan langkah demi langkah untuk membangun semua halaman aplikasi
> UMKM SaaS menggunakan **Livewire Volt** (single-file components).
> Ikuti urutan tahap dari atas ke bawah.

---

## ✅ Prasyarat (Harus Sudah Selesai)

- [x] `php artisan migrate:fresh` — semua tabel sudah terbuat
- [x] `php artisan db:seed --class=PermissionSeeder` — 4 permission sudah ada
- [x] Model: User, Permission, UserPermission, Category, Product, Ledger
- [x] AppServiceProvider: Gate dinamis dari DB sudah aktif
- [x] Auth: Login, Register, Pulihkan Akun sudah berfungsi

---

## 📁 Target Struktur File (End State)

```
routes/
└── web.php                                    ← MODIFY

resources/views/
├── layouts/
│   └── app.blade.php                          ← NEW (layout utama)
│
└── livewire/
    ├── layout/
    │   └── navigation.blade.php               ← MODIFY (tambah menu modul)
    ├── dashboard/
    │   └── index.blade.php                    ← NEW
    ├── stok/
    │   ├── index.blade.php                    ← NEW
    │   ├── form.blade.php                     ← NEW
    │   └── detail.blade.php                   ← NEW
    ├── kategori/
    │   ├── index.blade.php                    ← NEW
    │   └── form.blade.php                     ← NEW
    ├── pembukuan/
    │   ├── index.blade.php                    ← NEW
    │   ├── form.blade.php                     ← NEW
    │   └── ringkasan.blade.php                ← NEW
    └── admin/
        ├── pengguna/
        │   ├── index.blade.php                ← NEW
        │   └── hak-akses.blade.php            ← NEW
        └── pengaturan.blade.php               ← NEW
```

---

## 🗓️ Urutan Implementasi

| Tahap | Modul | File |
|-------|-------|------|
| 1 | Routes | `routes/web.php` |
| 2 | Layout Utama | `layouts/app.blade.php` |
| 3 | Navigasi Sidebar | `livewire/layout/navigation.blade.php` |
| 4 | Dashboard | `livewire/dashboard/index.blade.php` |
| 5 | Kategori | `livewire/kategori/` |
| 6 | Stok / Produk | `livewire/stok/` |
| 7 | Pembukuan | `livewire/pembukuan/` |
| 8 | Admin Panel | `livewire/admin/` |

---

---

# TAHAP 1 — Routes

**File:** `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ─── Halaman Publik ───────────────────────────────────────────────────────────
Route::view('/', 'welcome');

// ─── Halaman Terproteksi (Harus Login) ───────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Volt::route('/dashboard', 'dashboard.index')
        ->name('dashboard');

    // ── Stok / Produk ──────────────────────────────────────────────────────
    Route::prefix('stok')->name('stok.')->group(function () {
        Volt::route('/', 'stok.index')->name('index');
        Volt::route('/tambah', 'stok.form')->name('tambah');
        Volt::route('/{slug}/edit', 'stok.form')->name('edit');
        Volt::route('/{slug}', 'stok.detail')->name('detail');
    });

    // ── Kategori ───────────────────────────────────────────────────────────
    Route::prefix('kategori')->name('kategori.')->group(function () {
        Volt::route('/', 'kategori.index')->name('index');
        Volt::route('/tambah', 'kategori.form')->name('tambah');
        Volt::route('/{slug}/edit', 'kategori.form')->name('edit');
    });

    // ── Pembukuan ──────────────────────────────────────────────────────────
    Route::prefix('pembukuan')->name('pembukuan.')->group(function () {
        // PENTING: /ringkasan harus sebelum /{slug} agar tidak dianggap slug
        Volt::route('/ringkasan', 'pembukuan.ringkasan')->name('ringkasan');
        Volt::route('/', 'pembukuan.index')->name('index');
        Volt::route('/tambah', 'pembukuan.form')->name('tambah');
        Volt::route('/{slug}/edit', 'pembukuan.form')->name('edit');
    });

    // ── Admin Panel (hanya is_admin = true, dicek di mount()) ─────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Volt::route('/pengguna', 'admin.pengguna.index')->name('pengguna.index');
        Volt::route('/pengguna/{id}/hak-akses', 'admin.pengguna.hak-akses')->name('pengguna.hak-akses');
        Volt::route('/pengaturan', 'admin.pengaturan')->name('pengaturan');
    });

    // Profile
    Route::view('profile', 'profile')->name('profile');
});

require __DIR__ . '/auth.php';
```

---

---

# TAHAP 2 — Layout Utama

**File:** `resources/views/layouts/app.blade.php`

Layout ini membungkus semua halaman yang membutuhkan login.
Terdiri dari sidebar kiri + area konten kanan.

```blade
<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ config('app.name', 'UMKM SaaS') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-[Inter] antialiased">

<div class="flex h-full">

    {{-- ─── Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="w-64 flex-shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col">
        {{-- Logo --}}
        <div class="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-800">
            <span class="text-xl font-bold text-gray-900 dark:text-white">🧊 UMKM</span>
        </div>

        {{-- Navigasi --}}
        <nav class="flex-1 overflow-y-auto py-4">
            @livewire('layout.navigation')
        </nav>

        {{-- User & Logout --}}
        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                {{ auth()->user()->name }}
            </div>
            <div class="text-xs text-gray-400 truncate mb-3">{{ auth()->user()->email }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left text-xs text-red-500 hover:text-red-700 transition">
                    Keluar →
                </button>
            </form>
        </div>
    </aside>

    {{-- ─── Area Konten ──────────────────────────────────────────────── --}}
    <main class="flex-1 overflow-y-auto">

        {{-- Topbar --}}
        <div class="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center px-8">
            <h1 class="text-base font-semibold text-gray-800 dark:text-gray-200">
                {{ $header ?? 'Dashboard' }}
            </h1>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mx-8 mt-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mx-8 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                ❌ {{ session('error') }}
            </div>
        @endif

        {{-- Konten Halaman --}}
        <div class="p-8">
            {{ $slot }}
        </div>

    </main>

</div>

@livewireScripts
</body>
</html>
```

---

---

# TAHAP 3 — Navigasi Sidebar

**File:** `resources/views/livewire/layout/navigation.blade.php`

```php
<?php

use Livewire\Volt\Component;

new class extends Component {

    // Cek apakah URL aktif sesuai prefix tertentu
    public function isActive(string $routePrefix): bool
    {
        return request()->routeIs($routePrefix . '*');
    }
}; ?>

<ul class="space-y-1 px-3">

    {{-- Dashboard --}}
    <li>
        <a href="{{ route('dashboard') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                  {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            🏠 Dashboard
        </a>
    </li>

    {{-- Divider --}}
    <li class="pt-4 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Bisnis</li>

    {{-- Stok --}}
    @can('view-products')
    <li>
        <a href="{{ route('stok.index') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                  {{ request()->routeIs('stok.*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            📦 Stok Produk
        </a>
    </li>
    @endcan

    {{-- Kategori --}}
    @can('view-categories')
    <li>
        <a href="{{ route('kategori.index') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                  {{ request()->routeIs('kategori.*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            🏷️ Kategori
        </a>
    </li>
    @endcan

    {{-- Divider --}}
    <li class="pt-4 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Keuangan</li>

    {{-- Pembukuan --}}
    @can('view-ledger')
    <li>
        <a href="{{ route('pembukuan.index') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                  {{ request()->routeIs('pembukuan.*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            📒 Pembukuan
        </a>
    </li>

    <li>
        <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 ml-4 rounded-lg text-sm transition
                  {{ request()->routeIs('pembukuan.ringkasan') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            📊 Ringkasan
        </a>
    </li>
    @endcan

    {{-- Admin Panel (hanya admin) --}}
    @if(auth()->user()->is_admin)
    <li class="pt-4 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</li>

    <li>
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                  {{ request()->routeIs('admin.*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            👥 Manajemen Pengguna
        </a>
    </li>
    @endif

</ul>
```

---

---

# TAHAP 4 — Dashboard

**File:** `resources/views/livewire/dashboard/index.blade.php`

```php
<?php

use App\Models\{Product, Ledger};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public int    $totalProduk     = 0;
    public int    $stokMenipis     = 0; // stok < 10
    public string $totalPemasukan  = '0';
    public string $totalPengeluaran = '0';
    public string $labaKotor       = '0';

    public function mount(): void
    {
        $bulanIni = now()->format('Y-m');

        $this->totalProduk      = Product::where('is_active', true)->count();
        $this->stokMenipis      = Product::where('is_active', true)->where('stock', '<', 10)->count();

        $pemasukan              = Ledger::income()->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanIni])->sum('amount');
        $pengeluaran            = Ledger::expense()->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanIni])->sum('amount');

        $this->totalPemasukan   = number_format($pemasukan, 0, ',', '.');
        $this->totalPengeluaran = number_format($pengeluaran, 0, ',', '.');
        $this->labaKotor        = number_format($pemasukan - $pengeluaran, 0, ',', '.');
    }
}; ?>

<div>
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Produk Aktif</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalProduk }}</p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border {{ $stokMenipis > 0 ? 'border-amber-400' : 'border-gray-200 dark:border-gray-800' }} p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Stok Menipis (&lt; 10)</p>
            <p class="text-3xl font-bold {{ $stokMenipis > 0 ? 'text-amber-500' : 'text-gray-900 dark:text-white' }}">{{ $stokMenipis }}</p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Pemasukan Bulan Ini</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ $totalPemasukan }}</p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Laba Kotor Bulan Ini</p>
            <p class="text-2xl font-bold {{ str_contains($labaKotor, '-') ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                Rp {{ $labaKotor }}
            </p>
        </div>

    </div>

    {{-- Shortcut ke modul --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @can('create-products')
        <a href="{{ route('stok.tambah') }}" wire:navigate
           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 text-center hover:border-gray-400 transition group">
            <div class="text-2xl mb-2">📦</div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Tambah Produk</p>
        </a>
        @endcan

        @can('create-ledger')
        <a href="{{ route('pembukuan.tambah') }}" wire:navigate
           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 text-center hover:border-gray-400 transition group">
            <div class="text-2xl mb-2">📒</div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Catat Transaksi</p>
        </a>
        @endcan

        @can('view-ledger')
        <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate
           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 text-center hover:border-gray-400 transition group">
            <div class="text-2xl mb-2">📊</div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Lihat Ringkasan</p>
        </a>
        @endcan

        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate
           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 text-center hover:border-gray-400 transition group">
            <div class="text-2xl mb-2">👥</div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Kelola Pengguna</p>
        </a>
        @endif
    </div>
</div>
```

---

---

# TAHAP 5 — Kategori

### 5a. Daftar Kategori
**File:** `resources/views/livewire/kategori/index.blade.php`

```php
<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('view-categories');
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
        $this->authorize('delete-categories');

        $kategori = Category::withCount('products')->findOrFail($id);

        if ($kategori->products_count > 0) {
            session()->flash('error', "Kategori '{$kategori->name}' masih memiliki {$kategori->products_count} produk. Pindahkan produknya dulu.");
            return;
        }

        $kategori->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Kategori Produk</h2>
            <p class="text-sm text-gray-500">{{ $this->kategori->count() }} kategori terdaftar</p>
        </div>
        @can('create-categories')
        <a href="{{ route('kategori.tambah') }}" wire:navigate
           class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
            + Tambah Kategori
        </a>
        @endcan
    </div>

    <input wire:model.live="search" type="text" placeholder="Cari kategori..."
           class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm mb-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Produk</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->kategori as $k)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $k->name }}</td>
                    <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $k->slug }}</td>
                    <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">{{ $k->products_count }}</td>
                    <td class="px-6 py-4 text-right space-x-2">
                        @can('edit-categories')
                        <a href="{{ route('kategori.edit', $k->slug) }}" wire:navigate
                           class="text-xs text-blue-600 hover:underline">Edit</a>
                        @endcan
                        @can('delete-categories')
                        <button wire:click="hapus({{ $k->id }})"
                                wire:confirm="Hapus kategori '{{ $k->name }}'?"
                                class="text-xs text-red-500 hover:underline">Hapus</button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-10 text-center text-gray-400">Belum ada kategori.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

### 5b. Form Tambah & Edit Kategori
**File:** `resources/views/livewire/kategori/form.blade.php`

```php
<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?Category $kategori = null;
    public string $name        = '';
    public string $description = '';
    public bool   $modeEdit    = false;

    public function mount(?string $slug = null): void
    {
        if ($slug) {
            $this->authorize('edit-categories');
            $this->modeEdit  = true;
            $this->kategori  = Category::where('slug', $slug)->firstOrFail();
            $this->name        = $this->kategori->name;
            $this->description = $this->kategori->description ?? '';
        } else {
            $this->authorize('create-categories');
        }
    }

    public function simpan(): void
    {
        $rules = [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ];

        $validated = $this->validate($rules);

        if ($this->modeEdit) {
            $this->kategori->update($validated);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
            Category::create($validated);
            session()->flash('success', 'Kategori berhasil ditambahkan.');
        }

        $this->redirectRoute('kategori.index', navigate: true);
    }
}; ?>

<div class="max-w-xl">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        {{ $modeEdit ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
    </h2>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <form wire:submit="simpan" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Kategori</label>
                <input wire:model="name" type="text" placeholder="Contoh: Makanan Beku"
                       class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi (opsional)</label>
                <textarea wire:model="description" rows="3" placeholder="Penjelasan singkat kategori ini..."
                          class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"></textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('kategori.index') }}" wire:navigate
                   class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">← Kembali</a>
                <button type="submit"
                        class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                </button>
            </div>
        </form>
    </div>
</div>
```

---

---

# TAHAP 6 — Stok / Produk

### 6a. Daftar Produk
**File:** `resources/views/livewire/stok/index.blade.php`

```php
<?php

use App\Models\{Product, Category};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string  $search      = '';
    public string  $filterKat   = '';   // filter kategori
    public string  $filterAktif = '';   // '' | '1' | '0'

    public function mount(): void
    {
        $this->authorize('view-products');
    }

    public function getKategoriListProperty()
    {
        return Category::orderBy('name')->get();
    }

    public function getProductsProperty()
    {
        return Product::with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                                               ->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->filterKat, fn($q) => $q->where('category_id', $this->filterKat))
            ->when($this->filterAktif !== '', fn($q) => $q->where('is_active', (bool) $this->filterAktif))
            ->latest()
            ->paginate(15);
    }

    public function hapus(int $id): void
    {
        $this->authorize('delete-products');
        Product::findOrFail($id)->delete(); // soft delete
        session()->flash('success', 'Produk berhasil dihapus.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterKat(): void { $this->resetPage(); }
    public function updatedFilterAktif(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Stok Produk</h2>
        </div>
        @can('create-products')
        <a href="{{ route('stok.tambah') }}" wire:navigate
           class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
            + Tambah Produk
        </a>
        @endcan
    </div>

    {{-- Filter Bar --}}
    <div class="flex gap-3 mb-4 flex-wrap">
        <input wire:model.live="search" type="text" placeholder="Cari nama / SKU..."
               class="flex-1 min-w-48 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />

        <select wire:model.live="filterKat"
                class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">Semua Kategori</option>
            @foreach($this->kategoriList as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterAktif"
                class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stok</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Harga Jual</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->products as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $p->name }}</p>
                        @if($p->sku) <p class="text-xs text-gray-400 font-mono">{{ $p->sku }}</p> @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $p->category?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="{{ $p->stock < 10 ? 'text-amber-600 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $p->stock }} {{ $p->unit }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">
                        Rp {{ number_format($p->price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $p->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate
                           class="text-xs text-gray-500 hover:underline">Detail</a>
                        @can('edit-products')
                        <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate
                           class="text-xs text-blue-600 hover:underline">Edit</a>
                        @endcan
                        @can('delete-products')
                        <button wire:click="hapus({{ $p->id }})"
                                wire:confirm="Hapus produk '{{ $p->name }}'?"
                                class="text-xs text-red-500 hover:underline">Hapus</button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">Belum ada produk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->products->links() }}
    </div>
</div>
```

### 6b. Form Tambah & Edit Produk
**File:** `resources/views/livewire/stok/form.blade.php`

```php
<?php

use App\Models\{Product, Category};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public ?Product $product    = null;
    public bool     $modeEdit   = false;

    // Form fields
    public string  $name        = '';
    public string  $sku         = '';
    public ?int    $category_id = null;
    public string  $description = '';
    public string  $price       = '0';
    public string  $cost        = '0';
    public int     $stock       = 0;
    public string  $unit        = 'pcs';
    public bool    $is_active   = true;
    public $image  = null; // file upload

    public function mount(?string $slug = null): void
    {
        if ($slug) {
            $this->authorize('edit-products');
            $this->modeEdit = true;
            $this->product  = Product::where('slug', $slug)->firstOrFail();
            $this->fill($this->product->only([
                'name','sku','category_id','description','stock','unit','is_active'
            ]));
            $this->price = (string) $this->product->price;
            $this->cost  = (string) $this->product->cost;
        } else {
            $this->authorize('create-products');
        }
    }

    public function getKategoriListProperty()
    {
        return Category::orderBy('name')->get();
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:50',
                             $this->modeEdit
                                 ? \Illuminate\Validation\Rule::unique('products', 'sku')->ignore($this->product->id)
                                 : \Illuminate\Validation\Rule::unique('products', 'sku')
                             ],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'cost'        => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'unit'        => ['required', 'string', 'max:20'],
            'is_active'   => ['boolean'],
            'image'       => ['nullable', 'image', 'max:2048'],
        ]);

        // Upload gambar jika ada
        if ($this->image) {
            $validated['image'] = $this->image->store('products', 'public');
        }

        if ($this->modeEdit) {
            $this->product->update($validated);
            session()->flash('success', 'Produk berhasil diperbarui.');
        } else {
            Product::create($validated);
            session()->flash('success', 'Produk berhasil ditambahkan.');
        }

        $this->redirectRoute('stok.index', navigate: true);
    }
}; ?>

<div class="max-w-2xl">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
        {{ $modeEdit ? 'Edit Produk' : 'Tambah Produk Baru' }}
    </h2>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <form wire:submit="simpan" class="space-y-5">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Produk *</label>
                    <input wire:model="name" type="text" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="Nugget Ayam 500gr" />
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU (opsional)</label>
                    <input wire:model="sku" type="text" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="PRD-001" />
                    @error('sku') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                    <select wire:model="category_id" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">— Tanpa Kategori —</option>
                        @foreach($this->kategoriList as $k)
                            <option value="{{ $k->id }}">{{ $k->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Jual (Rp) *</label>
                    <input wire:model="price" type="number" min="0" step="100" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                    @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Modal (Rp) *</label>
                    <input wire:model="cost" type="number" min="0" step="100" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                    @error('cost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stok *</label>
                    <input wire:model="stock" type="number" min="0" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                    @error('stock') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Satuan</label>
                    <select wire:model="unit" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="pcs">pcs</option>
                        <option value="kg">kg</option>
                        <option value="gram">gram</option>
                        <option value="liter">liter</option>
                        <option value="pack">pack</option>
                        <option value="lusin">lusin</option>
                        <option value="karton">karton</option>
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                    <textarea wire:model="description" rows="3" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"></textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto Produk</label>
                    <input wire:model="image" type="file" accept="image/*" class="text-sm text-gray-600 dark:text-gray-400" />
                    @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex items-center gap-2">
                    <input wire:model="is_active" id="is_active" type="checkbox" class="rounded border-gray-300 dark:border-gray-600" />
                    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Produk aktif (tampil di daftar)</label>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('stok.index') }}" wire:navigate
                   class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">← Kembali</a>
                <button type="submit"
                        class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
                    {{ $modeEdit ? 'Simpan Perubahan' : 'Tambah Produk' }}
                </button>
            </div>

        </form>
    </div>
</div>
```

---

---

# TAHAP 7 — Pembukuan

### 7a. Daftar Transaksi
**File:** `resources/views/livewire/pembukuan/index.blade.php`

```php
<?php

use App\Models\Ledger;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search    = '';
    public string $filterType = '';  // '' | 'income' | 'expense'
    public string $filterBulan = ''; // format: Y-m

    public function mount(): void
    {
        $this->authorize('view-ledger');
        $this->filterBulan = now()->format('Y-m');
    }

    public function getLedgersProperty()
    {
        return Ledger::with('product')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->orderByDesc('date')
            ->paginate(20);
    }

    public function hapus(int $id): void
    {
        $this->authorize('delete-ledger');
        Ledger::findOrFail($id)->delete();
        session()->flash('success', 'Transaksi berhasil dihapus.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pembukuan</h2>
        <div class="flex gap-2">
            <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate
               class="px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                📊 Ringkasan
            </a>
            @can('create-ledger')
            <a href="{{ route('pembukuan.tambah') }}" wire:navigate
               class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
                + Catat Transaksi
            </a>
            @endcan
        </div>
    </div>

    <div class="flex gap-3 mb-4 flex-wrap">
        <input wire:model.live="search" type="text" placeholder="Cari transaksi..."
               class="flex-1 min-w-48 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
        <select wire:model.live="filterType"
                class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">Semua Tipe</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
        </select>
        <input wire:model.live="filterBulan" type="month"
               class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Tipe</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Nominal</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->ledgers as $l)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $l->title }}</p>
                        @if($l->reference) <p class="text-xs text-gray-400">Ref: {{ $l->reference }}</p> @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $l->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $l->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right font-medium {{ $l->type === 'income' ? 'text-green-600' : 'text-red-500' }}">
                        {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        @can('edit-ledger')
                        <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate
                           class="text-xs text-blue-600 hover:underline">Edit</a>
                        @endcan
                        @can('delete-ledger')
                        <button wire:click="hapus({{ $l->id }})"
                                wire:confirm="Hapus catatan '{{ $l->title }}'?"
                                class="text-xs text-red-500 hover:underline">Hapus</button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">Belum ada transaksi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $this->ledgers->links() }}</div>
</div>
```

---

---

# TAHAP 8 — Admin Panel

### 8a. Daftar Pengguna
**File:** `resources/views/livewire/admin/pengguna/index.blade.php`

```php
<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public bool   $is_admin = false;
    public bool   $showForm = false;

    public function mount(): void
    {
        // Hanya admin yang bisa masuk
        abort_unless(auth()->user()->is_admin, 403, 'Halaman ini khusus admin.');
    }

    public function getPenggunaProperty()
    {
        return User::latest()->get();
    }

    public function simpanUser(): void
    {
        $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['boolean'],
        ]);

        User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => $this->password,
            'is_admin' => $this->is_admin,
        ]);

        $this->reset(['name', 'email', 'password', 'is_admin', 'showForm']);
        session()->flash('success', 'Pengguna berhasil ditambahkan.');
    }

    public function hapusUser(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'Tidak bisa menghapus akun sendiri.');
            return;
        }
        User::findOrFail($id)->delete();
        session()->flash('success', 'Pengguna berhasil dihapus.');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Manajemen Pengguna</h2>
        <button wire:click="$toggle('showForm')"
                class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
            {{ $showForm ? '✕ Tutup' : '+ Tambah Pengguna' }}
        </button>
    </div>

    {{-- Form Tambah User --}}
    @if($showForm)
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 mb-6">
        <form wire:submit="simpanUser" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama</label>
                <input wire:model="name" type="text" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input wire:model="email" type="email" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input wire:model="password" type="password" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white" />
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end pb-1">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input wire:model="is_admin" type="checkbox" class="rounded" />
                    Jadikan Admin
                </label>
            </div>
            <div class="col-span-2 text-right">
                <button type="submit" class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
                    Tambah Pengguna
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Tabel Pengguna --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($this->pengguna as $u)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $u->name }}</p>
                        <p class="text-xs text-gray-400">{{ $u->email }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $u->is_admin ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $u->is_admin ? 'Admin' : 'Staf' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        @unless($u->is_admin)
                        <a href="{{ route('admin.pengguna.hak-akses', $u->id) }}" wire:navigate
                           class="text-xs text-blue-600 hover:underline">Atur Hak Akses</a>
                        @endunless
                        @if($u->id !== auth()->id())
                        <button wire:click="hapusUser({{ $u->id }})"
                                wire:confirm="Hapus pengguna '{{ $u->name }}'? Semua hak aksesnya juga terhapus."
                                class="text-xs text-red-500 hover:underline">Hapus</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
```

### 8b. Matriks Hak Akses
**File:** `resources/views/livewire/admin/pengguna/hak-akses.blade.php`

```php
<?php

use App\Models\{User, Permission, UserPermission};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public User  $user;
    public array $matrix = [];
    // Struktur: ['products' => ['label'=>'...','view'=>true,'create'=>false,'edit'=>false,'delete'=>false]]

    public function mount(int $id): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->user      = User::findOrFail($id);
        $permissions     = Permission::all();
        $userPerms       = UserPermission::with('permission')
                             ->where('user_id', $this->user->id)
                             ->get();

        foreach ($permissions as $perm) {
            $found = $userPerms->firstWhere('permission_id', $perm->id);
            $this->matrix[$perm->key] = [
                'label'    => $perm->label,
                'category' => $perm->category,
                'view'     => (bool) ($found?->can_view   ?? false),
                'create'   => (bool) ($found?->can_create ?? false),
                'edit'     => (bool) ($found?->can_edit   ?? false),
                'delete'   => (bool) ($found?->can_delete ?? false),
            ];
        }
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        foreach ($this->matrix as $key => $akses) {
            $perm = Permission::where('key', $key)->first();
            if (! $perm) continue;

            UserPermission::updateOrCreate(
                [
                    'user_id'       => $this->user->id,
                    'permission_id' => $perm->id,
                ],
                [
                    'can_view'   => $akses['view'],
                    'can_create' => $akses['create'],
                    'can_edit'   => $akses['edit'],
                    'can_delete' => $akses['delete'],
                ]
            );
        }

        session()->flash('success', "Hak akses {$this->user->name} berhasil disimpan.");
    }
}; ?>

<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Hak Akses</h2>
            <p class="text-sm text-gray-500">{{ $user->name }} — {{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate
           class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">← Kembali</a>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fitur</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Lihat</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Tambah</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Edit</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Hapus</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($matrix as $key => $akses)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $akses['label'] }}</p>
                        <p class="text-xs text-gray-400 capitalize">{{ $akses['category'] }}</p>
                    </td>
                    @foreach(['view','create','edit','delete'] as $action)
                    <td class="px-4 py-4 text-center">
                        <input wire:model="matrix.{{ $key }}.{{ $action }}"
                               type="checkbox"
                               class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-0" />
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 text-right">
            <button wire:click="simpan"
                    class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-80 transition">
                Simpan Hak Akses
            </button>
        </div>
    </div>
</div>
```

---

---

## 📋 Checklist Implementasi

- [ ] **Tahap 1** — Update `routes/web.php`
- [ ] **Tahap 2** — Buat `layouts/app.blade.php`
- [ ] **Tahap 3** — Update `livewire/layout/navigation.blade.php`
- [ ] **Tahap 4** — Buat `livewire/dashboard/index.blade.php`
- [ ] **Tahap 5a** — Buat `livewire/kategori/index.blade.php`
- [ ] **Tahap 5b** — Buat `livewire/kategori/form.blade.php`
- [ ] **Tahap 6a** — Buat `livewire/stok/index.blade.php`
- [ ] **Tahap 6b** — Buat `livewire/stok/form.blade.php`
- [ ] **Tahap 6c** — Buat `livewire/stok/detail.blade.php`
- [ ] **Tahap 7a** — Buat `livewire/pembukuan/index.blade.php`
- [ ] **Tahap 7b** — Buat `livewire/pembukuan/form.blade.php`
- [ ] **Tahap 7c** — Buat `livewire/pembukuan/ringkasan.blade.php`
- [ ] **Tahap 8a** — Buat `livewire/admin/pengguna/index.blade.php`
- [ ] **Tahap 8b** — Buat `livewire/admin/pengguna/hak-akses.blade.php`
