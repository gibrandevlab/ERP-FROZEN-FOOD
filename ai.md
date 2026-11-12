# AI Context: Riza Frozen Food ERP Database

Dokumen ini merangkum struktur database aplikasi ERP frozen food berdasarkan migration di `database/migrations/` pada workspace. Migration adalah sumber kebenaran; keberadaan file migration tidak berarti migration tersebut sudah dijalankan di setiap environment. Verifikasi juga model, validation, dan pemakaian kolom sebelum mengubah schema.

## Domain dan akses

Aplikasi memiliki 11 tabel inti ERP: `users`, `permissions`, `user_permissions`, `categories`, `products`, `ledgers`, `locations`, `stocks`, `customers`, `suppliers`, dan `purchase_requests`. Selain itu ada tabel untuk AI chat dan kuota token, detail transaksi ledger, serta audit log; tabel-tabel ini dijelaskan di bagian berikut.

Catatan histori: commit `12cffe8` menambahkan tabel AI chat/kuota token serta permission `forecast` dan `purchase_approval`. Migration 2026-11-02 menambahkan `ledger_items`, `audit_logs`, dan permission `audit_logs`; pastikan migration tersebut sudah masuk ke Git dan dijalankan pada environment target sebelum mengandalkan tabel-tabel itu.

Tidak ada tabel `roles`. Admin ditandai dengan `users.is_admin = true` dan mendapat akses penuh. User non-admin mendapat akses per modul dan aksi melalui `user_permissions`, yang menunjuk ke `permissions`.

Permission key yang dipakai aplikasi:

- `dashboard`
- `stok`
- `kategori`
- `lokasi`
- `pelanggan`
- `supplier`
- `spk`
- `purchase_request`
- `pembukuan`
- `ringkasan`
- `pengguna`
- `forecast`
- `purchase_approval`
- `audit_logs`

Jangan mengganti key permission tanpa menyelaraskan definisi permission di `AllTableSeeder`, assignment di `user_permissions`, Gate, dan pemanggil Gate di aplikasi. Nama tabel bisnis (`products`, `stocks`, dll.) tidak selalu sama dengan permission key.

## Tabel bisnis

Tipe kolom di bawah mengikuti schema database saat diperiksa. `BIGINT UNSIGNED` digunakan untuk ID/FK kecuali disebut lain. Kolom waktu `created_at` dan `updated_at` adalah timestamp.

### `users`

- `id` BIGINT UNSIGNED, primary key
- `name` VARCHAR(255)
- `email` VARCHAR(255), unique
- `password` VARCHAR(255)
- `recovery_phrase` VARCHAR(255), nullable, simpan dalam bentuk hash
- `is_admin` TINYINT(1), default false
- `remember_token` VARCHAR(100), nullable
- `created_at`, `updated_at`

### `permissions`

- `id` BIGINT UNSIGNED, primary key
- `key` VARCHAR(255), unique; dipakai untuk membentuk Gate
- `label` VARCHAR(255)
- `category` VARCHAR(255), default `umum`
- `description` TEXT, nullable
- `created_at`, `updated_at`

### `user_permissions`

- `id` BIGINT UNSIGNED, primary key
- `user_id` BIGINT UNSIGNED, FK ke `users.id`
- `permission_id` BIGINT UNSIGNED, FK ke `permissions.id`
- `can_view`, `can_create`, `can_edit`, `can_delete` TINYINT(1)
- `created_at`, `updated_at`
- Unique gabungan: (`user_id`, `permission_id`)

### `categories`

- `id` BIGINT UNSIGNED, primary key
- `name` VARCHAR(255)
- `slug` VARCHAR(255), unique
- `description` TEXT, nullable
- `user_id` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `created_at`, `updated_at`

### `products`

