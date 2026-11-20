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
            ->when(
                is_string($categoryId) && ctype_digit($categoryId),
                fn ($query) => $query->where('category_id', (int) $categoryId),
            )
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('home', [
            'categories' => Category::query()->orderBy('category_name')->get(),
            'products' => $products,
            'search' => $search,
            'selectedCategory' => is_string($categoryId) && ctype_digit($categoryId) ? $categoryId : '',
        ]);
    }
}
