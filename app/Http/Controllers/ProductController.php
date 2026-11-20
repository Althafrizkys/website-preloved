<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create', [
            'categories' => Category::query()->orderBy('category_name')->get(),
            'product' => null,
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $productService): RedirectResponse
    {
        $data = $request->validated();
        $product = $productService->create(
            $request->user(),
            $data,
            $request->file('photos', []),
            $request->file('video'),
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Barang berhasil dipublikasikan.');
    }

    public function show(Product $product): View
    {
        $product->load([
            'media',
            'category',
            'seller:user_id,name,campus,cohort',
        ]);

        $relatedProducts = Product::query()
            ->where('status', 'tersedia')
            ->where('product_id', '!=', $product->product_id)
            ->where('category_id', $product->category_id)
            ->with([
                'media' => fn ($query) => $query
                    ->where('media_type', 'foto')
                    ->orderBy('display_order')
                    ->limit(1),
                'category',
                'seller:user_id,name,campus',
            ])
            ->latest('created_at')
            ->limit(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        $product->load('media');

        return view('products.edit', [
            'categories' => Category::query()->orderBy('category_name')->get(),
            'product' => $product,
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductService $productService,
    ): RedirectResponse {
        $data = $request->validated();
        $updatedProduct = $productService->update(
            $product,
            $data,
            $request->file('photos', []),
            $request->file('video'),
        );

        return redirect()
            ->route('products.show', $updatedProduct)
            ->with('success', 'Perubahan barang berhasil disimpan.');
    }

    public function markSold(Product $product, ProductService $productService): RedirectResponse
    {
        Gate::authorize('updateStatus', $product);
        $productService->markSold($product);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Barang ditandai terjual.');
    }

    public function destroy(Product $product, ProductService $productService): RedirectResponse
    {
        Gate::authorize('delete', $product);
        $productService->delete($product);

        return redirect()
            ->route('home')
            ->with('success', 'Postingan barang berhasil dihapus.');
    }
}
