<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Data contoh (demo) untuk Seklov.
 *
 * Isi:
 *  - 3 akun: admin, penjual, pembeli (semua dengan password PASSWORD di bawah)
 *  - 30 produk beserta 1 foto per produk (dibuat dengan PHP GD, tanpa Faker/internet)
 *  - 30 transaksi "Penjualan Saya" + 30 transaksi "Pembelian Saya" untuk akun penjual demo
 *  - rating dan poin reward untuk transaksi yang selesai
 *
 * Jalankan (jangan lewat db:seed tanpa --class):
 *   php artisan db:seed --class=DemoSeeder --force
 *
 * Aman dijalankan berulang: data demo lama (hanya milik 3 akun demo) dihapus dulu, lalu dibuat ulang.
 * Tidak memakai Faker, jadi jalan di server dengan `composer install --no-dev`.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Demo12345!';

    private const EMAIL_ADMIN = 'admin.demo@seklov.test';
    private const EMAIL_PENJUAL = 'penjual.demo@seklov.test';
    private const EMAIL_PEMBELI = 'pembeli.demo@seklov.test';

    /** Jumlah transaksi per tab (Penjualan Saya dan Pembelian Saya). */
    private const JUMLAH_TRANSAKSI = 30;

    /** Pola status transaksi (diulang): 50% selesai, 20% menunggu, 20% dijadwalkan, 10% dibatalkan. */
    private const POLA_STATUS = [
        'selesai', 'selesai', 'menunggu', 'dijadwalkan', 'selesai',
        'dibatalkan', 'selesai', 'dijadwalkan', 'menunggu', 'selesai',
    ];

    private const CATATAN_PEMBELI = [
        'Bisa COD jam 1 siang ya.',
        'Mohon dicek bareng saat ketemu di kampus.',
        'Saya tunggu di titik temu ya, kabari kalau sudah sampai.',
        'Kalau bisa hari Jumat sore setelah kuliah.',
        null, null, null,
    ];

    private const ULASAN = [
        5 => ['Barang sesuai deskripsi, penjual ramah dan tepat waktu.', 'Mantap, COD lancar dan barang bagus!', 'Recommended seller, pasti beli lagi.'],
        4 => ['Barang bagus, cuma sedikit telat datang ke titik temu.', 'Sesuai foto, transaksi lancar.'],
        3 => ['Barang oke, tapi ada sedikit kekurangan yang tidak disebutkan.', 'Lumayan, respon agak lama.'],
    ];

    /** Warna latar gambar produk (R, G, B). */
    private const PALET = [
        [231, 76, 60], [41, 128, 185], [39, 174, 96],
        [142, 68, 173], [211, 130, 20], [22, 160, 133],
    ];

    public function run(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Ekstensi PHP GD belum terpasang. Jalankan: sudo apt install php8.3-gd lalu sudo systemctl restart php8.3-fpm');
        }

        // Data referensi (kategori dan titik temu). Keduanya memakai firstOrCreate, jadi aman dipanggil ulang.
        $this->call([
            CategorySeeder::class,
            CampusMeetingPointSeeder::class,
        ]);

        // Hasil acak selalu sama setiap dijalankan.
        mt_srand(20261127);

        $admin = $this->buatAkun(self::EMAIL_ADMIN, 'Admin Demo', 'admin', null, null, null);
        $penjual = $this->buatAkun(self::EMAIL_PENJUAL, 'Penjual Demo', 'user', 'UNSIKA', '2023', '2310631170001');
        $pembeli = $this->buatAkun(self::EMAIL_PEMBELI, 'Pembeli Demo', 'user', 'UBP', '2024', null);

        $idDemo = [(int) $admin->user_id, (int) $penjual->user_id, (int) $pembeli->user_id];

        DB::transaction(function () use ($penjual, $pembeli, $idDemo) {
            $this->bersihkanDataLama($idDemo);

            $produk = $this->buatProduk($penjual, $pembeli);

            // Penjualan Saya: penjual demo menjual ke pembeli demo.
            $this->buatTransaksi($pembeli, $penjual, $produk[$penjual->user_id], 0);
            // Pembelian Saya: penjual demo membeli dari pembeli demo.
            $this->buatTransaksi($penjual, $pembeli, $produk[$pembeli->user_id], 3);
        });

        $this->ringkasan($penjual->user_id, $pembeli->user_id);
    }

    // ------------------------------------------------------------------
    // Akun
    // ------------------------------------------------------------------

    private function buatAkun(string $email, string $nama, string $role, ?string $kampus, ?string $angkatan, ?string $npm): User
    {
        $user = User::firstOrNew(['email' => $email]);

        // forceFill karena role dan is_verified sengaja tidak masuk $fillable.
        // password di-hash otomatis oleh cast 'hashed' pada model User.
        $user->forceFill([
            'name' => $nama,
            'npm' => $npm,
            'password' => self::PASSWORD,
            'role' => $role,
            'campus' => $kampus,
            'cohort' => $angkatan,
            'is_verified' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    /**
     * Hapus data lama milik akun demo saja (transaksi, rating, produk, media, reward),
     * supaya seeder bisa dijalankan ulang tanpa menggandakan data.
     * Data akun lain tidak disentuh, kecuali transaksi yang melibatkan akun demo.
     */
    private function bersihkanDataLama(array $idDemo): void
    {
        $idProdukLama = DB::table('products')->whereIn('seller_id', $idDemo)->pluck('product_id')->all();

        // transaction_items dan ratings ikut terhapus (cascade).
        DB::table('transactions')
            ->where(fn ($q) => $q->whereIn('buyer_id', $idDemo)->orWhereIn('seller_id', $idDemo))
            ->delete();

        DB::table('reward_records')->whereIn('user_id', $idDemo)->delete();

        // product_media dan conversations ikut terhapus (cascade).
        DB::table('products')->whereIn('seller_id', $idDemo)->delete();

        foreach ($idProdukLama as $id) {
            Storage::disk('public')->deleteDirectory('products/'.$id);
        }
    }

    // ------------------------------------------------------------------
    // Produk (30) + foto
    // ------------------------------------------------------------------

    /**
     * @return array<int, list<array{id:int, harga:int, nego:bool}>>  produk per id penjual
     */
    private function buatProduk(User $penjual, User $pembeli): array
    {
        $idKategori = DB::table('categories')->pluck('category_id', 'category_name')->all();

        // [nama, kategori, harga, kondisi, ukuran, lama pemakaian, kekurangan]
        $daftar = [
            ['Jaket Denim Oversize', 'Fashion', 250000, 'Bekas', 'L', '1 tahun', null],
            ['Sepatu Sneakers Putih', 'Fashion', 300000, 'Seperti Baru', '42', '6 bulan', null],
            ['Tas Ransel Kuliah 30L', 'Fashion', 275000, 'Bekas', null, '1 tahun', 'Resleting kantong depan agak seret.'],
            ['Kemeja Flanel Kotak-Kotak', 'Fashion', 85000, 'Bekas', 'M', '8 bulan', null],
            ['Hoodie Polos Hitam', 'Fashion', 120000, 'Seperti Baru', 'L', '3 bulan', null],
            ['Celana Jeans Slim Fit', 'Fashion', 150000, 'Bekas', '32', '1 tahun', null],
            ['Jam Tangan Digital Retro', 'Aksesoris', 350000, 'Bekas', null, '2 tahun', 'Ada goresan halus di kaca.'],
            ['Topi Baseball Hitam', 'Aksesoris', 90000, 'Seperti Baru', null, '2 bulan', null],
            ['Dompet Kulit Lipat', 'Aksesoris', 65000, 'Baru', null, null, null],
            ['Smartphone Android 6/128GB', 'Gadget', 2400000, 'Bekas', null, '1,5 tahun', 'Baterai tersisa sekitar 85%.'],
            ['Headphone Bluetooth', 'Gadget', 450000, 'Seperti Baru', null, '4 bulan', null],
            ['Power Bank 20000 mAh', 'Gadget', 180000, 'Bekas', null, '1 tahun', null],
            ['Smartband Pelacak Aktivitas', 'Gadget', 280000, 'Seperti Baru', null, '5 bulan', null],
            ['Buku Kalkulus Edisi 9', 'Buku & Alat Tulis', 95000, 'Bekas', null, '2 semester', 'Ada sedikit coretan stabilo.'],
            ['Kalkulator Scientific', 'Buku & Alat Tulis', 200000, 'Seperti Baru', null, '1 semester', null],
            ['Set Alat Gambar Teknik', 'Buku & Alat Tulis', 110000, 'Baru', null, null, null],
            ['Rice Cooker Mini 0,6L', 'Dapur & Peralatan Masak', 150000, 'Bekas', null, '1 tahun', null],
            ['Set Panci Anti Lengket 3 Pcs', 'Dapur & Peralatan Masak', 175000, 'Seperti Baru', null, '3 bulan', null],
            ['Blender Portable', 'Dapur & Peralatan Masak', 120000, 'Bekas', null, '8 bulan', null],
            ['Kipas Angin Meja Mini', 'Elektronik', 85000, 'Seperti Baru', null, '4 bulan', null],
            ['Monitor LED 22 Inci', 'Elektronik', 650000, 'Bekas', null, '2 tahun', 'Ada 1 titik piksel mati di pojok kiri.'],
            ['Keyboard Mekanikal 87 Key', 'Elektronik', 320000, 'Seperti Baru', null, '6 bulan', null],
            ['Action Figure Koleksi', 'Hobi & Koleksi', 275000, 'Baru', null, null, null],
            ['Gitar Akustik Pemula', 'Hobi & Koleksi', 850000, 'Bekas', null, '2 tahun', 'Senar perlu diganti.'],
            ['Raket Badminton Karbon', 'Olahraga', 280000, 'Seperti Baru', null, '5 bulan', null],
            ['Bola Futsal Ukuran 4', 'Olahraga', 120000, 'Baru', null, null, null],
            ['Sepeda Lipat 16 Inci', 'Kendaraan', 1500000, 'Bekas', null, '2 tahun', 'Rem belakang perlu disetel.'],
            ['Helm Half Face SNI', 'Kendaraan', 135000, 'Bekas', 'All Size', '1 tahun', null],
            ['Meja Belajar Lipat', 'Perabotan', 160000, 'Seperti Baru', null, '6 bulan', null],
            ['Lampu Belajar LED', 'Perabotan', 70000, 'Baru', null, null, null],
        ];

        $hasil = [$penjual->user_id => [], $pembeli->user_id => []];
        $total = count($daftar);

        foreach ($daftar as $i => [$nama, $kategori, $harga, $kondisi, $ukuran, $pemakaian, $kekurangan]) {
            // Selang-seling: 15 produk milik penjual demo, 15 milik pembeli demo.
            $pemilik = $i % 2 === 0 ? $penjual : $pembeli;
            $nego = $i % 4 !== 3; // sebagian kecil tidak bisa nego
            $dibuat = now()->subDays(($total - $i) * 3 + 5)->toDateTimeString();

            $idProduk = DB::table('products')->insertGetId([
                'seller_id' => $pemilik->user_id,
                'category_id' => $idKategori[$kategori],
                'product_name' => $nama,
                'description' => "Contoh produk preloved: {$nama}. Kondisi {$kondisi}, siap dipakai. COD di area kampus.",
                'price' => $harga,
                'condition' => $kondisi,
                'size' => $ukuran,
                'usage_period' => $pemakaian,
                'defects' => $kekurangan,
                'status' => 'tersedia',
                'stock' => 5,
                'is_negotiable' => $nego,
                'created_at' => $dibuat,
                'updated_at' => $dibuat,
            ]);

            $gambar = $this->buatGambar($nama, $kategori, $i);
            $path = 'products/'.$idProduk.'/'.Str::uuid().'.jpg';
            Storage::disk('public')->put($path, $gambar);

            DB::table('product_media')->insert([
                'product_id' => $idProduk,
                'media_type' => 'foto',
                'media_url' => $path,
                'file_size' => strlen($gambar),
                'display_order' => 1,
            ]);

            $hasil[$pemilik->user_id][] = ['id' => $idProduk, 'harga' => $harga, 'nego' => $nego];
        }

        return $hasil;
    }

    /**
     * Gambar JPEG 640x640: latar gradasi, nama produk, kategori, dan label DEMO.
     * Teks diperbesar dari font bawaan GD, jadi tidak butuh file font apa pun.
     */
    private function buatGambar(string $judul, string $kategori, int $indeks): string
    {
        $ukuran = 640;
        [$r, $g, $b] = self::PALET[$indeks % count(self::PALET)];

        $im = imagecreatetruecolor($ukuran, $ukuran);
        imagealphablending($im, true);

        for ($y = 0; $y < $ukuran; $y++) {
            $f = 1 - 0.4 * ($y / $ukuran);
            imageline($im, 0, $y, $ukuran, $y, imagecolorallocate($im, (int) ($r * $f), (int) ($g * $f), (int) ($b * $f)));
        }

        // Hiasan lingkaran transparan.
        $lingkaran = imagecolorallocatealpha($im, 255, 255, 255, 105);
        imagefilledellipse($im, 520, 120, 360, 360, $lingkaran);
        imagefilledellipse($im, 90, 560, 260, 260, $lingkaran);

        $this->tulis($im, strtoupper($kategori), 320, 70, 2);

        $baris = $this->bungkusTeks($judul, 15);
        $y = 320 - (count($baris) * 56) / 2;
        foreach ($baris as $teks) {
            $this->tulis($im, $teks, 320, (int) $y, 4);
            $y += 56;
        }

        $this->tulis($im, 'CONTOH PRODUK (DEMO)', 320, 580, 2);

        ob_start();
        imagejpeg($im, null, 85);
        $data = (string) ob_get_clean();
        imagedestroy($im);

        return $data;
    }

    /** Tulis teks putih di tengah (x = $tengahX), diperbesar $skala kali. */
    private function tulis(\GdImage $im, string $teks, int $tengahX, int $y, int $skala): void
    {
        $font = 5;
        $lebar = imagefontwidth($font) * strlen($teks);
        $tinggi = imagefontheight($font);

        $kecil = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($kecil, false);
        imagesavealpha($kecil, true);
        imagefill($kecil, 0, 0, imagecolorallocatealpha($kecil, 0, 0, 0, 127));
        imagestring($kecil, $font, 0, 0, $teks, imagecolorallocate($kecil, 255, 255, 255));

        $lebarBesar = $lebar * $skala;
        imagecopyresampled($im, $kecil, (int) ($tengahX - $lebarBesar / 2), $y, 0, 0, $lebarBesar, $tinggi * $skala, $lebar, $tinggi);
        imagedestroy($kecil);
    }

    /** @return list<string> */
    private function bungkusTeks(string $teks, int $maksKarakter): array
    {
        return array_map('trim', explode("\n", wordwrap($teks, $maksKarakter, "\n", true)));
    }

    // ------------------------------------------------------------------
    // Transaksi + item + rating + reward
    // ------------------------------------------------------------------

    /**
     * @param  list<array{id:int, harga:int, nego:bool}>  $produkPenjual
     */
    private function buatTransaksi(User $pembeli, User $penjual, array $produkPenjual, int $geserPola): void
    {
        $titik = DB::table('campus_meeting_points')
            ->where('is_active', true)
            ->where('campus', $penjual->campus)
            ->pluck('meeting_point_id')
            ->all();

        for ($i = 0; $i < self::JUMLAH_TRANSAKSI; $i++) {
            $produk = $produkPenjual[$i % count($produkPenjual)];
            $status = self::POLA_STATUS[($i + $geserPola) % count(self::POLA_STATUS)];

            // Harga satuan: sebagian transaksi memakai harga hasil nego (diskon 5-15%, dibulatkan ribuan).
            $harga = $produk['harga'];
            $satuan = $harga;
            if ($produk['nego'] && $i % 3 === 0) {
                $satuan = (int) (round($harga * (1 - mt_rand(5, 15) / 100) / 1000) * 1000);
            }
            $diskon = max(0, $harga - $satuan);

            // Pesanan yang masih berjalan dibuat baru-baru ini, yang selesai/batal lebih lama.
            $berjalan = in_array($status, ['menunggu', 'dijadwalkan'], true);
            $dibuat = now()->subDays($berjalan ? mt_rand(0, 4) : mt_rand(6, 90))->subMinutes(mt_rand(0, 600));

            $jadwal = null;
            $selesai = null;
            if ($status === 'dijadwalkan' || $status === 'selesai') {
                $jadwal = $dibuat->copy()->addDays(mt_rand(1, 3))->setTime(mt_rand(9, 16), mt_rand(0, 1) * 30);
            }
            if ($status === 'selesai') {
                $selesai = $jadwal->copy()->addMinutes(mt_rand(30, 90));
            }

            $idTransaksi = DB::table('transactions')->insertGetId([
                'buyer_id' => $pembeli->user_id,
                'seller_id' => $penjual->user_id,
                'meeting_point_id' => $titik ? $titik[array_rand($titik)] : null,
                'subtotal_price' => $satuan,
                'total_price' => $satuan,
                'agreed_discount' => $diskon,
                'service_fee' => 0,
                'payment_method' => 'cod',
                'buyer_notes' => self::CATATAN_PEMBELI[array_rand(self::CATATAN_PEMBELI)],
                'transaction_status' => $status,
                'scheduled_at' => $jadwal?->toDateTimeString(),
                'completed_at' => $selesai?->toDateTimeString(),
                'created_at' => $dibuat->toDateTimeString(),
            ]);

            DB::table('transaction_items')->insert([
                'transaction_id' => $idTransaksi,
                'product_id' => $produk['id'],
                'quantity' => 1,
                'agreed_price' => $satuan,
                'subtotal' => $satuan,
            ]);

            if ($status === 'selesai') {
                $this->buatRating($idTransaksi, $pembeli, $penjual, $selesai);
            }
        }
    }

    /** Rating dari pembeli ke penjual (selalu), dan dari penjual ke pembeli (sekitar 60%). */
    private function buatRating(int $idTransaksi, User $pembeli, User $penjual, $waktuSelesai): void
    {
        $arah = [[$pembeli, $penjual]];
        if (mt_rand(1, 10) <= 6) {
            $arah[] = [$penjual, $pembeli];
        }

        foreach ($arah as $n => [$pemberi, $penerima]) {
            $bintang = [5, 5, 5, 4, 4, 3][mt_rand(0, 5)];
            $waktu = $waktuSelesai->copy()->addHours($n + 1)->toDateTimeString();

            DB::table('ratings')->insert([
                'transaction_id' => $idTransaksi,
                'reviewer_id' => $pemberi->user_id,
                'reviewed_user_id' => $penerima->user_id,
                'stars' => $bintang,
                'review' => self::ULASAN[$bintang][array_rand(self::ULASAN[$bintang])],
                'created_at' => $waktu,
            ]);

            // Sama seperti TransactionController::storeRating: pemberi dan penerima masing-masing +25 poin.
            DB::table('reward_records')->insert([
                [
                    'user_id' => $pemberi->user_id,
                    'points' => 25,
                    'reward_type' => 'poin',
                    'description' => "Memberikan rating pada Transaksi #{$idTransaksi}",
                    'created_at' => $waktu,
                ],
                [
                    'user_id' => $penerima->user_id,
                    'points' => 25,
                    'reward_type' => 'poin',
                    'description' => "Transaksi #{$idTransaksi} selesai dan menerima rating",
                    'created_at' => $waktu,
                ],
            ]);
        }
    }

    // ------------------------------------------------------------------

    private function ringkasan(int $idPenjual, int $idPembeli): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info('Data demo selesai dibuat:');
        $this->command->line('  Produk              : '.DB::table('products')->whereIn('seller_id', [$idPenjual, $idPembeli])->count());
        $this->command->line('  Penjualan Saya      : '.DB::table('transactions')->where('seller_id', $idPenjual)->count().' (penjual.demo)');
        $this->command->line('  Pembelian Saya      : '.DB::table('transactions')->where('buyer_id', $idPenjual)->count().' (penjual.demo)');
        $this->command->line('  Rating              : '.DB::table('ratings')->count());
        $this->command->line('');
        $this->command->line('Login user  : '.self::EMAIL_PENJUAL.' / '.self::EMAIL_PEMBELI.'  (halaman /login)');
        $this->command->line('Login admin : '.self::EMAIL_ADMIN.'  (halaman /admin/login)');
        $this->command->line('Password    : '.self::PASSWORD);
        $this->command->warn('Ganti atau hapus akun demo ini sebelum website dipakai pengguna sungguhan.');
    }
}