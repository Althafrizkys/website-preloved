<?php

namespace Tests\Feature;

use App\Models\CampusMeetingPoint;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('cart.index'))->assertRedirect(route('login'));
        $this->post(route('cart.store'), ['product_id' => 1])->assertRedirect(route('login'));
    }

    public function test_tombol_keranjang_di_halaman_produk_dan_navbar_aktif(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-product-cart-form', false)
            ->assertSee(route('cart.store'), false)
            ->assertSee(route('cart.index'), false)
            ->assertDontSee('Keranjang segera hadir');
    }

    public function test_menambah_barang_dan_menambah_jumlah_jika_sudah_ada(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['stock' => 5]);

        $this->actingAs($buyer)->from(route('products.show', $product))
            ->post(route('cart.store'), ['product_id' => $product->product_id, 'quantity' => 2])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('success');

        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->product_id, 'quantity' => 1]);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(3, CartItem::first()->quantity);
    }

    public function test_tidak_bisa_menambah_barang_sendiri_habis_atau_melebihi_stok(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['stock' => 2]);
        $soldOut = $this->createProduct($seller, ['stock' => 0, 'status' => 'terjual']);

        $this->actingAs($seller)->post(route('cart.store'), ['product_id' => $product->product_id])
            ->assertSessionHasErrors('product_id');
        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $soldOut->product_id])
            ->assertSessionHasErrors('product_id');
        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->product_id, 'quantity' => 3])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_halaman_keranjang_dikelompokkan_per_penjual_dan_menampilkan_harga(): void
    {
        $sellerA = $this->createUser('Penjual Alfa');
        $sellerB = $this->createUser('Penjual Beta', 'UBP');
        $buyer = $this->createUser('Pembeli');
        $this->addToCart($buyer, $this->createProduct($sellerA, ['product_name' => 'Jam Tangan', 'price' => 49500]), 2);
        $this->addToCart($buyer, $this->createProduct($sellerB, ['product_name' => 'Sepatu Lari']));

        $this->actingAs($buyer)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Keranjang Belanja')
            ->assertSee('Penjual Alfa')
            ->assertSee('Penjual Beta')
            ->assertSee('Jam Tangan')
            ->assertSee('Rp49.500')
            ->assertSee('Rp99.000')
            ->assertSee('Checkout Sekarang');
    }

    public function test_mengubah_jumlah_dan_menghapus_barang(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $item = $this->addToCart($buyer, $this->createProduct($seller, ['stock' => 3]));

        $this->actingAs($buyer)->patchJson(route('cart.update', $item), ['quantity' => 3])
            ->assertOk()
            ->assertJson(['quantity' => 3, 'stock' => 3]);
        $this->actingAs($buyer)->patchJson(route('cart.update', $item), ['quantity' => 4])
            ->assertUnprocessable();
        $this->assertSame(3, $item->fresh()->quantity);

        $this->actingAs($buyer)->delete(route('cart.destroy', $item))
            ->assertRedirect(route('cart.index'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_menghapus_barang_terpilih_hanya_milik_sendiri(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $other = $this->createUser('Orang Lain');
        $mine = $this->addToCart($buyer, $this->createProduct($seller));
        $theirs = $this->addToCart($other, $this->createProduct($seller));

        $this->actingAs($buyer)->delete(route('cart.destroy-selected'), ['items' => [$mine->cart_item_id, $theirs->cart_item_id]])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['cart_item_id' => $mine->cart_item_id]);
        $this->assertDatabaseHas('cart_items', ['cart_item_id' => $theirs->cart_item_id]);

        $this->actingAs($buyer)->delete(route('cart.destroy', $theirs))->assertForbidden();
        $this->actingAs($buyer)->patch(route('cart.update', $theirs), ['quantity' => 1])->assertForbidden();
    }

    public function test_checkout_dua_barang_dari_satu_penjual_menjadi_satu_transaksi(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $productA = $this->createProduct($seller, ['product_name' => 'Barang A', 'price' => 30000, 'stock' => 3]);
        $productB = $this->createProduct($seller, ['product_name' => 'Barang B', 'price' => 20000, 'stock' => 1]);
        $itemA = $this->addToCart($buyer, $productA, 2);
        $itemB = $this->addToCart($buyer, $productB);
        $meetingPoint = $this->createMeetingPoint();

        $this->actingAs($buyer)->get(route('cart.checkout', ['items' => [$itemA->cart_item_id, $itemB->cart_item_id]]))
            ->assertOk()
            ->assertSee('Barang A')
            ->assertSee('Barang B')
            ->assertSee('Rp80.000')
            ->assertSee(route('cart.place-order'), false);

        $response = $this->actingAs($buyer)->post(route('cart.place-order'), [
            'items' => [$itemA->cart_item_id, $itemB->cart_item_id],
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'buyer_notes' => 'Setelah kelas',
        ]);

        $transaction = Transaction::with('items')->firstOrFail();
        $response->assertRedirect(route('transactions.show', $transaction));

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame((int) $seller->user_id, (int) $transaction->seller_id);
        $this->assertSame(80000, (int) $transaction->total_price);
        $this->assertCount(2, $transaction->items);
        $this->assertSame(1, $productA->fresh()->stock);
        $this->assertSame('terjual', $productB->fresh()->status);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame(1, UserNotification::where('user_id', $seller->user_id)->where('notification_type', 'transaksi')->count());
    }

    public function test_checkout_barang_dari_dua_penjual_ditolak(): void
    {
        $sellerA = $this->createUser('Penjual A');
        $sellerB = $this->createUser('Penjual B');
        $buyer = $this->createUser('Pembeli');
        $itemA = $this->addToCart($buyer, $this->createProduct($sellerA));
        $itemB = $this->addToCart($buyer, $this->createProduct($sellerB));
        $meetingPoint = $this->createMeetingPoint();
        $ids = [$itemA->cart_item_id, $itemB->cart_item_id];

        $this->actingAs($buyer)->get(route('cart.checkout', ['items' => $ids]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->actingAs($buyer)->post(route('cart.place-order'), [
            'items' => $ids,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_checkout_ditolak_jika_stok_berubah_atau_barang_milik_orang_lain(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $other = $this->createUser('Orang Lain');
        $product = $this->createProduct($seller, ['stock' => 3]);
        $item = $this->addToCart($buyer, $product, 3);
        $othersItem = $this->addToCart($other, $this->createProduct($seller));
        $meetingPoint = $this->createMeetingPoint();

        $product->update(['stock' => 1]);

        $this->actingAs($buyer)->post(route('cart.place-order'), [
            'items' => [$item->cart_item_id],
            'meeting_point_id' => $meetingPoint->meeting_point_id,
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->actingAs($buyer)->post(route('cart.place-order'), [
            'items' => [$othersItem->cart_item_id],
            'meeting_point_id' => $meetingPoint->meeting_point_id,
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_keranjang_memakai_harga_nego_yang_diterima(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['price' => 100000]);
        $conversation = Conversation::create([
            'product_id' => $product->product_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
        ]);
        Negotiation::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
            'offered_price' => 80000,
            'quantity' => 1,
            'status' => 'diterima',
        ]);
        $item = $this->addToCart($buyer, $product);

        $this->actingAs($buyer)->post(route('cart.place-order'), [
            'items' => [$item->cart_item_id],
            'meeting_point_id' => $this->createMeetingPoint()->meeting_point_id,
        ]);

        $transaction = Transaction::firstOrFail();
        $this->assertSame(80000, (int) $transaction->total_price);
        $this->assertSame(20000, (int) $transaction->agreed_discount);
    }

    private function addToCart(User $user, Product $product, int $quantity = 1): CartItem
    {
        return CartItem::create([
            'user_id' => $user->user_id,
            'product_id' => $product->product_id,
            'quantity' => $quantity,
        ]);
    }

    private function createMeetingPoint(): CampusMeetingPoint
    {
        return CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama',
            'address_description' => 'Pos satpam',
            'is_active' => true,
        ]);
    }

    private function createUser(string $name = 'User', string $campus = 'UNSIKA'): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => uniqid('user-').'@seklov.test',
            'password' => bcrypt('password'),
            'campus' => $campus,
            'cohort' => '2023',
            'is_verified' => true,
            'role' => 'user',
        ]);
    }

    private function createProduct(User $seller, array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['category_name' => 'Aksesoris']);

        return Product::create(array_merge([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Barang Preloved',
            'description' => 'Deskripsi barang preloved siap COD kampus.',
            'price' => 50000,
            'condition' => 'Bekas',
            'stock' => 5,
            'status' => 'tersedia',
        ], $attributes));
    }
}