- `id` BIGINT UNSIGNED, primary key
- `category_id` BIGINT UNSIGNED, nullable, FK ke `categories.id`
- `name` VARCHAR(255)
- `slug` VARCHAR(255), unique
- `sku` VARCHAR(255), nullable, unique jika diisi
- `description` TEXT, nullable
- `price`, `cost` DECIMAL(15,2), default 0
- `wholesale_price` DECIMAL(15,2), nullable
- `wholesale_min_qty` INT, nullable
- `min_stock` INT, default 10
- `unit` VARCHAR(255), default `pcs`
- `image` VARCHAR(255), nullable
- `is_active` TINYINT(1), default true
- `lead_time` INT, default 0
- `user_id`, `updated_by` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `created_at`, `updated_at`, `deleted_at` (soft delete)

### `locations`

- `id` BIGINT UNSIGNED, primary key
- `name` VARCHAR(255)
- `description` TEXT, nullable
- `is_active` TINYINT(1), default true
- `user_id` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `created_at`, `updated_at`

### `stocks`

- `id` BIGINT UNSIGNED, primary key
- `product_id` BIGINT UNSIGNED, FK ke `products.id`
- `location_id` BIGINT UNSIGNED, FK ke `locations.id`
- `quantity` INT, default 0
- `user_id` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `created_at`, `updated_at`
- Unique gabungan: (`product_id`, `location_id`); satu baris stok per produk per lokasi

### `customers`

- `id` BIGINT UNSIGNED, primary key
- `name` VARCHAR(255)
- `phone` VARCHAR(255), nullable
- `type` ENUM(`seller`, `non_seller`), default `non_seller`
- `user_id` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `created_at`, `updated_at`
- Dalam data seed, `seller` berarti pembeli grosir/reseller; `non_seller` berarti pembeli retail.

### `suppliers`

- `id` BIGINT UNSIGNED, primary key
- `name` VARCHAR(255)
- `phone` VARCHAR(255), nullable
- `address`, `description` TEXT, nullable
- `is_active` TINYINT(1), default true
- `created_at`, `updated_at`, `deleted_at` (soft delete)

### `ledgers`

- `id` BIGINT UNSIGNED, primary key
- `type` ENUM(`income`, `expense`)
- `title` VARCHAR(255)
- `slug` VARCHAR(255), unique
- `amount` DECIMAL(15,2)
- `payment_status` ENUM(`paid`, `unpaid`), default `paid`
- `due_date` DATE, nullable
- `description` TEXT, nullable
- `date` DATE
- `reference` VARCHAR(255), nullable
- `product_id`, `supplier_id`, `location_id`, `customer_id` BIGINT UNSIGNED, nullable; FK masing-masing ke tabel dengan nama sama
- `quantity` INT, nullable
- `stock_movement` ENUM(`in`, `out`), nullable; bukan angka bertanda
- `proof_image` VARCHAR(255), nullable
- `user_id`, `updated_by` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `created_at`, `updated_at`, `deleted_at` (soft delete)

### `purchase_requests`

- `id` BIGINT UNSIGNED, primary key
- `ticket_number` VARCHAR(255), unique
- `product_id` BIGINT UNSIGNED, FK ke `products.id`
- `location_id` BIGINT UNSIGNED, FK ke `locations.id`
- `supplier_id` BIGINT UNSIGNED, nullable, FK ke `suppliers.id`
- `requested_qty` INT; `received_qty` INT, nullable
- `status` ENUM(`pending`, `approved`, `rejected`, `purchased`, `completed`), default `pending`
- `requested_by` BIGINT UNSIGNED, FK ke `users.id`
- `request_notes` TEXT, nullable
- `reviewed_by`, `purchased_by`, `received_by` BIGINT UNSIGNED, nullable, FK ke `users.id`
- `rejection_reason` TEXT, nullable
- `reviewed_at`, `purchased_at`, `received_at` TIMESTAMP, nullable
- `invoice_number`, `proof_image` VARCHAR(255), nullable
- `total_cost` DECIMAL(15,2), nullable
- `ledger_id` BIGINT UNSIGNED, nullable, FK ke `ledgers.id`
- `created_at`, `updated_at`, `deleted_at` (soft delete)
- `completed` berarti barang sudah diterima; PR yang belum selesai tidak memiliki ledger penerimaan.

