<div align="center">

# 🛍️ Seklov

### Marketplace Preloved Kampus Karawang

Jual beli barang bekas antar mahasiswa **UNSIKA** dan **UBP** dengan sistem **COD di titik temu kampus**.

<p>
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Vite-7-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite 7">
</p>

<p>
  <a href="#-instalasi">Instalasi</a> •
  <a href="#-akun-uji">Akun Uji</a> •
  <a href="#-fitur">Fitur</a>
</p>

</div>

---

## ✨ Fitur

<table>
  <tr>
    <td width="50%" valign="top">

**🛒 Untuk Pengguna**
- Katalog produk dengan foto dan video
- Pencarian dan filter (kategori, kondisi, harga, bisa nego)
- Chat penjual dan pembeli
- Nego harga (tawaran dan tawaran balik)
- Transaksi COD di titik temu kampus
- Rating, poin reward, dan badge
- Wishlist: minta barang yang dicari
- Notifikasi nego dan transaksi

    </td>
    <td width="50%" valign="top">

**🛡️ Untuk Admin**
- Dashboard admin terpisah (`/admin/login`)
- Kelola pengguna (aktif/nonaktif)
- Pantau produk dan transaksi

**🔐 Keamanan**
- Registrasi, login, dan lupa password dengan **OTP email**
- Batas percobaan OTP (maks. 5 kali)
- Akun admin dipisah dari akun pengguna

    </td>
  </tr>
</table>

---

## 🧰 Kebutuhan

| Perangkat lunak | Versi | Catatan |
|:---|:---:|:---|
| **PHP** | 8.2+ | Ekstensi: `mbstring` `xml` `curl` `zip` `bcmath` `intl` `pdo_mysql` **`gd`** |
| **Composer** | 2.x | |
| **MySQL / MariaDB** | 8.x / 10.x | |
| **Node.js + npm** | 20.19+ atau 22.12+ | Dibutuhkan Vite 7 |

> [!TIP]
> Pengguna **XAMPP**: aktifkan `extension=gd` di `C:\xampp\php\php.ini` (hapus tanda `;`), lalu restart Apache.
> GD dipakai seeder untuk membuat gambar produk demo.

---

## 🚀 Instalasi

### 1️⃣ Unduh project dan pasang dependensi

```bash
git clone https://github.com/USERNAME/REPO.git website-preloved
cd website-preloved

composer install
```

### 2️⃣ Siapkan konfigurasi

```bash
cp .env.example .env            # Windows PowerShell: copy .env.example .env
php artisan key:generate
```

Buat database kosong:

```sql
CREATE DATABASE preloved_karawang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu ubah bagian ini di `.env`:

```env
APP_NAME=Seklov
APP_URL=http://localhost:8000
APP_LOCALE=id

DB_DATABASE=preloved_karawang
DB_USERNAME=root
DB_PASSWORD=

# Akun admin utama (dibaca AdminSeeder). WAJIB diisi, kalau kosong akun ini tidak dibuat.
ADMIN_EMAIL=admin@seklov.test
ADMIN_PASSWORD=GantiDenganPasswordKuat123!
```

> [!NOTE]
> `MAIL_MAILER=log` (bawaan) berarti email tidak benar-benar terkirim. Kode OTP ditulis ke
> `storage/logs/laravel.log`, cukup untuk pengembangan.

### 3️⃣ Migrasi dan seeder

```bash
php artisan migrate --seed
php artisan storage:link
```

<details>
<summary><b>📦 Apa saja yang dibuat oleh <code>--seed</code>?</b></summary>

<br>

| Data | Jumlah | Keterangan |
|:---|:---:|:---|
| Akun admin | 1 | Dari `ADMIN_EMAIL` dan `ADMIN_PASSWORD` |
| Kategori | 13 | Fashion, Gadget, Elektronik, dan lainnya |
| Titik temu kampus | 10 | 5 UNSIKA dan 5 UBP |
| Akun uji | 3 | Admin, penjual, pembeli *(bukan production)* |
| Produk demo | 30 | Masing-masing 1 foto *(bukan production)* |
| Transaksi demo | 60 | 30 Penjualan Saya + 30 Pembelian Saya *(bukan production)* |
| Rating dan reward | ± 48 | Untuk transaksi yang selesai *(bukan production)* |

</details>

### 4️⃣ Build frontend dan jalankan

```bash
npm install
npm run build        # atau "npm run dev" saat mengembangkan
php artisan serve
```

Buka **http://localhost:8000** 🎉

---

## 🔑 Akun Uji

Semua akun demo memakai password **`Demo12345!`** dan sudah terverifikasi (tanpa OTP).

| Peran | Email | Login di | Keterangan |
|:---:|:---|:---|:---|
| 🛡️ **Admin** | `admin.demo@seklov.test` | `/admin/login` | Dashboard admin |
| 🧑‍💼 **Penjual** | `penjual.demo@seklov.test` | `/login` | UNSIKA. 15 produk, **30 Penjualan Saya**, **30 Pembelian Saya** |
| 🛒 **Pembeli** | `pembeli.demo@seklov.test` | `/login` | UBP. 15 produk, transaksi lawan dari akun penjual |

<details>
<summary><b>Akun tambahan</b></summary>

<br>

| Email | Password | Keterangan |
|:---|:---|:---|
| nilai `ADMIN_EMAIL` di `.env` | nilai `ADMIN_PASSWORD` di `.env` | Admin utama (`/admin/login`) |
| `penjual@seklov.test`, `pembeli@seklov.test` | `password` | Dari `NotificationSeeder`, berisi contoh notifikasi nego |

</details>

> [!IMPORTANT]
> - Di database hanya ada dua peran: `user` dan `admin`. Setiap `user` bisa menjadi penjual sekaligus pembeli.
> - Akun admin tidak bisa login lewat `/login`, dan akun user tidak bisa lewat `/admin/login`.
> - Mendaftar akun baru butuh OTP. Selama `MAIL_MAILER=log`, ambil kodenya dari `storage/logs/laravel.log`.
> - Semua data demo bersifat fiktif. Data asli mitra tidak dipakai selama pengembangan.

### 🔄 Mengulang data demo

```bash
php artisan db:seed --class=DemoSeeder
```

Aman dijalankan berulang: data lama milik 3 akun demo dihapus, lalu dibuat ulang.

---

<div align="center">

Dibuat untuk mahasiswa **UNSIKA** & **UBP** Karawang 🎓

<sub>Dibangun dengan Laravel 12 • Blade • Tailwind CSS 4 • Vite</sub>

</div>
