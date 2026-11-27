<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dapat_membuka_beranda_dan_tombol_jual_mengarah_ke_register(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('Seklov')
            ->assertSee('Login')
            ->assertSee('Register')
            ->assertSee('Dari Mahasiswa Untuk Mahasiswa!')
            ->assertSee('Transaksi aman bisa COD di Kampus.')
            ->assertSee('alt="Dua mahasiswa berbagi buku"', false)
            ->assertSee('alt="Banner wishlist: minta barang yang belum tersedia"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee(asset('images/karakter-mahasiswa.png'))
            ->assertSee(asset('images/banner-wishlist.jpeg'))
            ->assertDontSee('Marketplace mahasiswa Karawang')
            ->assertDontSee('Temukan barang preloved kampus dan jual barangmu')
            ->assertDontSee('Keranjang segera hadir')
            ->assertDontSee('Daftar Transaksi')
            ->assertDontSee('Pusat Notifikasi')
            ->assertSee('href="'.route('register').'"', false);
    }

    public function test_tautan_banner_wishlist_tidak_mencakup_section_barang_terbaru(): void
    {
        $this->withoutVite();
        $html = $this->get('/')->assertOk()->getContent();
        $wishlistUrl = preg_quote(route('wishlist.index'), '~');

        $this->assertMatchesRegularExpression(
            '~<a href="'.$wishlistUrl.'"[^>]*>\s*<img\b(?=[^>]*alt="Banner wishlist: minta barang yang belum tersedia")[^>]*>\s*</a>\s*</div>\s*<section class="mt-7 sm:mt-9">~s',
            $html,
        );
    }

    public function test_barang_tersedia_ditampilkan_dengan_foto_pertama_harga_dan_paginasi(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);

        $products = collect(range(1, 13))->map(function (int $number) use ($seller, $category) {
            $product = $this->createProduct($seller, $category, [
                'product_name' => 'Barang '.$number,
                'price' => 150000,
            ]);
            $product->forceFill(['created_at' => now()->addSeconds($number)])->saveQuietly();

            return $product;
        });

        ProductMedia::create([
            'product_id' => $products->last()->product_id,
            'media_type' => 'foto',
            'media_url' => 'products/'.$products->last()->product_id.'/foto-awal.jpg',
            'file_size' => 1200,
            'display_order' => 1,
        ]);
        ProductMedia::create([
            'product_id' => $products->last()->product_id,
            'media_type' => 'foto',
            'media_url' => 'products/'.$products->last()->product_id.'/foto-kedua.jpg',
            'file_size' => 1400,
            'display_order' => 2,
        ]);
        $this->createProduct($seller, $category, [
            'product_name' => 'Barang terjual',
            'status' => 'terjual',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Barang 13')
            ->assertSee('Rp 150.000')
            ->assertSee('data-product-category="Fashion"', false)
            ->assertSee('UNSIKA')
            ->assertSee('/storage/products/'.$products->last()->product_id.'/foto-awal.jpg')
            ->assertDontSee('foto-kedua.jpg')
            ->assertDontSee('Barang terjual')
            ->assertDontSee('>Barang 1<', false)
            ->assertSee('page=2');

        $this->assertSame(13, Product::query()->where('status', 'tersedia')->count());
    }

    public function test_pencarian_nama_dan_filter_kategori_dapat_digunakan_bersamaan(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $fashion = Category::create(['category_name' => 'Fashion']);
        $electronics = Category::create(['category_name' => 'Elektronik']);
        $this->createProduct($seller, $fashion, ['product_name' => 'Jaket Kampus']);
        $this->createProduct($seller, $electronics, ['product_name' => 'Jaket Elektronik']);
        $this->createProduct($seller, $fashion, ['product_name' => 'Sepatu Kampus']);

        $this->get('/?q=Jaket&kategori='.$fashion->category_id)
            ->assertOk()
            ->assertSee('Jaket Kampus')
            ->assertDontSee('Jaket Elektronik')
            ->assertDontSee('Sepatu Kampus');
    }

    public function test_filter_kondisi_menampilkan_barang_dengan_kondisi_terpilih(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Baru', 'condition' => 'Baru']);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Bekas', 'condition' => 'Bekas']);

        $this->get('/?kondisi=Baru')
            ->assertOk()
            ->assertSee('Barang Baru')
            ->assertDontSee('Barang Bekas');
    }

    public function test_filter_nego_memisahkan_barang_bisa_nego_dan_harga_pas(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Nego', 'is_negotiable' => true]);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Pas', 'is_negotiable' => false]);

        $this->get('/?nego=ya')
            ->assertOk()
            ->assertSee('Barang Nego')
            ->assertDontSee('Barang Pas');

        $this->get('/?nego=tidak')
            ->assertOk()
            ->assertSee('Barang Pas')
            ->assertDontSee('Barang Nego');
    }

    public function test_filter_rentang_harga_memakai_batas_minimum_dan_maksimum(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Murah', 'price' => 20000]);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Sedang', 'price' => 100000]);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Mahal', 'price' => 500000]);

        $this->get('/?harga_min=50000&harga_max=200000')
            ->assertOk()
            ->assertSee('Barang Sedang')
            ->assertDontSee('Barang Murah')
            ->assertDontSee('Barang Mahal');

        $this->get('/?harga_min=200000')
            ->assertOk()
            ->assertSee('Barang Mahal')
            ->assertDontSee('Barang Sedang');

        $this->get('/?harga_max=50000')
            ->assertOk()
            ->assertSee('Barang Murah')
            ->assertDontSee('Barang Sedang');
    }

    public function test_semua_filter_dapat_digabung_dengan_pencarian(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $fashion = Category::create(['category_name' => 'Fashion']);
        $gadget = Category::create(['category_name' => 'Gadget']);
        $this->createProduct($seller, $fashion, [
            'product_name' => 'Jaket Cocok', 'condition' => 'Seperti Baru', 'is_negotiable' => true, 'price' => 90000,
        ]);
        $this->createProduct($seller, $fashion, [
            'product_name' => 'Jaket Mahal', 'condition' => 'Seperti Baru', 'is_negotiable' => true, 'price' => 900000,
        ]);
        $this->createProduct($seller, $gadget, [
            'product_name' => 'Jaket Gadget', 'condition' => 'Seperti Baru', 'is_negotiable' => true, 'price' => 90000,
        ]);

        $this->get('/?q=Jaket&kategori='.$fashion->category_id.'&kondisi=Seperti+Baru&nego=ya&harga_min=50000&harga_max=200000')
            ->assertOk()
            ->assertSee('Jaket Cocok')
            ->assertDontSee('Jaket Mahal')
            ->assertDontSee('Jaket Gadget');
    }

    public function test_nilai_filter_tidak_valid_diabaikan(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $this->createProduct($seller, $category, ['product_name' => 'Barang Aman']);

        $this->get('/?kondisi=Rusak&nego=mungkin&harga_min=abc&harga_max=-5&kategori=xyz')
            ->assertOk()
            ->assertSee('Barang Aman');
    }

    public function test_search_ada_di_navbar_membawa_filter_dan_chip_kategori_dihapus(): void
    {
        $this->withoutVite();
        $category = Category::create(['category_name' => 'Fashion']);

        $this->get('/?kondisi=Bekas&nego=ya')
            ->assertOk()
            ->assertSee('role="search"', false)
            ->assertSee('<input type="hidden" name="kondisi" value="Bekas">', false)
            ->assertSee('<input type="hidden" name="nego" value="ya">', false)
            ->assertSee('Reset filter')
            ->assertSee('+ Mulai Jual')
            ->assertDontSee('Kategori barang')
            ->assertDontSee('Cari barang');
    }

    public function test_halaman_selain_beranda_tidak_menampilkan_search_di_navbar(): void
    {
        $this->withoutVite();
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $product = $this->createProduct($seller, $category);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('role="search"', false);
    }

    public function test_user_login_melihat_nama_logout_dan_tombol_jual_menuju_route_upload(): void
    {
        $this->withoutVite();
        $user = $this->createUser();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('Logout')
            ->assertSee('Keranjang segera hadir')
            ->assertSee('Daftar Transaksi')
            ->assertSee('Pusat Notifikasi')
            ->assertSee('href="'.route('products.create').'"', false);
    }

    public function test_admin_melihat_tautan_dashboard_dan_dapat_logout(): void
    {
        $this->withoutVite();
        $admin = $this->createUser(['role' => 'admin']);

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('href="'.route('admin.dashboard').'"', false);

        $this->actingAs($admin)->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_halaman_detail_barang_masih_mengembalikan_404_sampai_fase_upload(): void
    {
        $this->get('/barang/123')->assertNotFound();
    }

    private function createUser(array $attributes = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'Penjual Contoh',
            'email' => uniqid('penjual-').'@example.test',
            'password' => 'password',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'is_verified' => true,
            'role' => 'user',
        ], $attributes));
    }

    private function createProduct(User $seller, Category $category, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Produk Contoh',
            'description' => 'Deskripsi contoh barang preloved yang masih layak digunakan.',
            'price' => 100000,
            'condition' => 'Baik',
            'status' => 'tersedia',
        ], $attributes));
    }
}