## Tabel modul tambahan

### `ledger_items`

Menyimpan rincian produk untuk satu transaksi pembukuan. Migration 2026-11-02 menambahkan tabel ini.

- `id` BIGINT UNSIGNED, primary key
- `ledger_id` BIGINT UNSIGNED, FK ke `ledgers.id`, cascade saat ledger dihapus permanen
- `product_id`, `location_id` BIGINT UNSIGNED, nullable; FK ke `products.id` dan `locations.id`, menjadi null saat parent dihapus
- `product_name`, `location_name` VARCHAR(255), snapshot nama saat transaksi dicatat
- `quantity` INT
- `unit_price` DECIMAL(15,2)
- `created_at`, `updated_at`
- Index: (`location_id`, `product_id`)

Ledger lama tetap memiliki kolom `product_id`, `location_id`, `quantity`, dan `stock_movement`. Model `Ledger` dan `LedgerItem` sama-sama menangani mutasi stok; jangan mencatat item yang sama sekaligus pada kolom legacy ledger dan `ledger_items`, agar stok tidak termutasi dua kali. Penghapusan lunak ledger tidak menghapus `ledger_items`; penghapusan permanen ledger menjalankan cascade.

### `audit_logs`

Menyimpan riwayat perubahan; migration 2026-11-02 menambahkan tabel dan permission `audit_logs`.

- `id` BIGINT UNSIGNED, primary key
- `actor_id` BIGINT UNSIGNED, nullable, FK ke `users.id`, menjadi null jika user dihapus
- `actor_name` VARCHAR(255), snapshot nama pelaku
- `event` VARCHAR(20), misalnya `created`, `updated`, `deleted`, atau `restored`
- `subject_type` VARCHAR(255), `subject_id` VARCHAR(64), dan `subject_label` VARCHAR(255)
- `changes` JSON
- `created_at` TIMESTAMP; tabel ini tidak memiliki `updated_at`
- Index: (`subject_type`, `subject_id`, `id`), (`event`, `id`), dan (`actor_id`, `id`)

### `conversations` dan `chat_messages`

Ditambahkan pada migration Oktober 2026 untuk riwayat AI chat.

- `conversations.id` UUID primary key; `user_id` FK ke `users.id` (cascade delete); `title`; `created_at`, `updated_at`; index (`user_id`, `updated_at`)
- `chat_messages.id` UUID primary key; `conversation_id` UUID FK ke `conversations.id` (cascade delete); `role` ENUM(`system`, `user`, `assistant`, `tool`), default `user`; `content` LONGTEXT; `tool_call_id` VARCHAR(255), nullable; `tool_calls_json` LONGTEXT, nullable; `created_at`, `updated_at`; index (`conversation_id`, `created_at`)

### `token_usages`

Mencatat pemakaian token AI per percakapan dan, bila tersedia, per pesan.

- `id` BIGINT UNSIGNED, primary key
- `conversation_id` UUID, FK ke `conversations.id` (cascade delete)
- `message_id` UUID, nullable, FK ke `chat_messages.id` (menjadi null saat pesan dihapus)
- `prompt_tokens`, `completion_tokens`, `total_tokens`, `cache_hit_tokens`, `cache_miss_tokens` INT, default 0
- `model` VARCHAR(255), nullable, default `deepseek-chat`
- `created_at`, `updated_at`
- Index: (`conversation_id`, `created_at`) dan (`created_at`)

### `token_limits`

Mengatur kuota dan akses AI chat per user.

- `id` BIGINT UNSIGNED, primary key
- `user_id` BIGINT UNSIGNED, FK ke `users.id` (cascade delete), unique; maksimal satu baris per user
- `daily_token_limit`, `monthly_token_limit` INT UNSIGNED, default 0; `0` berarti tanpa batas
- `is_enabled` TINYINT(1), default true
- `notes` VARCHAR(255), nullable
- `created_at`, `updated_at`

