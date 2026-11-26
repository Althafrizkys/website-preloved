<?php

namespace Tests\Feature;

use App\Models\CampusMeetingPoint;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TransactionExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tamu_diarahkan_ke_login_saat_mengakses_export_transaksi(): void
    {
        $this->get(route('transactions.export'))
            ->assertRedirect(route('login'));
    }

    public function test_tombol_export_hanya_tampil_pada_tab_penjualan_saya(): void
    {
        $user = $this->createUser('Penjual');

        $this->actingAs($user)
            ->get(route('transactions.index', ['tab' => 'pembelian']))
            ->assertOk()
            ->assertDontSee('Export Excel')
            ->assertDontSee(route('transactions.export'), false);

        $this->actingAs($user)
            ->get(route('transactions.index', ['tab' => 'penjualan']))
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Dari Tanggal')
            ->assertSee('Sampai Tanggal')
            ->assertSee(route('transactions.export'), false);
    }

    public function test_penjual_dapat_mengekspor_riwayat_penjualan_ke_excel_sesuai_data_miliknya(): void
    {
        $seller = $this->createUser('Fathan Seller', 'UNSIKA');
        $buyer = $this->createUser('Budi Pembeli', 'UBP');
        $otherSeller = $this->createUser('Penjual Lain', 'UNSIKA');

        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Gerbang Utama UNSIKA',
            'address_description' => 'Pos satpam',
            'is_active' => true,
        ]);

        $product1 = $this->createProduct($seller, ['product_name' => 'Jaket Denim Vintage']);
        $product2 = $this->createProduct($otherSeller, ['product_name' => 'Sepatu Kampus']);

        // Penjualan milik $seller
        $sale = Transaction::forceCreate([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 150000,
            'service_fee' => 0,
            'total_price' => 150000,
            'payment_method' => 'cod',
            'transaction_status' => 'selesai',
            'completed_at' => now(),
            'created_at' => '2026-11-20 10:30:00',
        ]);
        $sale->items()->create([
            'product_id' => $product1->product_id,
            'quantity' => 2,
            'agreed_price' => 75000,
            'subtotal' => 150000,
        ]);

        // Transaksi dimana $seller adalah pembeli (bukan penjualan) -> tidak boleh ikut terexport
        $purchase = Transaction::forceCreate([
            'buyer_id' => $seller->user_id,
            'seller_id' => $otherSeller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 80000,
            'service_fee' => 0,
            'total_price' => 80000,
            'payment_method' => 'cod',
            'transaction_status' => 'menunggu',
            'created_at' => '2026-11-21 12:00:00',
        ]);
        $purchase->items()->create([
            'product_id' => $product2->product_id,
            'quantity' => 1,
            'agreed_price' => 80000,
            'subtotal' => 80000,
        ]);

        $response = $this->actingAs($seller)->get(route('transactions.export'));

        $response->assertOk();
        $contentDisposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.xlsx', $contentDisposition);

        $file = $response->getFile();
        $spreadsheet = IOFactory::load($file->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame([
            'No. Pesanan',
            'Tanggal Transaksi',
            'Nama Barang',
            'Jumlah',
            'Pembeli',
            'Kampus Pembeli',
            'Lokasi COD',
            'Metode Pembayaran',
            'Total Harga',
            'Status',
            'Tanggal Selesai',
        ], $rows[0]);

        $this->assertSame('#'.$sale->transaction_id, $rows[1][0]);
        $this->assertSame('20-11-2026 10:30', $rows[1][1]);
        $this->assertSame('Jaket Denim Vintage', $rows[1][2]);
        $this->assertEquals(2, $rows[1][3]);
        $this->assertSame('Budi Pembeli', $rows[1][4]);
        $this->assertSame('UBP', $rows[1][5]);
        $this->assertSame('UNSIKA - Gerbang Utama UNSIKA', $rows[1][6]);
        $this->assertSame('COD', $rows[1][7]);
        $this->assertEquals(150000, $rows[1][8]);
        $this->assertSame('Selesai', $rows[1][9]);
    }

    public function test_filter_rentang_tanggal_memfilter_daftar_dan_hasil_export_excel(): void
    {
        $seller = $this->createUser('Fathan Seller', 'UNSIKA');
        $buyer = $this->createUser('Budi Pembeli', 'UBP');
        $meetingPoint = CampusMeetingPoint::create([
            'campus' => 'UNSIKA',
            'point_name' => 'Kantin Fasilkom',
            'address_description' => 'Area kantin',
            'is_active' => true,
        ]);

        $oldProduct = $this->createProduct($seller, ['product_name' => 'Buku Lama']);
        $recentProduct = $this->createProduct($seller, ['product_name' => 'Tas Baru']);

        $oldSale = Transaction::forceCreate([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 30000,
            'service_fee' => 0,
            'total_price' => 30000,
            'payment_method' => 'cod',
            'transaction_status' => 'selesai',
            'created_at' => '2026-11-01 09:00:00',
        ]);
        $oldSale->items()->create([
            'product_id' => $oldProduct->product_id,
            'quantity' => 1,
            'agreed_price' => 30000,
            'subtotal' => 30000,
        ]);

        $recentSale = Transaction::forceCreate([
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'meeting_point_id' => $meetingPoint->meeting_point_id,
            'subtotal_price' => 120000,
            'service_fee' => 0,
            'total_price' => 120000,
            'payment_method' => 'cod',
            'transaction_status' => 'dijadwalkan',
            'created_at' => '2026-11-25 18:45:00',
        ]);
        $recentSale->items()->create([
            'product_id' => $recentProduct->product_id,
            'quantity' => 1,
            'agreed_price' => 120000,
            'subtotal' => 120000,
        ]);

        // Halaman daftar transaksi dengan filter tanggal
        $this->actingAs($seller)
            ->get(route('transactions.index', [
                'tab' => 'penjualan',
                'start' => '2026-11-20',
                'end' => '2026-11-25',
            ]))
            ->assertOk()
            ->assertSee('Tas Baru')
            ->assertDontSee('Buku Lama')
            ->assertSee(route('transactions.export', ['start' => '2026-11-20', 'end' => '2026-11-25']));

        // Export Excel dengan filter yang sama
        $response = $this->actingAs($seller)->get(route('transactions.export', [
            'start' => '2026-11-20',
            'end' => '2026-11-25',
        ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'riwayat-penjualan-cod-20261120-20261125.xlsx',
            (string) $response->headers->get('content-disposition'),
        );

        $spreadsheet = IOFactory::load($response->getFile()->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('#'.$recentSale->transaction_id, $rows[1][0]);
        $this->assertSame('Tas Baru', $rows[1][2]);
    }

    public function test_rentang_tanggal_tidak_valid_ditolak(): void
    {
        $seller = $this->createUser('Fathan Seller');

        $this->actingAs($seller)
            ->from(route('transactions.index', ['tab' => 'penjualan']))
            ->get(route('transactions.index', [
                'tab' => 'penjualan',
                'start' => '2026-11-25',
                'end' => '2026-11-20',
            ]))
            ->assertSessionHasErrors('end');
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
