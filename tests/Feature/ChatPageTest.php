<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get(route('chat.index'))->assertRedirect(route('login'));
    }

    public function test_daftar_percakapan_menampilkan_lawan_bicara_produk_dan_pesan_terakhir(): void
    {
        [$buyer, $seller, $product, $conversation] = $this->createChat();
        Message::create(['conversation_id' => $conversation->conversation_id, 'sender_id' => $seller->user_id, 'message_text' => 'Masih ada kak']);

        $this->actingAs($buyer)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Penjual Satu')
            ->assertSee('Jaket Kampus')
            ->assertSee('Masih ada kak')
            ->assertSee('Pilih percakapan untuk mulai membaca pesan.');
    }

    public function test_percakapan_dibuka_dan_pesan_lawan_ditandai_dibaca(): void
    {
        [$buyer, $seller, , $conversation] = $this->createChat();
        $fromSeller = Message::create(['conversation_id' => $conversation->conversation_id, 'sender_id' => $seller->user_id, 'message_text' => 'Halo pembeli']);
        $fromBuyer = Message::create(['conversation_id' => $conversation->conversation_id, 'sender_id' => $buyer->user_id, 'message_text' => 'Halo penjual']);

        $this->actingAs($buyer)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('Halo pembeli')
            ->assertSee('Halo penjual')
            ->assertSee('data-chat-thread', false)
            ->assertDontSee('Permintaan wishlist')
            ->assertDontSee('Tawarkan barang untuk wishlist');

        $this->assertNotNull($fromSeller->fresh()->read_at);
        $this->assertNull($fromBuyer->fresh()->read_at);
    }

    public function test_user_luar_tidak_bisa_membuka_percakapan(): void
    {
        [, , , $conversation] = $this->createChat();
        $outsider = $this->createUser('Orang Luar');

        $this->actingAs($outsider)->get(route('chat.show', $conversation))->assertForbidden();
    }

    public function test_daftar_hanya_berisi_percakapan_milik_user(): void
    {
        [$buyer, , , ] = $this->createChat();
        $otherSeller = $this->createUser('Penjual Lain');
        $otherBuyer = $this->createUser('Pembeli Lain');
        $otherProduct = $this->createProduct($otherSeller, 'Sepatu Rahasia');
        Conversation::create(['product_id' => $otherProduct->product_id, 'buyer_id' => $otherBuyer->user_id, 'seller_id' => $otherSeller->user_id]);

        $this->actingAs($buyer)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertDontSee('Sepatu Rahasia');
    }

    public function test_filter_penjual_pembeli_dan_belum_dibaca(): void
    {
        [$buyer, $seller, , $conversation] = $this->createChat();
        Message::create(['conversation_id' => $conversation->conversation_id, 'sender_id' => $seller->user_id, 'message_text' => 'Belum kamu baca']);

        $this->actingAs($buyer)->get(route('chat.index', ['filter' => 'penjual']))
            ->assertOk()->assertSee('Tidak ada percakapan yang cocok.');
        $this->get(route('chat.index', ['filter' => 'pembeli']))
            ->assertOk()->assertSee('Penjual Satu');
        $this->get(route('chat.index', ['filter' => 'belum']))
            ->assertOk()->assertSee('Penjual Satu');

        $this->actingAs($seller)->get(route('chat.index', ['filter' => 'belum']))
            ->assertOk()->assertSee('Tidak ada percakapan yang cocok.');
    }

    public function test_pencarian_menurut_nama_lawan_atau_produk(): void
    {
        [$buyer, , , ] = $this->createChat();

        $this->actingAs($buyer)->get(route('chat.index', ['q' => 'Penjual Satu']))->assertOk()->assertSee('Jaket Kampus');
        $this->get(route('chat.index', ['q' => 'Jaket']))->assertOk()->assertSee('Penjual Satu');
        $this->get(route('chat.index', ['q' => 'tidak-ada']))->assertOk()->assertSee('Tidak ada percakapan yang cocok.');
    }

    public function test_kartu_penawaran_tampil_dan_tombol_terima_tolak_hanya_untuk_penjual(): void
    {
        [$buyer, $seller, , $conversation] = $this->createChat();
        $offer = Negotiation::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
            'offered_price' => 120000,
            'quantity' => 1,
            'status' => 'menunggu',
        ]);

        $this->actingAs($buyer)->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('Penawaran harga')
            ->assertSee('Rp120.000')
            ->assertSee('Penawaran Terkirim')
            ->assertDontSee(route('negotiations.accept', $offer), false);

        $this->actingAs($seller)->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('Rp120.000')
            ->assertSee(route('negotiations.accept', $offer), false)
            ->assertSee(route('negotiations.reject', $offer), false);
    }
    public function test_tombol_chat_di_notifikasi_membuka_percakapan(): void
    {
        [$buyer, $seller, $product, $conversation] = $this->createChat();
        $offer = Negotiation::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
            'parent_offer_id' => null,
            'offered_price' => 90000,
            'quantity' => 1,
            'status' => 'menunggu',
        ]);

        foreach ([[$seller, 'penjual'], [$buyer, 'pembeli']] as [$user, $role]) {
            $notification = \App\Models\UserNotification::create([
                'user_id' => $user->user_id,
                'product_id' => $product->product_id,
                'negotiation_id' => $offer->negotiation_id,
                'role' => $role,
                'notification_type' => 'negosiasi',
                'title' => 'Tawaran nego baru',
                'message' => 'Tawaran',
                'is_read' => false,
            ]);

            $this->actingAs($user);
            $this->assertSame(route('chat.show', $conversation), $notification->fresh()->target_url);
        }
    }
    /**
     * @return array{0: User, 1: User, 2: Product, 3: Conversation}
     */
    private function createChat(): array
    {
        $seller = $this->createUser('Penjual Satu');
        $buyer = $this->createUser('Pembeli Satu');
        $product = $this->createProduct($seller, 'Jaket Kampus');
        $conversation = Conversation::create([
            'product_id' => $product->product_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
        ]);

        return [$buyer, $seller, $product, $conversation];
    }

    private function createUser(string $name): User
    {
        static $counter = 0;
        $counter++;

        return User::forceCreate([
            'name' => $name,
            'email' => 'page-'.$counter.'-'.uniqid().'@example.test',
            'password' => 'password',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'is_verified' => true,
            'role' => 'user',
        ]);
    }

    private function createProduct(User $seller, string $name): Product
    {
        $category = Category::firstOrCreate(['category_name' => 'Fashion']);

        return Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => $name,
            'description' => 'Barang dalam kondisi baik dan layak digunakan.',
            'price' => 150000,
            'condition' => 'Baik',
            'status' => 'tersedia',
        ]);
    }
}