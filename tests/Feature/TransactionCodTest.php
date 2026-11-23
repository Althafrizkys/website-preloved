<?php

namespace Tests\Feature;

use App\Models\CampusMeetingPoint;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_checkout_cod(): void
    {
        $seller = $this->createUser('Penjual');
        $product = $this->createProduct($seller);

        $this->get(route('transactions.create', $product))
            ->assertRedirect(route('login'));
    }

    public function test_pembeli_dapat_melihat_halaman_checkout_cod_sesuai_figma(): void
    {
        $seller = $this->createUser('Penjual Seller', 'UNSIKA');
        $buyer = $this->createUser('Pembeli Buyer', 'UNSIKA');
        $product = $this->createProduct($seller, [
            'product_name' => 'Rolex Submariner Watch',
            'price' => 49500,
            'stock' => 2,
        ]);

        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama UNSIKA (Area Pos Satpam)',
            'address_description' => 'Depan pos satpam gerbang utama',
            'is_active' => true,
        ]);

        $response = $this->actingAs($buyer)->get(route('transactions.create', [
            'product' => $product,
            'quantity' => 2,
        ]));

        $response->assertOk()
            ->assertSee('1. Pilih Lokasi COD Kampus 📍')
            ->assertSee('UNSIKA')
            ->assertSee('UBP')
            ->assertSee('Titik Temu Spesifik')
            ->assertSee('Gerbang Utama UNSIKA (Area Pos Satpam)')
            ->assertSee('Pesan / Catatan untuk Penjual')
            ->assertSee('Contoh: Ketemu jam 13.00 WIB setelah kelas...')
            ->assertSee('Produk Dipesan')
            ->assertSee('Rolex Submariner Watch')
            ->assertSee('data-stock-max="2"', false)
            ->assertSee('max="2"', false)
            ->assertSee('Rp49.500')
            ->assertSee('Rp99.000')
            ->assertSee('2. Metode Pembayaran 💳')
            ->assertSee('COD')
            ->assertSee('Cash On Delivery')
            ->assertSee('Ringkasan Pembayaran')
            ->assertSee('Subtotal Produk')
            ->assertSee('data-stock-max="2"', false)
            ->assertSee('max="2"', false)
            ->assertDontSee('Biaya Layanan')
            ->assertDontSee('Rp2.000')
            ->assertDontSee('Dana ditahan sistem Seklov')
            ->assertSee('Total Bayar')
            ->assertSee('Rp99.000')
            ->assertSee('Bayar &amp; Buat Pesanan', false);

        $this->get(route('transactions.create', [
            'product' => $product,
            'quantity' => 3,
        ]))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error', 'Jumlah pembelian melebihi stok yang tersedia.');
    }

    public function test_halaman_detail_membatasi_jumlah_dan_mengirimkan_stok_ke_checkout(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['stock' => 4]);

        $this->actingAs($buyer)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Stok tersedia: 4 unit')
            ->assertSee('max="4"', false)
            ->assertSee(route('transactions.create', ['product' => $product, 'quantity' => 1]), false);
    }

    public function test_penjual_tidak_bisa_membeli_barangnya_sendiri(): void
    {
        $seller = $this->createUser('Penjual');
        $product = $this->createProduct($seller);

        $this->actingAs($seller)
            ->get(route('transactions.create', $product))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error');
    }

    public function test_pembeli_berhasil_membuat_pesanan_cod(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, [
            'product_name' => 'Rolex Submariner Watch',
            'price' => 49500,
            'stock' => 2,
        ]);

        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama UNSIKA (Area Pos Satpam)',
            'address_description' => 'Pos satpam',
            'is_active' => true,
        ]);

        $response = $this->actingAs($buyer)->post(route('transactions.store'), [
            'product_id' => $product->product_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'quantity' => 2,
            'buyer_notes' => 'Ketemu jam 13.00 WIB setelah kelas di pos satpam.',
        ]);

        $transaction = Transaction::first();
        $this->assertNotNull($transaction);
        $response->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame((int) $buyer->user_id, (int) $transaction->buyer_id);
        $this->assertSame((int) $seller->user_id, (int) $transaction->seller_id);
        $this->assertSame('menunggu', $transaction->transaction_status);
        $this->assertSame('cod', $transaction->payment_method);
        $this->assertEquals(99000, (int) $transaction->subtotal_price);
        $this->assertEquals(0, (int) $transaction->service_fee);
        $this->assertEquals(99000, (int) $transaction->total_price);
        $this->assertSame('Ketemu jam 13.00 WIB setelah kelas di pos satpam.', $transaction->buyer_notes);

        // Stok habis setelah semua unit dipesan.
        $this->assertSame('terjual', $product->fresh()->status);
        $this->assertSame(0, $product->fresh()->stock);

        // Ada 1 transaction item
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->transaction_id,
            'product_id' => $product->product_id,
            'quantity' => 2,
            'agreed_price' => 49500,
            'subtotal' => 99000,
        ]);

        // Notifikasi dibuat untuk penjual dan pembeli
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'role' => 'penjual',
            'notification_type' => 'transaksi',
            'title' => 'Pembelian masuk — '.$product->product_name,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'role' => 'pembeli',
            'notification_type' => 'transaksi',
            'title' => 'Pesanan berhasil dibuat — '.$product->product_name,
        ]);
    }

    public function test_pesanan_melebihi_stok_ditolak_dan_stok_hanya_berkurang_saat_pesanan_valid(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['stock' => 3]);
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Kantin Fasilkom',
            'address_description' => 'Area kantin',
            'is_active' => true,
        ]);

        $this->actingAs($buyer)
            ->from(route('transactions.create', $product))
            ->post(route('transactions.store'), [
                'product_id' => $product->product_id,
                'meeting_point_id' => $meetingPoint->meeting_point_id,
                'quantity' => 4,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(3, $product->fresh()->stock);

        $this->post(route('transactions.store'), [
            'product_id' => $product->product_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame('tersedia', $product->fresh()->status);
    }

    public function test_checkout_memperhitungkan_harga_nego_yang_disepakati(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, [
            'price' => 100000,
        ]);

        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Kantin Fasilkom',
            'address_description' => 'Area kantin',
            'is_active' => true,
        ]);

        // Buat percakapan dan tawaran yang diterima (Rp 80.000)
        $conversation = Conversation::create([
            'product_id' => $product->product_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
        ]);

        $negotiation = Negotiation::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
            'offered_price' => 80000,
            'quantity' => 1,
            'status' => 'diterima',
        ]);

        $this->actingAs($buyer)->post(route('transactions.store'), [
            'product_id' => $product->product_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'quantity' => 1,
            'negotiation_id' => $negotiation->negotiation_id,
        ]);

        $transaction = Transaction::first();
        $this->assertEquals(80000, (int) $transaction->subtotal_price);
        $this->assertEquals(80000, (int) $transaction->total_price);
    }

    public function test_checkout_menolak_tawaran_nego_milik_barang_lain(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['stock' => 2]);
        $otherProduct = $this->createProduct($seller, ['stock' => 2]);
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Kantin Fasilkom',
            'address_description' => 'Area kantin',
            'is_active' => true,
        ]);
        $conversation = Conversation::create([
            'product_id' => $otherProduct->product_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
        ]);
        $negotiation = Negotiation::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
            'offered_price' => 40000,
            'quantity' => 1,
            'status' => 'diterima',
        ]);

        $this->actingAs($buyer)
            ->post(route('transactions.store'), [
                'product_id' => $product->product_id,
                'meeting_point_id' => $meetingPoint->meeting_point_id,
                'quantity' => 1,
                'negotiation_id' => $negotiation->negotiation_id,
            ])
            ->assertSessionHasErrors('negotiation_id');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_penjual_dapat_mengonfirmasi_jadwal_cod(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama',
            'address_description' => 'Gerbang',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 50000,
            'service_fee' => 0,
            'total_price' => 50000,
            'payment_method' => 'cod',
            'transaction_status' => 'menunggu',
        ]);

        $this->actingAs($seller)
            ->post(route('transactions.confirm', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame('dijadwalkan', $transaction->fresh()->transaction_status);
    }

    public function test_penjual_atau_pembeli_dapat_menyelesaikan_transaksi_cod(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama',
            'address_description' => 'Gerbang',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 50000,
            'service_fee' => 0,
            'total_price' => 50000,
            'payment_method' => 'cod',
            'transaction_status' => 'dijadwalkan',
        ]);

        $this->actingAs($seller)
            ->post(route('transactions.complete', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame('selesai', $transaction->fresh()->transaction_status);
        $this->assertNotNull($transaction->fresh()->completed_at);
    }

    public function test_pesanan_dapat_dibatalkan_dan_produk_kembali_tersedia(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller, ['status' => 'terjual', 'stock' => 0]);
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama',
            'address_description' => 'Gerbang',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 50000,
            'service_fee' => 0,
            'total_price' => 50000,
            'payment_method' => 'cod',
            'transaction_status' => 'menunggu',
        ]);

        $transaction->items()->create([
            'product_id' => $product->product_id,
            'quantity' => 1,
            'agreed_price' => 50000,
            'subtotal' => 50000,
        ]);

        $this->actingAs($buyer)
            ->post(route('transactions.cancel', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame('dibatalkan', $transaction->fresh()->transaction_status);
        $this->assertSame('tersedia', $product->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'product_id' => $product->product_id,
            'role' => 'penjual',
            'notification_type' => 'transaksi',
            'title' => 'Pesanan dibatalkan — '.$product->product_name,
        ]);

        $this->actingAs($seller)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Pesanan dibatalkan — '.$product->product_name);

        $this->post(route('transactions.cancel', $transaction))
            ->assertSessionHasErrors('transaction');
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_daftar_transaksi_menampilkan_pembelian_dan_penjualan(): void
    {
        $seller = $this->createUser('Penjual');
        $buyer = $this->createUser('Pembeli');
        $product = $this->createProduct($seller);
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama',
            'address_description' => 'Gerbang',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 50000,
            'service_fee' => 0,
            'total_price' => 50000,
            'payment_method' => 'cod',
            'transaction_status' => 'menunggu',
        ]);

        $transaction->items()->create([
            'product_id' => $product->product_id,
            'quantity' => 1,
            'agreed_price' => 50000,
            'subtotal' => 50000,
        ]);

        // Buyer sees purchase
        $this->actingAs($buyer)->get(route('transactions.index', ['tab' => 'pembelian']))
            ->assertOk()
            ->assertSee('Daftar Transaksi COD')
            ->assertSee('Pembelian Saya')
            ->assertSee('Pesanan #'.$transaction->transaction_id)
            ->assertSee('Hubungi Penjual')
            ->assertSee('mailto:'.$seller->email, false);

        // Seller sees sale
        $this->actingAs($seller)->get(route('transactions.index', ['tab' => 'penjualan']))
            ->assertOk()
            ->assertSee('Penjualan Saya')
            ->assertSee('Pesanan #'.$transaction->transaction_id)
            ->assertSee('Hubungi Pembeli')
            ->assertSee('mailto:'.$buyer->email, false);
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
            'condition' => 'Baik',
            'stock' => 5,
            'status' => 'tersedia',
        ], $attributes));
    }
}
