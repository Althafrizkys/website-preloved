<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegotiationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_pembeli_membuka_popup_dan_mengirim_tawaran_ke_penjual(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->get(route('negotiations.show', $product))
            ->assertRedirect(route('products.show', ['product' => $product, 'nego' => 'open']));

        $this->get(route('products.show', ['product' => $product, 'nego' => 'open']))
            ->assertOk()
            ->assertSee('data-nego-open', false)
            ->assertSee('data-nego-auto-open="true"', false);

        $this->get(route('negotiations.show', $product), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Nego Harga Produk')
            ->assertSee('Harga Awal:')
            ->assertSee('data-offer-discount="10"', false);

        $response = $this->post(route('negotiations.store', $product), [
            'offered_price' => '120.000',
        ]);

        $response->assertRedirect(route('products.show', ['product' => $product, 'nego' => 'open']));
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('negotiations', 1);
        $this->assertDatabaseHas('negotiations', [
            'conversation_id' => Conversation::query()->value('conversation_id'),
            'sender_id' => $buyer->user_id,
            'parent_offer_id' => null,
            'offered_price' => 120000,
            'quantity' => 1,
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'notification_type' => 'negosiasi',
            'title' => 'Penawaran masuk — '.$product->product_name,
        ]);
    }

    public function test_tawaran_menunggu_mencegah_tawaran_kedua_dan_percakapan_ganda(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);

        $this->sendOffer($buyer, $product, 120000);
        $this->actingAs($buyer)
            ->from(route('products.show', $product))
            ->post(route('negotiations.store', $product), ['offered_price' => 125000])
            ->assertSessionHasErrors('offered_price');

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('negotiations', 1);
    }

    public function test_pembeli_dapat_menawar_lagi_setelah_ditolak_hanya_dengan_harga_berbeda(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $firstOffer = $this->sendOffer($buyer, $product, 120000);

        $this->actingAs($seller)
            ->post(route('negotiations.reject', $firstOffer))
            ->assertRedirect(route('products.edit', $product));

        $this->assertNotNull($firstOffer->fresh()->responded_at);
        $this->assertSame('ditolak', $firstOffer->fresh()->status);

        $this->actingAs($buyer)
            ->post(route('negotiations.store', $product), ['offered_price' => 120000])
            ->assertSessionHasErrors('offered_price');

        $this->sendOffer($buyer, $product, 125000);

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('negotiations', 2);
        $this->assertSame(
            [120000, 125000],
            Negotiation::query()->orderBy('negotiation_id')->pluck('offered_price')->map(fn ($price) => (int) $price)->all(),
        );
        $this->assertSame(3, UserNotification::query()->count());
    }

    public function test_penjual_dapat_menerima_tawaran_dan_harga_kesepakatan_tersedia(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $offer = $this->sendOffer($buyer, $product, 120000);

        $this->actingAs($seller)
            ->post(route('negotiations.accept', $offer))
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHas('success', 'Tawaran berhasil diterima.');

        $acceptedOffer = $offer->fresh();
        $this->assertSame('diterima', $acceptedOffer->status);
        $this->assertNotNull($acceptedOffer->responded_at);
        $this->assertSame($acceptedOffer->negotiation_id, $acceptedOffer->conversation->agreedOffer()->negotiation_id);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'notification_type' => 'negosiasi',
            'title' => 'Penawaran diterima — '.$product->product_name,
        ]);

        $this->actingAs($buyer)
            ->post(route('negotiations.store', $product), ['offered_price' => 125000])
            ->assertSessionHasErrors('offered_price');
        $this->assertDatabaseCount('negotiations', 1);
    }

    public function test_tawaran_dengan_harga_di_luar_batas_ditolak(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->post(route('negotiations.store', $product), ['offered_price' => 44000])
            ->assertSessionHasErrors('offered_price');
        $this->post(route('negotiations.store', $product), ['offered_price' => 150001])
            ->assertSessionHasErrors('offered_price')
            ->assertRedirect(route('products.show', ['product' => $product, 'nego' => 'open']));

        $this->get(route('products.show', ['product' => $product, 'nego' => 'open']))->assertOk();
        $this->get(route('negotiations.show', $product), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Tawaran tidak boleh lebih besar dari harga barang.');

        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('negotiations', 0);
    }

    public function test_penjual_tidak_bisa_menawar_pembeli_tidak_bisa_menanggapi_dan_tidak_ada_tawaran_balik(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $thirdParty = $this->createUser('Pihak Ketiga');
        $product = $this->createProduct($seller);

        $this->actingAs($seller)
            ->post(route('negotiations.store', $product), ['offered_price' => 120000])
            ->assertForbidden();

        $offer = $this->sendOffer($buyer, $product, 120000);

        $this->actingAs($buyer)
            ->post(route('negotiations.accept', $offer))
            ->assertForbidden();
        $this->post(route('negotiations.reject', $offer))
            ->assertForbidden();

        $this->actingAs($thirdParty)
            ->post(route('negotiations.accept', $offer))
            ->assertForbidden();
        $this->post('/nego/'.$offer->negotiation_id.'/balas')
            ->assertNotFound();
    }

    public function test_pembeli_tidak_bisa_menawar_barang_sendiri_dan_pembeli_lain_tidak_melihat_riwayat(): void
    {
        $seller = $this->createUser('Penjual');
        $firstBuyer = $this->createUser('Pembeli Satu');
        $otherBuyer = $this->createUser('Pembeli Dua');
        $product = $this->createProduct($seller);
        $offer = $this->sendOffer($firstBuyer, $product, 120000);

        $this->actingAs($seller)
            ->post(route('negotiations.store', $product), ['offered_price' => 125000])
            ->assertForbidden();

        $this->actingAs($otherBuyer)
            ->get(route('negotiations.show', $product), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee($firstBuyer->name)
            ->assertDontSee((string) $offer->offered_price);
    }

    public function test_barang_terjual_menutup_form_dan_tawaran_menunggu_tidak_bisa_diterima(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $offer = $this->sendOffer($buyer, $product, 120000);
        $product->update(['status' => 'terjual']);

        $this->actingAs($buyer)
            ->get(route('negotiations.show', $product), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Barang sudah terjual')
            ->assertDontSee('Kirim Penawaran Nego');

        $this->actingAs($seller)
            ->post(route('negotiations.accept', $offer))
            ->assertForbidden();

        $this->assertSame('menunggu', $offer->fresh()->status);
    }

    public function test_penjual_melihat_tawaran_masuk_di_halaman_kelola_barang(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $this->sendOffer($buyer, $product, 120000);

        $this->actingAs($seller)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Tawaran masuk')
            ->assertSee('Pembeli')
            ->assertSee('Rp120.000')
            ->assertSee('Terima')
            ->assertSee('Tolak');
    }

    private function sendOffer(User $buyer, Product $product, int $price): Negotiation
    {
        $this->actingAs($buyer)
            ->post(route('negotiations.store', $product), ['offered_price' => $price])
            ->assertRedirect(route('products.show', ['product' => $product, 'nego' => 'open']));

        return Negotiation::query()->latest('negotiation_id')->firstOrFail();
    }

    private function createUser(string $name): User
    {
        static $counter = 0;
        $counter++;

        return User::forceCreate([
            'name' => $name,
            'email' => 'user-'.$counter.'-'.uniqid().'@example.test',
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
