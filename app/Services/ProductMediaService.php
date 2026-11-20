<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductMediaService
{
    /**
     * Simpan media baru dan hapus catatan media yang dipilih dalam transaksi pemanggil.
     *
     * @param  list<int>  $deleteIds
     * @param  list<UploadedFile>  $photos
     * @return list<string> Path file lama yang aman dihapus setelah transaksi berhasil.
     */
    public function sync(Product $product, array $deleteIds, array $photos, ?UploadedFile $video): array
    {
        if ($video !== null) {
            $deleteIds = array_merge(
                $deleteIds,
                $product->media()->where('media_type', 'video')->pluck('media_id')->all(),
            );
        }

        $mediaToDelete = $product->media()
            ->whereIn('media_id', array_unique(array_map('intval', $deleteIds)))
            ->get();
        $oldPaths = $mediaToDelete->pluck('media_url')->all();
        $writtenPaths = [];

        try {
            if ($mediaToDelete->isNotEmpty()) {
                $product->media()
                    ->whereIn('media_id', $mediaToDelete->modelKeys())
                    ->delete();
            }

            foreach ($photos as $photo) {
                $writtenPaths[] = $this->storeFile($product, $photo, 'foto');
            }

            if ($video !== null) {
                $writtenPaths[] = $this->storeFile($product, $video, 'video');
            }

            $this->reorder($product);
        } catch (Throwable $exception) {
            try {
                $this->deleteFiles($writtenPaths);
            } catch (Throwable $cleanupException) {
                throw new RuntimeException(
                    'Proses media gagal dan file sementara tidak dapat dibersihkan.',
                    previous: $exception,
                );
            }

            throw $exception;
        }

        return $oldPaths;
    }

    /**
     * @return list<string>
     */
    public function pathsForProduct(Product $product): array
    {
        return $product->media()->pluck('media_url')->all();
    }

    /**
     * @param  list<string>  $paths
     */
    public function deleteFiles(array $paths): void
    {
        if ($paths !== [] && ! Storage::disk('public')->delete($paths)) {
            throw new RuntimeException('File media barang tidak dapat dihapus dari penyimpanan.');
        }
    }

    private function storeFile(Product $product, UploadedFile $file, string $mediaType): string
    {
        $directory = 'products/'.$product->product_id;
        $extension = strtolower((string) $file->extension());
        $filename = Str::uuid().'.'.$extension;
        $path = Storage::disk('public')->putFileAs($directory, $file, $filename);

        if ($path === false) {
            throw new RuntimeException('File media barang gagal disimpan.');
        }

        try {
            $product->media()->create([
                'media_type' => $mediaType,
                'media_url' => $path,
                'file_size' => (int) $file->getSize(),
                'display_order' => 1,
            ]);
        } catch (Throwable $exception) {
            $this->deleteFiles([$path]);

            throw $exception;
        }

        return $path;
    }

    private function reorder(Product $product): void
    {
        $media = $product->media()
            ->orderByRaw("CASE WHEN media_type = 'foto' THEN 0 ELSE 1 END")
            ->orderBy('display_order')
            ->orderBy('media_id')
            ->get();

        foreach ($media as $index => $item) {
            $item->update(['display_order' => $index + 1]);
        }
    }
}
