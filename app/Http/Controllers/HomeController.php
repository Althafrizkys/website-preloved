<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $rawSearch = $request->query('q', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        $categoryId = $request->query('kategori');
        $condition = $request->query('kondisi');
        $nego = $request->query('nego');
        $minPrice = $request->query('harga_min');
        $maxPrice = $request->query('harga_max');

        $selectedCategory = is_string($categoryId) && ctype_digit($categoryId) ? $categoryId : '';
        $selectedCondition = in_array($condition, config('seklov.products.conditions'), true) ? $condition : '';
        $selectedNego = in_array($nego, ['ya', 'tidak'], true) ? $nego : '';
        $minPrice = is_string($minPrice) && ctype_digit($minPrice) ? $minPrice : '';
        $maxPrice = is_string($maxPrice) && ctype_digit($maxPrice) ? $maxPrice : '';

        $products = Product::query()
            ->where('status', 'tersedia')
            ->with([
                'media' => fn ($query) => $query
                    ->where('media_type', 'foto')
                    ->orderBy('display_order')
                    ->limit(1),
                'category',
                'seller:user_id,name,campus',
            ])
            ->when($search !== '', fn ($query) => $query->where('product_name', 'like', '%'.$search.'%'))
            ->when($selectedCategory !== '', fn ($query) => $query->where('category_id', (int) $selectedCategory))
            ->when($selectedCondition !== '', fn ($query) => $query->where('condition', $selectedCondition))
            ->when($selectedNego !== '', fn ($query) => $query->where('is_negotiable', $selectedNego === 'ya'))
            ->when($minPrice !== '', fn ($query) => $query->where('price', '>=', (int) $minPrice))
            ->when($maxPrice !== '', fn ($query) => $query->where('price', '<=', (int) $maxPrice))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('home', [
            'categories' => Category::query()->orderBy('category_name')->get(),
            'products' => $products,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'selectedCondition' => $selectedCondition,
            'selectedNego' => $selectedNego,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'conditions' => config('seklov.products.conditions'),
            'activeFilters' => array_filter([
                'kategori' => $selectedCategory,
                'kondisi' => $selectedCondition,
                'nego' => $selectedNego,
                'harga_min' => $minPrice,
                'harga_max' => $maxPrice,
            ]),
        ]);
    }
}
