<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::firstOrCreate(
            ['email' => 'penjual@seklov.test'],
            [
                'name' => 'Fathan Seller',
                'password' => bcrypt('password'),
                'campus' => 'UNSIKA',
                'cohort' => '2023',
                'is_verified' => true,
                'role' => 'user',
            ]
        );

        $buyer = User::firstOrCreate(
            ['email' => 'pembeli@seklov.test'],
            [
                'name' => 'Budi Pembeli',
                'password' => bcrypt('password'),
                'campus' => 'UBP',
                'cohort' => '2024',
                'is_verified' => true,
                'role' => 'user',
            ]
        );

        $fashionCategory = Category::firstOrCreate(['category_name' => 'Fashion']);

        $sampleItems = [
            [
                'name' => 'Nike Court Version LO NN Original',
                'price' => 850000,
                'offer' => 800000,
                'time' => now()->subHour(),
                'is_read' => false,
                'role' => 'penjual',
            ],
            [
                'name' => 'Nike T-Shirt',
                'price' => 400000,
                'offer' => 370000,
                'time' => now()->subHours(4),
                'is_read' => false,
                'role' => 'penjual',
            ],
            [
                'name' => 'sendal Slip On Converse',
                'price' => 650000,
                'offer' => 500000,
                'time' => now()->subHours(4),
                'is_read' => false,
                'role' => 'penjual',
            ],
            [
                'name' => 'Rolex TDR-3000',
                'price' => 12000000,
                'offer' => 8000000,
                'time' => now()->subDays(2),
                'is_read' => true,
                'role' => 'penjual',
            ],
        ];

        foreach ($sampleItems as $item) {
            $product = Product::firstOrCreate(
                [
                    'seller_id' => $seller->user_id,
                    'product_name' => $item['name'],
                ],
                [
                    'category_id' => $fashionCategory->category_id,
                    'description' => 'Barang preloved kondisi sangat baik dan siap pakai langsung COD kampus.',
                    'price' => $item['price'],
                    'condition' => 'Baik',
                    'status' => 'tersedia',
                ]
            );

            $conversation = Conversation::firstOrCreate([
                'product_id' => $product->product_id,
                'buyer_id' => $buyer->user_id,
                'seller_id' => $seller->user_id,
            ]);

            $negotiation = Negotiation::create([
                'conversation_id' => $conversation->conversation_id,
                'sender_id' => $buyer->user_id,
                'parent_offer_id' => null,
                'offered_price' => $item['offer'],
                'quantity' => 1,
                'status' => $item['is_read'] ? 'diterima' : 'menunggu',
                'created_at' => $item['time'],
            ]);

            UserNotification::create([
                'user_id' => $seller->user_id,
                'product_id' => $product->product_id,
                'negotiation_id' => $negotiation->negotiation_id,
                'role' => 'penjual',
                'notification_type' => 'negosiasi',
                'title' => 'Tawaran nego baru',
                'message' => $buyer->name.' menawar '.$product->product_name.' seharga Rp '.number_format($item['offer'], 0, ',', '.').'.',
                'offered_price' => $item['offer'],
                'original_price' => $item['price'],
                'is_read' => $item['is_read'],
                'created_at' => $item['time'],
            ]);
        }

        // Contoh notifikasi untuk Pembeli
        $buyerProduct = Product::firstOrCreate(
            [
                'seller_id' => $seller->user_id,
                'product_name' => 'Hoodie H&M Regular Fit',
            ],
            [
                'category_id' => $fashionCategory->category_id,
                'description' => 'Hoodie original lembut cocok untuk kuliah.',
                'price' => 350000,
                'condition' => 'Seperti baru',
                'status' => 'tersedia',
            ]
        );

        $buyerConv = Conversation::firstOrCreate([
            'product_id' => $buyerProduct->product_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
        ]);

        $buyerNego = Negotiation::create([
            'conversation_id' => $buyerConv->conversation_id,
            'sender_id' => $buyer->user_id,
            'offered_price' => 280000,
            'quantity' => 1,
            'status' => 'diterima',
            'created_at' => now()->subHours(2),
        ]);

        UserNotification::create([
            'user_id' => $buyer->user_id,
            'product_id' => $buyerProduct->product_id,
            'negotiation_id' => $buyerNego->negotiation_id,
            'role' => 'pembeli',
            'notification_type' => 'negosiasi',
            'title' => 'Tawaran nego diterima',
            'message' => 'Tawaranmu untuk '.$buyerProduct->product_name.' sebesar Rp 280.000 telah diterima.',
            'offered_price' => 280000,
            'original_price' => 350000,
            'is_read' => false,
            'created_at' => now()->subHours(2),
        ]);
    }
}
