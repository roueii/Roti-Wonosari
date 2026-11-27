# KPI Roti Wonosari — Frontend

Aplikasi web pemantauan KPI harian karyawan Toko Roti Wonosari (Laravel 10, Blade).
Semua data (akun, KPI, isian capaian, approval) disimpan di **Backend** terpisah:
[Issnji/backend-roti-wonosari](https://github.com/Issnji/backend-roti-wonosari).
Frontend tidak bisa dipakai tanpa Backend yang menyala.

## Kebutuhan

- PHP 8.2 atau lebih baru (ekstensi `pdo_sqlite`, `fileinfo`, `mbstring`, `openssl`)
- Composer

## Cara menjalankan (lokal)

Jalankan **Backend dulu di port 8000**, baru Frontend di port 8001. Port harus ditulis eksplisit:
kalau keduanya dijalankan dengan `php artisan serve` tanpa `--port`, yang kedua otomatis pindah port
dan Frontend tidak menemukan Backend.

### 1. Backend (repo backend-roti-wonosari)

```bash
git clone https://github.com/Issnji/backend-roti-wonosari.git
cd backend-roti-wonosari
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
```

Lalu **ubah 4 baris ini di file `.env` Backend** (wajib):

```env
APP_URL=http://127.0.0.1:8000
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

> Tanpa langkah ini login selalu gagal: `.env.example` Backend memakai session/cache berbasis
> database, tetapi tabel `cache` tidak ada di migrasi (error `no such table: cache`).
> `APP_URL` dipakai untuk tautan foto bukti.

```bash
php artisan migrate --seed      # buat database SQLite + akun awal (jawab "yes" bila ditanya)
php artisan storage:link        # agar foto bukti bisa dibuka
php artisan serve --port=8000
```

### 2. Frontend (repo ini), di terminal lain

```bash
git clone https://github.com/roueii/Roti-Wonosari.git
cd Roti-Wonosari
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
php artisan serve --port=8001
```

Buka **http://127.0.0.1:8001/login**. Bila Backend berjalan di alamat lain, ubah `BACKEND_API_URL` di `.env` Frontend.

## Akun awal (kata sandi semua: `password`)

| Nama | Email | Peran |
|---|---|---|
| Admin System | admin@rotiwonosari.test | Admin (pemilik) |
| Budi (Store Manager) | sm@rotiwonosari.test | Store Manager |
| Siti (Supervisor) | spv@rotiwonosari.test | Karyawan — Supervisor |
| Dewi (Frontliner / Kasir) | frontliner@rotiwonosari.test | Karyawan — Frontliner |
| Joko (Tim Produksi) | produksi@rotiwonosari.test | Karyawan — Produksi |
| Andi (Driver) | driver@rotiwonosari.test | Karyawan — Driver |
| Rina (Sales & Marketing) | sales@rotiwonosari.test | Karyawan — Sales & Marketing |

Ganti kata sandi semua akun sebelum dipakai sungguhan (Admin: Profil → Ganti Kata Sandi;
akun lain: Admin → Kelola Akun → Reset Kata Sandi).

## Alur singkat

1. **Admin** mengatur hal yang dicatat tiap divisi di **Kelola KPI**.
2. **Karyawan** mengisi **Isi Capaian** (form mengikuti Kelola KPI) lalu mengirim.
3. **Store Manager** menyetujui/menolak di **Approval Tim**.
4. **Admin** memberi persetujuan akhir di **Approve Laporan**; hasilnya terlihat di **Rekap Bulanan**.
   Laporan Store Manager langsung diputuskan Admin.

## Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| Login: "Server sedang tidak dapat dihubungi" | Backend belum menyala, atau tidak di `http://127.0.0.1:8000`. Cek `BACKEND_API_URL`. |
| Login gagal / error 500, log Backend `no such table: cache` | Ubah `.env` Backend seperti langkah 1 lalu `php artisan config:clear`. |
| "Email tidak sesuai" untuk akun di atas | Akun belum dibuat: jalankan `php artisan migrate --seed` di Backend. |
| "Terlalu banyak percobaan" | Backend membatasi 10 percobaan login per menit. Tunggu 1 menit. |
| Foto bukti tidak bisa dibuka | `APP_URL` Backend belum benar atau `php artisan storage:link` belum dijalankan. |
