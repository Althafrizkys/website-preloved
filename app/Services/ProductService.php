<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(private readonly ProductMediaService $mediaService) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $photos
     */
    public function create(User $seller, array $data, array $photos, ?UploadedFile $video): Product
    {
        return DB::transaction(function () use ($seller, $data, $photos, $video): Product {
            $product = Product::create([
                ...$this->productAttributes($data),
                'seller_id' => $seller->user_id,
                'status' => 'tersedia',
            ]);

            $this->mediaService->sync($product, [], $photos, $video);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $photos
     */
    public function update(Product $product, array $data, array $photos, ?UploadedFile $video): Product
    {
        $deleteIds = array_map('intval', $data['delete_media'] ?? []);
        $attributes = $this->productAttributes($data);

        $oldPaths = DB::transaction(function () use ($product, $attributes, $data, $deleteIds, $photos, $video): array {
            $lockedProduct = Product::query()
                ->where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();
            $submittedStock = (int) $attributes['stock'];
            $originalStock = (int) $data['stock_original'];

            if ($originalStock !== $lockedProduct->stock && $submittedStock !== $originalStock) {
                throw ValidationException::withMessages([
                    'stock' => 'Stok berubah sejak formulir dibuka. Muat ulang halaman sebelum mengubah stok.',
                ]);
            }

            $attributes['stock'] = $submittedStock === $originalStock
                ? $lockedProduct->stock
                : $submittedStock;

            if ((int) $attributes['stock'] !== (int) $lockedProduct->stock) {
                $attributes['status'] = (int) $attributes['stock'] === 0
                    ? 'terjual'
                    : 'tersedia';
            }

            $lockedProduct->update($attributes);

            return $this->mediaService->sync($lockedProduct, $deleteIds, $photos, $video);
        });

        $this->mediaService->deleteFiles($oldPaths);

        return $product->refresh();
    }

    public function markSold(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $lockedProduct = Product::query()
                ->where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedProduct->update([
                'stock' => 0,
                'status' => 'terjual',
            ]);
        });
    }

    public function delete(Product $product): void
    {
        $paths = $this->mediaService->pathsForProduct($product);

        DB::transaction(function () use ($product): void {
            $product->delete();
        });

        $this->mediaService->deleteFiles($paths);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'product_name',
            'price',
            'category_id',
            'condition',
            'size',
            'usage_period',
            'defects',
            'description',
            'stock',
            'is_negotiable',
        ]));
    }
}
