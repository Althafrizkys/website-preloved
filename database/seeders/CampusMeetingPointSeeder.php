<?php

namespace Database\Seeders;

use App\Models\CampusMeetingPoint;
use Illuminate\Database\Seeder;

class CampusMeetingPointSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            // UNSIKA
            [
                'campus' => 'UNSIKA',
                'point_name' => 'Gerbang Utama UNSIKA (Area Pos Satpam)',
                'address_description' => 'Depan pos satpam gerbang utama kampus UNSIKA, Jl. HS. Ronggowaluyo',
                'is_active' => true,
            ],
            [
                'campus' => 'UNSIKA',
                'point_name' => 'Perpustakaan Pusat UNSIKA (Lobi Utama)',
                'address_description' => 'Lobi lantai 1 gedung perpustakaan pusat UNSIKA',
                'is_active' => true,
            ],
            [
                'campus' => 'UNSIKA',
                'point_name' => 'Kantin Fasilkom / FISIP UNSIKA',
                'address_description' => 'Area tempat duduk santai kantin bersama Fasilkom dan FISIP UNSIKA',
                'is_active' => true,
            ],
            [
                'campus' => 'UNSIKA',
                'point_name' => 'Gedung Rektorat UNSIKA',
                'address_description' => 'Halaman depan tangga lobi utama gedung rektorat UNSIKA',
                'is_active' => true,
            ],
            [
                'campus' => 'UNSIKA',
                'point_name' => 'Kopma UNSIKA',
                'address_description' => 'Depan minimarket Koperasi Mahasiswa UNSIKA',
                'is_active' => true,
            ],

            // UBP
            [
                'campus' => 'UBP',
                'point_name' => 'Gerbang Utama UBP Karawang',
                'address_description' => 'Depan pos satpam gerbang masuk utama kampus UBP Karawang',
                'is_active' => true,
            ],
            [
                'campus' => 'UBP',
                'point_name' => 'Lobi Gedung Rektorat UBP',
                'address_description' => 'Lobi utama lantai dasar gedung rektorat UBP Karawang',
                'is_active' => true,
            ],
            [
                'campus' => 'UBP',
                'point_name' => 'Kantin Kampus UBP',
                'address_description' => 'Area sentra kuliner / kantin mahasiswa UBP Karawang',
                'is_active' => true,
            ],
            [
                'campus' => 'UBP',
                'point_name' => 'Area Parkir Gedung B UBP',
                'address_description' => 'Titik kumpul parkir motor di samping gedung B kampus UBP',
                'is_active' => true,
            ],
            [
                'campus' => 'UBP',
                'point_name' => 'Perpustakaan UBP (Lantai 2)',
                'address_description' => 'Depan pintu masuk perpustakaan kampus UBP Karawang lantai 2',
                'is_active' => true,
            ],
        ];

        foreach ($points as $point) {
            CampusMeetingPoint::firstOrCreate(
                [
                    'campus' => $point['campus'],
                    'point_name' => $point['point_name'],
                ],
                $point
            );
        }
    }
}
