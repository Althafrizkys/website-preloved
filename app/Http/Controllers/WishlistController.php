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