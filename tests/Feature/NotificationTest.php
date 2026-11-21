<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NegotiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_halaman_notifikasi(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_dapat_melihat_halaman_pusat_notifikasi_dengan_item_dan_desain_figma(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $category = Category::create(['category_name' => 'Fashion']);

        $product = Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Nike Court Version LO NN Original',
            'description' => 'Sepatu sneakers original kampus masih bagus.',
            'price' => 850000,
            'condition' => 'Baik',
            'status' => 'tersedia',
        ]);

        $conversation = Conversation::create([
            'product_id' => $product->product_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
        ]);

        $negotiation = Negotiation::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
            'offered_price' => 800000,
            'quantity' => 1,
            'status' => 'menunggu',
            'created_at' => now()->subHour(),
        ]);

        $notification = UserNotification::create([
            'user_id' => $seller->user_id,
            'product_id' => $product->product_id,
            'negotiation_id' => $negotiation->negotiation_id,
            'role' => 'penjual',
            'notification_type' => 'negosiasi',
            'title' => '💬 Penawaran masuk — '.$product->product_name,
            'message' => 'Pembeli menawar Nike Court Version LO NN Original',
            'offered_price' => 800000,
            'original_price' => 850000,
            'is_read' => false,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($seller)->get(route('notifications.index'));

        $response->assertOk()
            ->assertSee('Pusat Notifikasi')
            ->assertSee('Semua')
            ->assertSee('Belum dibaca')
            ->assertSee('Penjual')
            ->assertSee('Pembeli')
            ->assertSee('💬 Penawaran masuk')
            ->assertSee('Nike Court Version LO NN Original')
            ->assertSee('Pembeli menawar Nike Court Version LO NN Original')
            ->assertSee('data-unread-dot', false)
            ->assertSee('data-chat-link', false);
    }

    public function test_navbar_menampilkan_tombol_pusat_notifikasi_dan_badge_belum_dibaca(): void
    {
        $user = $this->createUser('Mahasiswa');

        // Belum ada notifikasi
        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('data-notification-trigger', false)
            ->assertSee('data-notification-dialog', false)
            ->assertSee('hidden', false);

        // Tambah notifikasi unread
        UserNotification::create([
            'user_id' => $user->user_id,
            'role' => 'penjual',
            'notification_type' => 'negosiasi',
            'title' => 'Notifikasi test',
            'message' => 'Contoh notifikasi unread',
            'is_read' => false,
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('data-notification-badge', false)
            ->assertSeeText('1');
    }

    public function test_user_dapat_menandai_notifikasi_sebagai_dibaca(): void
    {
        $user = $this->createUser('User Satu');

        $notification = UserNotification::create([
            'user_id' => $user->user_id,
            'role' => 'penjual',
            'notification_type' => 'negosiasi',
            'title' => 'Notifikasi test baca',
            'message' => 'Pesan tawaran',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('notifications.read', $notification), [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_tidak_dapat_menandai_notifikasi_milik_user_lain(): void
    {
        $owner = $this->createUser('Pemilik');
        $other = $this->createUser('Orang Lain');

        $notification = UserNotification::create([
            'user_id' => $owner->user_id,
            'role' => 'penjual',
            'notification_type' => 'negosiasi',
            'title' => 'Notifikasi milik pemilik',
            'message' => 'Pesan tawaran',
            'is_read' => false,
        ]);

        $this->actingAs($other)
            ->patch(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_user_dapat_menandai_semua_notifikasi_sebagai_dibaca(): void
    {
        $user = $this->createUser('User Satu');

        UserNotification::create([
            'user_id' => $user->user_id,
            'role' => 'penjual',
            'notification_type' => 'negosiasi',
            'title' => 'Notif 1',
            'message' => 'Pesan 1',
            'is_read' => false,
        ]);

        UserNotification::create([
            'user_id' => $user->user_id,
            'role' => 'pembeli',
            'notification_type' => 'negosiasi',
            'title' => 'Notif 2',
            'message' => 'Pesan 2',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->post(route('notifications.readAll'), [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);

        $this->assertSame(0, $user->notifications()->where('is_read', false)->count());
    }

    public function test_alur_negosiasi_otomatis_memperbarui_notifikasi_dengan_harga_dan_produk(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $category = Category::create(['category_name' => 'Elektronik']);

        $product = Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Rolex TDR-3000',
            'description' => 'Jam tangan mewah kondisi mulus.',
            'price' => 12000000,
            'condition' => 'Baik',
            'status' => 'tersedia',
        ]);

        $service = app(NegotiationService::class);

        // Buyer creates offer
        $offer = $service->createOffer($buyer, $product, 8000000);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'product_id' => $product->product_id,
            'negotiation_id' => $offer->negotiation_id,
            'role' => 'penjual',
            'notification_type' => 'negosiasi',
            'title' => '💬 Penawaran masuk — '.$product->product_name,
            'offered_price' => 8000000,
            'original_price' => 12000000,
            'is_read' => false,
        ]);

        // Seller accepts offer
        $service->acceptOffer($seller, $offer);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'product_id' => $product->product_id,
            'negotiation_id' => $offer->negotiation_id,
            'role' => 'pembeli',
            'notification_type' => 'negosiasi',
            'title' => '✅ Penawaran diterima — '.$product->product_name,
            'offered_price' => 8000000,
            'original_price' => 12000000,
            'is_read' => false,
        ]);
    }

    private function createUser(string $name = 'User'): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => uniqid('user-').'@seklov.test',
            'password' => bcrypt('password'),
            'campus' => 'UNSIKA',
            'cohort' => '2023',
            'is_verified' => true,
            'role' => 'user',
        ]);
    }
}
