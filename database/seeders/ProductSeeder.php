<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sellerId   = DB::table('users')->value('user_id');
        $categoryId = DB::table('categories')->value('category_id');

        if (!$sellerId || !$categoryId) {
            $this->command->error('Isi dulu minimal 1 baris di tabel users dan categories.');
            return;
        }

        $items = [
            ['Jaket Denim Levis',     'Jaket denim ukuran M, masih tebal dan nyaman.',  180000, 'Baik',         'M',  '1 tahun'],
            ['Sepatu Sneakers Putih', 'Sneakers putih ukuran 42, jarang dipakai.',      250000, 'Seperti baru', '42', '6 bulan'],
            ['Kemeja Flanel Kotak',   'Kemeja flanel lengan panjang, bahan adem.',       75000, 'Baik',         'L',  '2 tahun'],
            ['Tas Ransel Eiger',      'Ransel 30L, resleting masih lancar.',            200000, 'Cukup',        null, '3 tahun'],
            ['Hoodie Hitam Polos',    'Hoodie fleece hitam, tidak ada noda.',           110000, 'Seperti baru', 'XL', '3 bulan'],
            ['Celana Cargo Krem',     'Celana cargo ukuran 32, kantong banyak.',         95000, 'Baik',         '32', '1 tahun'],
            ['Jam Tangan Digital',    'Jam tangan digital, baterai baru diganti.',       60000, 'Cukup',        null, '2 tahun'],
            ['Kaos Band Vintage',     'Kaos band ukuran L, sablon masih jelas.',         85000, 'Baik',         'L',  '1 tahun'],
        ];

        foreach ($items as [$name, $desc, $price, $cond, $size, $usage]) {
            Product::create([
                'seller_id'    => $sellerId,
                'category_id'  => $categoryId,
                'product_name' => $name,
                'description'  => $desc,
                'price'        => $price,
                'condition'    => $cond,
                'size'         => $size,
                'usage_period' => $usage,
                'status'       => 'tersedia',
            ]);
        }
    }
}