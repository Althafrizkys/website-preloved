<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Aksesoris',
            'Gadget',
            'Buku & Alat Tulis',
            'Dapur & Peralatan Masak',
            'Elektronik',
            'Fashion',
            'Hobi & Koleksi',
            'Kendaraan',
            'Kosmetik & Skincare',
            'Olahraga',
            'Perabotan',
            'Tiket & Voucher',
            'Lainnya',
        ] as $categoryName) {
            Category::firstOrCreate([
                'category_name' => $categoryName,
            ]);
        }
    }
}
