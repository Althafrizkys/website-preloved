<?php

namespace Tests\Feature;

use App\Models\CampusMeetingPoint;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WishlistRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistDealFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_pengguna_dapat_menghubungi_pemilik_wishlist_dan_percakapan_tidak_duplikat(): void
    {
        $owner = $this->createUser('Pemilik Wishlist');
        $seller = $this->createUser('Calon Penjual');
        $wishlist = $this->createWishlist($owner);

        $this->actingAs($owner)
            ->post(route('chat.wishlist.start', $wishlist))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee('Chat pemilik wishlist');

        $this->post(route('chat.wishlist.start', $wishlist))
            ->assertRedirect();

        $conversation = Conversation::query()->firstOrFail();
        $this->assertSame($wishlist->request_id, $conversation->wishlist_request_id);
        $this->assertNull($conversation->product_id);
        $this->assertSame($owner->user_id, $conversation->buyer_id);
        $this->assertSame($seller->user_id, $conversation->seller_id);

        $this->post(route('chat.wishlist.start', $wishlist))
            ->assertRedirect(route('chat.show', $conversation));
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_pemilik_wishlist_menyetujui_penawaran_dan_checkout_menggunakan_snapshot_harga_deal(): void
    {
        $owner = $this->createUser('Pemilik Wishlist');
        $seller = $this->createUser('Calon Penjual');
        $wishlist = $this->createWishlist($owner);
        $product = $this->createProduct($seller, ['stock' => 1]);
        $conversation = $this->startConversation($seller, $wishlist);

        $this->actingAs($seller)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('Permintaan wishlist')
            ->assertSee('Tawarkan barang untuk wishlist')
            ->assertSee('value="Jaket Kampus"', false)
            ->assertDontSee('name="product_id"', false)
            ->assertDontSee(route('negotiations.accept', 1), false);

        $this->post(route('wishlist-deals.store', $conversation), [
            'offered_price' => 120000,
        ])->assertRedirect(route('chat.show', $conversation));

        $offer = Negotiation::query()->firstOrFail();
        $this->assertSame('menunggu', $offer->status);
        $this->assertNull($offer->product_id);
        $this->assertSame($wishlist->item_name, $offer->offered_item_name);
        $this->assertSame($seller->user_id, $offer->sender_id);

        $this->actingAs($owner)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('Penawaran barang wishlist')
            ->assertSee('Rp120.000')
            ->assertSee(route('wishlist-deals.accept', $offer), false)
            ->assertSee(route('wishlist-deals.reject', $offer), false);

        $this->post(route('wishlist-deals.accept', $offer))
            ->assertRedirect(route('transactions.create-wishlist', $offer));

        $this->actingAs($seller)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('Harga disepakati · Rp120.000')
            ->assertSee('Wishlist: Jaket Kampus · Budget Rp150.000')
            ->assertSee('Budget wishlist: Rp150.000');

        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama',
            'address_description' => 'Gerbang utama kampus',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('transactions.create-wishlist', $offer))
            ->assertOk()
            ->assertSee($wishlist->item_name)
            ->assertSee('Rp120.000')
            ->assertSee('name="negotiation_id" value="'.$offer->negotiation_id.'"', false);

        $this->post(route('transactions.store'), [
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'quantity' => 1,
            'negotiation_id' => $offer->negotiation_id,
        ])->assertRedirect();

        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame($owner->user_id, $transaction->buyer_id);
        $this->assertSame($seller->user_id, $transaction->seller_id);
        $item = $transaction->items()->firstOrFail();
        $this->assertNull($item->product_id);
        $this->assertSame($wishlist->request_id, $item->wishlist_request_id);
        $this->assertSame($offer->negotiation_id, $item->negotiation_id);
        $this->assertSame($wishlist->item_name, $item->item_name_snapshot);
        $this->assertSame(120000, (int) $item->agreed_price);
        $this->assertSame('terpenuhi', $wishlist->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame('tersedia', $product->fresh()->status);

        $this->actingAs($owner)
            ->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertSee($wishlist->item_name)
            ->assertSee('Rp120.000');

        $this->get(route('transactions.index'))
            ->assertOk()
            ->assertSee($wishlist->item_name);

        $this->post(route('transactions.cancel', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame('aktif', $wishlist->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_hanya_pemilik_wishlist_yang_dapat_menanggapi_dan_penawaran_dapat_ditolak(): void
    {
        $owner = $this->createUser('Pemilik Wishlist');
        $seller = $this->createUser('Calon Penjual');
        $outsider = $this->createUser('Orang Luar');
        $wishlist = $this->createWishlist($owner);
        $conversation = $this->startConversation($seller, $wishlist);
        $offer = $this->sendOffer($seller, $conversation);

        $this->actingAs($outsider)
            ->post(route('wishlist-deals.accept', $offer))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('wishlist-deals.reject', $offer))
            ->assertRedirect(route('chat.show', $conversation));

        $this->assertSame('ditolak', $offer->fresh()->status);
    }

    public function test_penjual_harus_memasukkan_harga_penawaran_dan_nama_barang_diambil_dari_wishlist(): void
    {
        $owner = $this->createUser('Pemilik Wishlist');
        $seller = $this->createUser('Calon Penjual');
        $wishlist = $this->createWishlist($owner);
        $conversation = $this->startConversation($seller, $wishlist);

        $this->actingAs($seller)
            ->post(route('wishlist-deals.store', $conversation), [
                'offered_price' => 0,
            ])
            ->assertSessionHasErrors('offered_price');

        $this->assertDatabaseCount('negotiations', 0);
    }

    private function startConversation(User $seller, WishlistRequest $wishlist): Conversation
    {
        $this->actingAs($seller)
            ->post(route('chat.wishlist.start', $wishlist))
            ->assertRedirect();

        return Conversation::query()->firstOrFail();
    }

    private function sendOffer(User $seller, Conversation $conversation): Negotiation
    {
        $this->actingAs($seller)
            ->post(route('wishlist-deals.store', $conversation), [
                'offered_price' => 120000,
            ])
            ->assertRedirect(route('chat.show', $conversation));

        return Negotiation::query()->firstOrFail();
    }

    private function createWishlist(User $owner): WishlistRequest
    {
        $category = Category::firstOrCreate(['category_name' => 'Fashion']);

        return WishlistRequest::create([
            'user_id' => $owner->user_id,
            'category_id' => $category->category_id,
            'item_name' => 'Jaket Kampus',
            'condition' => 'Baik',
            'budget' => 150000,
            'description' => 'Mencari jaket kampus',
            'expired_at' => now()->addDays(7),
            'status' => 'aktif',
        ]);
    }

    private function createProduct(User $seller, array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['category_name' => 'Fashion']);

        return Product::create(array_merge([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Jaket Preloved',
            'description' => 'Jaket masih layak digunakan.',
            'price' => 150000,
            'condition' => 'Baik',
            'stock' => 1,
            'status' => 'tersedia',
        ], $attributes));
    }

    private function createUser(string $name): User
    {
        static $counter = 0;
        $counter++;

        return User::forceCreate([
            'name' => $name,
            'email' => 'wishlist-'.$counter.'-'.uniqid().'@example.test',
            'password' => 'password',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'is_verified' => true,
            'role' => 'user',
        ]);
    }
}
