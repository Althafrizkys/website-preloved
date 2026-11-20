<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Fashion',
            'Elektronik',
            'Buku & Alat Kuliah',
            'Perabot Kos',
            'Olahraga',
            'Lainnya',
        ] as $categoryName) {
            Category::firstOrCreate([
                'category_name' => $categoryName,
            ]);
        }
    }
}
