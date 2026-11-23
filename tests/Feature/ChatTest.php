<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_pembeli_dan_penjual_bisa_mengirim_pesan(): void
    {
        [$buyer, $seller, , $conversation] = $this->createChat();

        $this->actingAs($buyer)
            ->postJson(route('chat.store', $conversation), ['message_text' => 'Halo, masih ada?'])
            ->assertCreated()
            ->assertJsonPath('message.message_text', 'Halo, masih ada?')
            ->assertJsonPath('message.sender_id', $buyer->user_id);

        $this->actingAs($seller)
            ->postJson(route('chat.store', $conversation), ['message_text' => 'Masih ada kak.'])
            ->assertCreated();

        $this->assertDatabaseCount('messages', 2);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $seller->user_id,
            'message_text' => 'Masih ada kak.',
        ]);
    }

    public function test_pesan_kosong_dan_terlalu_panjang_ditolak(): void
    {
        [$buyer, , , $conversation] = $this->createChat();

        $this->actingAs($buyer)
            ->postJson(route('chat.store', $conversation), ['message_text' => ''])
            ->assertJsonValidationErrors('message_text');

        $this->postJson(route('chat.store', $conversation), ['message_text' => str_repeat('a', 1001)])
            ->assertJsonValidationErrors('message_text');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_user_luar_tidak_bisa_membaca_atau_mengirim_pesan(): void
    {
        [, , , $conversation] = $this->createChat();
        $outsider = $this->createUser('Orang Luar');

        $this->actingAs($outsider)
            ->getJson(route('chat.messages', $conversation))
            ->assertForbidden();

        $this->postJson(route('chat.store', $conversation), ['message_text' => 'Intip'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        [, , , $conversation] = $this->createChat();

        $this->get(route('chat.messages', $conversation))->assertRedirect(route('login'));
        $this->post(route('chat.store', $conversation), ['message_text' => 'Halo'])->assertRedirect(route('login'));
    }

    public function test_polling_hanya_mengembalikan_pesan_setelah_id_tertentu(): void
    {
        [$buyer, $seller, , $conversation] = $this->createChat();

        $first = Message::create(['conversation_id' => $conversation->conversation_id, 'sender_id' => $buyer->user_id, 'message_text' => 'Satu']);
        $second = Message::create(['conversation_id' => $conversation->conversation_id, 'sender_id' => $seller->user_id, 'message_text' => 'Dua']);

        $this->actingAs($buyer)
            ->getJson(route('chat.messages', ['conversation' => $conversation, 'after' => $first->message_id]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message_id', $second->message_id);

        $this->getJson(route('chat.messages', $conversation))
            ->assertJsonCount(2, 'messages');
    }

    public function test_penjual_dan_admin_tidak_bisa_memulai_chat_di_barang_sendiri_atau_sebagai_admin(): void
    {
        [, $seller, $product] = $this->createChat();

        $this->actingAs($seller)
            ->post(route('chat.start', $product))
            ->assertForbidden();

        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin-chat@example.test',
            'password' => 'password',
            'is_verified' => true,
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('chat.start', $product))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: Product, 3: Conversation}
     */
    private function createChat(): array
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
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
            'email' => 'chat-'.$counter.'-'.uniqid().'@example.test',
            'password' => 'password',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'is_verified' => true,
            'role' => 'user',
        ]);
    }

    private function createProduct(User $seller): Product
    {
        $category = Category::create(['category_name' => 'Fashion']);

        return Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Jaket Kampus',
            'description' => 'Jaket kampus masih dalam kondisi baik dan layak digunakan.',
            'price' => 150000,
            'condition' => 'Baik',
            'status' => 'tersedia',
        ]);
    }
}