# 📋 Cheatsheet Development — UMKM Frozen Food

> Catatan ini dirancang supaya kamu **nggak perlu scrolling chat lagi** pas mau kerja.

---

## 💻 Bagian 1: Daily Development (Di Laptop / WSL)

Gunakan perintah ini setiap kali kamu mau mulai ngoding di pagi hari.

### 1. Persiapan Awal

- Buka **Docker Desktop** — wajib nyala duluan.
- Buka **VS Code** — pastikan sudah di dalam WSL.

### 2. Perintah Rutin (Terminal VS Code)

| Aksi | Perintah |
|------|----------|
| Start Mesin | `sh vendor/bin/sail up -d` |
| Start Tampilan (Tailwind/Vite) | `sh vendor/bin/sail npm run dev` |
| Bikin / Update Database | `sh vendor/bin/sail artisan migrate` |
| Update Sitemap Manual | `sh vendor/bin/sail artisan sitemap:generate` |
| Matiin Mesin (Kalau Selesai) | `sh vendor/bin/sail stop` |

### 3. Workflow Git (Simpan ke GitHub)

```bash
git add .
git commit -m "pesan kodingan kamu hari ini"
git push -u origin main
```

---

## 🚀 Bagian 2: Persiapan Menuju Production (Shared Hosting)

Sebelum file kamu di-upload ke cPanel, kamu **WAJIB** melakukan "pembungkusan" aset agar web kamu kencang.

### 1. Build Aset Frontend (Di Laptop)

Jalankan ini agar Tailwind diproses menjadi file CSS kecil yang siap pakai:

```bash
sh vendor/bin/sail npm run build
```

> Hasilnya akan ada di folder `public/build`. Jangan lupa di-push juga ke GitHub.

### 2. Cek File `.env` (Produksi)

Pastikan saat di hosting, `.env` kamu berisi:

```env
APP_DEBUG=false
APP_ENV=production
APP_URL=https://umkm-frozenfood.com   # Ganti dengan domain asli

DB_HOST=127.0.0.1   # Biasanya hosting pakai IP lokal ini
```

---

## 🏠 Bagian 3: Maintenance di cPanel (Terminal / SSH)

Di shared hosting, **TIDAK ADA** Docker/Sail. Kamu hanya menggunakan perintah `php artisan` biasa.

### 1. Perintah Wajib Setelah Upload

Buka menu **Terminal** di cPanel kamu, lalu jalankan:

```bash
# Masuk ke folder web kamu dulu
cd public_html

# Install library (Jika upload via Git)
composer install --no-dev --optimize-autoloader

# Migrasi Database
php artisan migrate --force

# Bersihkan Cache agar Web Kencang
php artisan optimize
```

### 2. Setting Cron Job (Sitemap Otomatis)

Buka menu **Cron Jobs** di cPanel, masukkan perintah ini untuk jalan tiap menit:

```bash
/usr/local/bin/php /home/username_kamu/public_html/artisan schedule:run >> /dev/null 2>&1
```

> **Catatan:** `/usr/local/bin/php` bisa berbeda tiap hosting, tanya CS hosting kalau error.

---

## ⚠️ Hal Penting yang Sering Lupa

| # | Hal | Penjelasan |
|---|-----|------------|
| 1 | **Storage Link** | Di hosting, jalankan `php artisan storage:link` agar gambar produk muncul. |
| 2 | **Symlink Folder Public** | Jika folder utama adalah `public_html` tapi Laravel punya folder `public`, lakukan pointing yang benar di cPanel (pakai `.htaccess` atau pindah isi folder `public` ke `public_html`). |
| 3 | **XAMPP** | Lupakan XAMPP. Jangan buka XAMPP lagi kalau mau pakai Sail supaya port-nya nggak bentrok. |