## Form dan alur aplikasi

- Form produk mengelola `min_stock` bersama harga, harga grosir, satuan, lead time, status aktif, kategori, dan foto. Slug dibuat oleh model; `user_id` dan `updated_by` berasal dari user yang sedang login.
- Form kategori dan lokasi membuat `user_id` dari user yang sedang login. ID, slug, timestamp, dan kolom audit bukan input manual.
- Status supplier (`is_active`) dapat diubah dari form supplier; supplier nonaktif tidak muncul pada pilihan transaksi atau permintaan pembelian.
- Purchase Request dibuat sebagai `pending`; dapat berubah ke `approved` atau `rejected`, lalu `purchased`, dan berakhir `completed` saat diterima. Penolakan wajib memiliki alasan.
- Konfirmasi penerimaan Purchase Request membuat satu ledger pengeluaran berstatus lunas dan mutasi stok masuk dalam transaksi database yang sama. `ledger_id` menjadi penanda bahwa penerimaan sudah diproses; jangan membuat ledger penerimaan kedua untuk PR yang sama.
- Pembuatan/penyuntingan pengguna tersedia di panel admin. Password dan recovery phrase harus dikirim ke model agar cast `hashed` melakukan hashing; `is_admin` tidak diubah melalui form staf.
- Aksi modal cepat tetap harus memeriksa permission modul tujuan (`create-stok`, `create-lokasi`, `create-pelanggan`, atau `create-kategori`), tidak cukup hanya memiliki permission halaman pemanggil.

## Peta relasi

- `user_permissions` -> `users`, `permissions`
- `categories` -> `users`
- `products` -> `categories`, `users` (pembuat dan updater)
- `locations` -> `users`
- `stocks` -> `products`, `locations`, `users`
- `customers` -> `users`
- `ledgers` -> `products`, `suppliers`, `locations`, `customers`, `users` (pembuat dan updater)
- `ledger_items` -> `ledgers`, `products`, `locations`
- `purchase_requests` -> `products`, `locations`, `suppliers`, `ledgers`, `users` (requester, reviewer, purchaser, receiver)
- `conversations` -> `users`; `chat_messages` -> `conversations`
- `token_usages` -> `conversations`, `chat_messages`; `token_limits` -> `users`
- `audit_logs` -> `users` melalui `actor_id` (nullable)

## Tabel infrastruktur Laravel

Migration/framework Laravel juga mengelola tabel `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, dan `failed_jobs` (tergantung konfigurasi). Tabel ini bukan data bisnis dan biasanya tidak diisi oleh seeder ERP.

## Aturan untuk perubahan schema dan seeder

- Buat perubahan schema melalui migration baru; jangan mengedit migration yang sudah pernah dijalankan di lingkungan bersama.
- Sebelum menambah/mengubah kolom, periksa model, validation, Livewire/controller, query, dan seeder yang memakai kolom itu.
- Pertahankan tipe enum dan nilai yang persis sama antara migration, model, validation, UI, dan seeder.
- Insert data sesuai urutan FK: parent sebelum child. Saat menggeser ID, geser juga semua FK terkait dan jangan lupa constraint unik.
- `DB::table()` melewati model cast, event, dan observer. Hash password/recovery phrase secara eksplisit dan invalidasi cache permission jika memasukkan permission langsung.
- Seeder produksi/demo harus idempotent bila dijalankan ulang, membungkus beberapa insert terkait dalam transaksi, dan tidak menjalankan `migrate:fresh` secara otomatis.
- `migrate:fresh` menghapus seluruh tabel pada database target; gunakan hanya untuk database development/test yang memang boleh dikosongkan.
- Seeder `AllTableSeeder` menghasilkan data simulasi sekitar enam bulan dan memiliki guard email admin; baca implementasi terbaru sebelum mengasumsikan perilaku seed ulang.
