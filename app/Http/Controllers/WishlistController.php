<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\WishlistRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->query('kategori');
        $condition = $request->query('kondisi');

        $selectedCategory = is_string($categoryId) && ctype_digit($categoryId) ? $categoryId : '';
        $selectedCondition = in_array($condition, config('seklov.products.conditions'), true) ? $condition : '';

        $wishlists = WishlistRequest::query()
            ->where('status', 'aktif')
            ->where('expired_at', '>', now())
            ->with(['category', 'user:user_id,name,campus,profile_photo'])
            ->when($search !== '', fn ($q) => $q->where('item_name', 'like', '%'.$search.'%'))
            ->when($selectedCategory !== '', fn ($q) => $q->where('category_id', (int) $selectedCategory))
            ->when($selectedCondition !== '', fn ($q) => $q->where('condition', $selectedCondition))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('wishlist.index', [
            'wishlists' => $wishlists,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'selectedCondition' => $selectedCondition,
        ] + $this->formData());
    }
    
public function create(): View
    {
        return view('wishlist.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'durasi' => ['required', 'in:7,14,30'],
        ]);

        $durasi = (int) $data['durasi'];
        unset($data['durasi'], $data['image']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('wishlists', 'public');
        }

        $request->user()->wishlistRequests()->create($data + [
            'expired_at' => now()->addDays($durasi),
            'status' => 'aktif',
        ]);

        return redirect()->route('profile.me', ['tab' => 'wishlist'])
            ->with('success', 'Permintaan barang berhasil ditambahkan.');
    }

    public function edit(Request $request, WishlistRequest $wishlist): View
    {
        $this->authorizeOwner($request, $wishlist);

        return view('wishlist.edit', $this->formData() + ['wishlist' => $wishlist]);
    }

    public function update(Request $request, WishlistRequest $wishlist): RedirectResponse
    {
        $this->authorizeOwner($request, $wishlist);

        $data = $request->validate($this->rules());
        unset($data['image']);

        if ($request->hasFile('image')) {
            if ($wishlist->image) {
                Storage::disk('public')->delete($wishlist->image);
            }
            $data['image'] = $request->file('image')->store('wishlists', 'public');
        }

        $wishlist->update($data);

        return redirect()->route('profile.me', ['tab' => 'wishlist'])
            ->with('success', 'Permintaan barang berhasil diperbarui.');
    }

    public function destroy(Request $request, WishlistRequest $wishlist): RedirectResponse
    {
        $this->authorizeOwner($request, $wishlist);

        if ($wishlist->image) {
            Storage::disk('public')->delete($wishlist->image);
        }
        $wishlist->delete();

        return redirect()->route('profile.me', ['tab' => 'wishlist'])
            ->with('success', 'Permintaan barang dihapus.');
    }

    private function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'condition' => ['required', Rule::in(config('seklov.products.conditions'))],
            'budget' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->orderBy('category_name')->get(),
            'conditions' => config('seklov.products.conditions'),
        ];
    }

    private function authorizeOwner(Request $request, WishlistRequest $wishlist): void
    {
        abort_unless((int) $request->user()->user_id === (int) $wishlist->user_id, 403);
    }
}