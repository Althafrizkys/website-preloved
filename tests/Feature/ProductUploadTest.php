<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProductUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_tamu_dialihkan_ke_register_untuk_route_jual_dan_edit(): void
    {
        $product = $this->createProduct($this->createUser());

        $this->get('/jual')->assertRedirect(route('register'));
        $this->post('/jual')->assertRedirect(route('register'));
        $this->get(route('products.edit', $product))->assertRedirect(route('register'));
        $this->put(route('products.update', $product))->assertRedirect(route('register'));
        $this->patch(route('products.status', $product))->assertRedirect(route('register'));
        $this->delete(route('products.destroy', $product))->assertRedirect(route('register'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_upload_valid_menyimpan_produk_dan_media_dengan_seller_dari_session(): void
    {
        $seller = $this->createUser();
        $seller->update(['profile_photo' => 'profiles/seller.png']);
        Storage::disk('public')->put('profiles/seller.png', 'foto profil');
        $otherUser = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);

        $response = $this->actingAs($seller)->post(route('products.store'), array_merge(
            $this->validProductData($category),
            [
                'seller_id' => $otherUser->user_id,
                'status' => 'terjual',
                'photos' => [
                    $this->fakeImage('foto-depan.jpg'),
                    $this->fakeImage('foto-samping.png'),
                ],
                'video' => UploadedFile::fake()->createWithContent('detail.mp4', str_repeat('v', 100 * 1024)),
            ],
        ));

        $product = Product::query()->with('media')->firstOrFail();
        $response->assertRedirect(route('products.show', $product));
        $this->assertSame($seller->user_id, $product->seller_id);
        $this->assertSame('tersedia', $product->status);
        $this->assertSame('1500000.00', $product->price);
        $this->assertSame(3, $product->stock);
        $this->assertFalse($product->is_negotiable);
        $this->assertCount(3, $product->media);
        $this->assertSame(['foto', 'foto', 'video'], $product->media->pluck('media_type')->all());
        $this->assertSame([1, 2, 3], $product->media->pluck('display_order')->all());

        foreach ($product->media as $media) {
            $this->assertSame((int) Storage::disk('public')->size($media->media_url), $media->file_size);
            Storage::disk('public')->assertExists($media->media_url);
            $this->assertStringStartsWith('products/'.$product->product_id.'/', $media->media_url);
            $this->assertStringNotContainsString('foto-depan', $media->media_url);
        }

        $this->actingAs($seller)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Jaket Kampus')
            ->assertSee('Tentang produk')
            ->assertSee('Deskripsi Produk')
            ->assertSee('Beli Sekarang')
            ->assertSee('Chat Sekarang')
            ->assertSee('Stok tersedia: 3 unit')
            ->assertSee('Harga pas')
            ->assertSee('Penjual tidak mengaktifkan negosiasi')
            ->assertDontSee('data-nego-open', false)
            ->assertSee('Lainnya')
            ->assertSee('Angkatan 2024')
            ->assertSee(Storage::disk('public')->url('profiles/seller.png'));
    }

    public function test_gagal_menyimpan_salah_satu_file_membersihkan_file_sebelumnya_dan_rollback_produk(): void
    {
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')
            ->twice()
            ->andReturn('products/1/foto-pertama.jpg', false);
        $disk->shouldReceive('delete')
            ->once()
            ->with(['products/1/foto-pertama.jpg'])
            ->andReturn(true);
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturn($disk);

        try {
            app(ProductService::class)->create(
                $seller,
                array_merge($this->validProductData($category), ['price' => 1500000]),
                [$this->fakeImage('foto-1.jpg'), $this->fakeImage('foto-2.jpg')],
                null,
            );
            $this->fail('Penyimpanan media yang gagal seharusnya menghasilkan exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('File media barang gagal disimpan.', $exception->getMessage());
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_media', 0);
    }

    public function test_halaman_form_upload_dan_edit_dapat_dibuka_pemilik(): void
    {
        $owner = $this->createUser();
        $product = $this->createProduct($owner);

        $this->actingAs($owner)->get('/jual')
            ->assertOk()
            ->assertSee('Tambahkan Detail Produk')
            ->assertSee('Stok barang')
            ->assertSee('Negosiasi harga')
            ->assertSee('data-live-stock', false)
            ->assertSee('data-live-negotiable', false)
            ->assertSee('data-live-size', false)
            ->assertSee('data-live-usage_period', false)
            ->assertSee('data-live-defects', false)
            ->assertSee('data-live-photo-thumbnails', false)
            ->assertSee('Preview', false)
            ->assertSee('data-restore-photos="false"', false);

        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Perbarui Detail Produk')
            ->assertSee('Tandai terjual')
            ->assertSee('Hapus postingan')
            ->assertSee('data-live-photo-thumbnails', false)
            ->assertSee('data-product-form', false);
    }

    public function test_validasi_form_upload_hanya_menandai_field_salah_dan_mempertahankan_isian_lain(): void
    {
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $data = array_merge($this->validProductData($category), [
            'product_name' => '',
            'photos' => [$this->fakeImage('foto.jpg')],
        ]);

        $response = $this->actingAs($seller)
            ->from('/jual')
            ->post('/jual', $data)
            ->assertSessionHasErrors(['product_name'])
            ->assertSessionHasInput('price', '1.500.000')
            ->assertSessionHasInput('description', $data['description'])
            ->assertSessionHasInput('stock', '3');

        $page = $this->get('/jual')
            ->assertOk()
            ->assertSee('id="product_name"', false)
            ->assertSee('data-restore-photos="true"', false)
            ->assertSee('value="1.500.000"', false)
            ->assertSee('value="3"', false)
            ->assertSee('aria-invalid="true"', false);

        $this->assertSame(1, substr_count($page->getContent(), 'aria-invalid="true"'));
    }

    public function test_upload_menolak_foto_lebih_dari_lima_file_terlalu_besar_dan_tipe_salah(): void
    {
        $seller = $this->createUser();
        $category = Category::create(['category_name' => 'Elektronik']);
        $data = $this->validProductData($category);

        $tooManyPhotos = array_map(
            fn (int $number) => $this->fakeImage('foto-'.$number.'.jpg'),
            range(1, 6),
        );
        $this->actingAs($seller)
            ->from('/jual')
            ->post('/jual', array_merge($data, ['photos' => $tooManyPhotos]))
            ->assertSessionHasErrors('photos');

        $this->from('/jual')
            ->post('/jual', array_merge($data, [
                'photos' => [$this->fakeImage('besar.jpg', 2 * 1024 * 1024)],
            ]))
            ->assertSessionHasErrors('photos.0');

        $this->from('/jual')
            ->post('/jual', array_merge($data, [
                'photos' => [UploadedFile::fake()->createWithContent('bukan-gambar.txt', 'bukan gambar')],
            ]))
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_hanya_pemilik_dapat_mengubah_status_mengedit_dan_menghapus_produk(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();
        $product = $this->createProduct($owner);

        $this->actingAs($otherUser)
            ->get(route('products.edit', $product))
            ->assertForbidden();
        $this->put(route('products.update', $product), [])
            ->assertForbidden();
        $this->patch(route('products.status', $product))
            ->assertForbidden();
        $this->delete(route('products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'status' => 'tersedia',
        ]);
    }

    public function test_pemilik_dapat_memperbarui_detail_menghapus_media_dan_menandai_terjual(): void
    {
        $owner = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);
        $product = $this->createProduct($owner, $category);
        $oldPath = 'products/'.$product->product_id.'/old-photo.jpg';
        Storage::disk('public')->put($oldPath, 'old photo');
        $oldMedia = ProductMedia::create([
            'product_id' => $product->product_id,
            'media_type' => 'foto',
            'media_url' => $oldPath,
            'file_size' => 9,
            'display_order' => 1,
        ]);
        $extraMedia = ProductMedia::create([
            'product_id' => $product->product_id,
            'media_type' => 'foto',
            'media_url' => 'products/'.$product->product_id.'/keep-photo.jpg',
            'file_size' => 10,
            'display_order' => 2,
        ]);
        Storage::disk('public')->put($extraMedia->media_url, 'keep photo');

        $this->actingAs($owner)
            ->put(route('products.update', $product), array_merge(
                $this->validProductData($category),
                [
                    'product_name' => 'Jaket diperbarui',
                    'delete_media' => [$oldMedia->media_id],
                    'photos' => [$this->fakeImage('foto-baru.jpg')],
                ],
            ))
            ->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'product_name' => 'Jaket diperbarui',
        ]);
        $this->assertDatabaseMissing('product_media', ['media_id' => $oldMedia->media_id]);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($extraMedia->media_url);
        $updatedMedia = $product->refresh()->load('media')->media;
        $this->assertCount(2, $updatedMedia);
        $this->assertSame([1, 2], $updatedMedia->pluck('display_order')->all());
        Storage::disk('public')->assertExists($updatedMedia->last()->media_url);

        $this->patch(route('products.status', $product))
            ->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'status' => 'terjual',
        ]);
        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_menghapus_produk_menghapus_semua_file_dan_admin_dapat_menghapus(): void
    {
        $owner = $this->createUser();
        $admin = $this->createUser(['role' => 'admin']);
        $product = $this->createProduct($owner);
        $path = 'products/'.$product->product_id.'/photo.jpg';
        Storage::disk('public')->put($path, 'photo');
        ProductMedia::create([
            'product_id' => $product->product_id,
            'media_type' => 'foto',
            'media_url' => $path,
            'file_size' => 5,
            'display_order' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('products', ['product_id' => $product->product_id]);
        Storage::disk('public')->assertMissing($path);
    }

    private function createUser(array $attributes = []): User
    {
        static $counter = 0;
        $counter++;

        return User::forceCreate(array_merge([
            'name' => 'Penjual '.$counter,
            'email' => 'seller'.$counter.'@example.test',
            'password' => 'password',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'is_verified' => true,
            'role' => 'user',
        ], $attributes));
    }

    private function createProduct(User $seller, ?Category $category = null): Product
    {
        $category ??= Category::create(['category_name' => 'Lainnya']);

        return Product::create([
            'seller_id' => $seller->user_id,
            'category_id' => $category->category_id,
            'product_name' => 'Jaket Kampus',
            'description' => 'Jaket kampus masih dalam kondisi baik dan layak digunakan.',
            'price' => 1500000,
            'condition' => 'Bekas',
            'status' => 'tersedia',
        ]);
    }

    private function fakeImage(string $filename, int $paddingBytes = 0): UploadedFile
    {
        $content = file_get_contents(base_path('docs/figma/home.jpeg'));
        if ($content === false) {
            throw new RuntimeException('Gambar fixture untuk test tidak dapat dibaca.');
        }

        return UploadedFile::fake()->createWithContent(
            $filename,
            $content.str_repeat(' ', $paddingBytes),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validProductData(Category $category): array
    {
        return [
            'product_name' => 'Jaket Kampus',
            'price' => '1.500.000',
            'category_id' => $category->category_id,
            'condition' => 'Bekas',
            'stock' => '3',
            'stock_original' => '1',
            'is_negotiable' => '0',
            'size' => 'M',
            'usage_period' => '6 bulan',
            'defects' => '',
            'description' => 'Jaket kampus masih dalam kondisi baik dan layak digunakan.',
        ];
    }
}
