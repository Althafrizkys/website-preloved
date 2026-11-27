<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shop_shows_twelve_products_per_page(): void
    {
        $user = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);

        foreach (range(1, 13) as $number) {
            $product = Product::create([
                'seller_id' => $user->user_id,
                'category_id' => $category->category_id,
                'product_name' => sprintf('Produk profil %02d', $number),
                'description' => 'Produk profil untuk menguji pagination.',
                'price' => 100000,
                'condition' => 'Bekas',
                'status' => 'tersedia',
            ]);
            $product->forceFill(['created_at' => now()->addSeconds($number)])->saveQuietly();
        }

        $this->actingAs($user)
            ->get(route('profile.me', ['tab' => 'shop']))
            ->assertOk()
            ->assertSee('13 barang')
            ->assertSee('Produk profil 13')
            ->assertDontSee('Produk profil 01')
            ->assertSee('tab=shop', false)
            ->assertSee('page=2', false);

        $this->get(route('profile.me', ['tab' => 'shop', 'page' => 2]))
            ->assertOk()
            ->assertSee('Produk profil 01')
            ->assertDontSee('Produk profil 02');
    }

    public function test_profile_wishlist_shows_twelve_requests_per_page(): void
    {
        $user = $this->createUser();
        $category = Category::create(['category_name' => 'Fashion']);

        foreach (range(1, 13) as $number) {
            $wishlist = WishlistRequest::create([
                'user_id' => $user->user_id,
                'category_id' => $category->category_id,
                'item_name' => sprintf('Wishlist profil %02d', $number),
                'condition' => 'Bekas',
                'budget' => 100000,
                'description' => 'Wishlist untuk menguji pagination.',
                'expired_at' => now()->addDays(7),
                'status' => 'aktif',
            ]);
            $wishlist->forceFill(['created_at' => now()->addSeconds($number)])->saveQuietly();
        }

        $this->actingAs($user)
            ->get(route('profile.me', ['tab' => 'wishlist']))
            ->assertOk()
            ->assertSee('13 Barang Dicari')
            ->assertSee('Wishlist profil 13')
            ->assertDontSee('Wishlist profil 01')
            ->assertSee('tab=wishlist', false)
            ->assertSee('page=2', false);

        $this->get(route('profile.me', ['tab' => 'wishlist', 'page' => 2]))
            ->assertOk()
            ->assertSee('Wishlist profil 01')
            ->assertDontSee('Wishlist profil 02');
    }

    private function createUser(): User
    {
        return User::forceCreate([
            'name' => 'Pemilik Profil',
            'email' => 'pemilik@example.test',
            'password' => 'password',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'is_verified' => true,
            'role' => 'user',
        ]);
    }
}
