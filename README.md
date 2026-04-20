# UMKM (Local Laravel)

Singkat: Aplikasi Laravel lokal yang menggunakan Laravel Sail (Docker). README ini menjelaskan cara cepat menyiapkan lingkungan, menjalankan front-end, dan penjadwalan sitemap.

## Prasyarat
- Docker Desktop (dengan WSL2 jika menggunakan Windows)
- VS Code (direkomendasikan) atau terminal yang sudah berada di WSL

## Quick start (dari root project)
1. Jalankan container (background):

```bash
sh vendor/bin/sail up -d
```

2. Install dependency PHP (jika belum):

```bash
sh vendor/bin/sail composer install
```

3. Jalankan migrasi (jika perlu):

```bash
sh vendor/bin/sail artisan migrate
```

4. Jalankan frontend (Vite) saat development:

```bash
sh vendor/bin/sail npm run dev
```

Catatan: Biarkan terminal yang menjalankan `npm run dev` terbuka; buka terminal baru untuk perintah artisan lain.

## Sitemap
- Sitemap dihasilkan oleh perintah artisan bawaan: `php artisan sitemap:generate`.
- Hasilnya disimpan di `public/sitemap.xml`.

Untuk menjalankan manual:

```bash
sh vendor/bin/sail artisan sitemap:generate
```

Catatan: Routes untuk `/sitemap.xml` dan `/robots.txt` telah dihapus dari `routes/web.php` karena sitemap sekarang dibuat secara statis di `public/`.

## Penjadwalan (Scheduler / Cron)
Kernel sudah menjadwalkan perintah `sitemap:generate` setiap hari pada pukul 00:00 (lihat `app/Console/Kernel.php`). Untuk membuat scheduler berjalan otomatis di server/VM, tambahkan baris ini ke crontab (`crontab -e`):

```cron
# Jika menggunakan Sail (Docker)
* * * * * cd /path/to/UMKM && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1

# Jika di hosting biasa (tanpa Docker)
* * * * * cd /path/to/UMKM && php artisan schedule:run >> /dev/null 2>&1

Baris di atas menjalankan scheduler Laravel setiap menit; Laravel akan mengeksekusi tugas yang dijadwalkan sesuai waktu yang ditentukan.

## Catatan penting
- Pastikan `.env` memiliki `DB_HOST=mysql` saat menggunakan Sail.
- Jika perlu, jalankan `sh vendor/bin/sail artisan migrate --seed` untuk menambahkan data contoh.

## Bantuan / Selanjutnya
Jika mau, saya bisa menambahkan instruksi ini ke file systemd service atau membantu menambahkan baris cron secara otomatis.

## Troubleshooting Vite (Tailwind tidak muncul)
Jika tampilan berantakan (CSS tidak load), pastikan:
1. File `vite.config.js` sudah memiliki setting `server: { hmr: { host: 'localhost' } }`.
2. Akses web melalui `http://localhost`, bukan `http://127.0.0.1:8000`.
3. Jalankan `sh vendor/bin/sail npm install` jika baru pertama kali pindah ke lingkungan WSL.
---
EOF