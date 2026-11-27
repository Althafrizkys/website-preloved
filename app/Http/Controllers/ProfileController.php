<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $oldPhoto = $user->profile_photo;

        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profiles', 'public');

            if (! is_string($photoPath)) {
                throw new RuntimeException('Foto profil gagal disimpan.');
            }

            $data['profile_photo'] = $photoPath;
        }

        $user->update($data);

        if (isset($data['profile_photo']) && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()->route('profile.me')->with('success', 'Profil berhasil diperbarui.');
    }

    public function me(Request $request): View
    {
        return $this->render($request, $request->user());
    }

    public function show(Request $request, User $user): View|RedirectResponse
    {
        // Buka toko sendiri lewat /toko/{id} -> arahkan ke halaman pribadi
        if ($request->user()?->user_id === $user->user_id) {
            return redirect()->route('profile.me', $request->query());
        }

        return $this->render($request, $user);
    }

    private function render(Request $request, User $user): View
    {
        $isOwner = $request->user()?->user_id === $user->user_id;

        $tab = in_array($request->query('tab'), ['shop', 'wishlist', 'reviews'], true)
            ? $request->query('tab')
            : 'shop';

        $productsQuery = fn () => $user->products()->where('status', 'tersedia');
        $wishlistsQuery = fn () => $user->wishlistRequests()
            ->when(! $isOwner, fn ($q) => $q->where('status', 'aktif')->where('expired_at', '>', now()));
        $ratingsQuery = fn () => $user->receivedRatings();

        $counts = [
            'shop' => $productsQuery()->count(),
            'wishlist' => $wishlistsQuery()->count(),
            'reviews' => $ratingsQuery()->count(),
        ];

        $items = match ($tab) {
            'shop' => $productsQuery()
                ->with([
                    'media' => fn ($q) => $q->where('media_type', 'foto'),
                    'category',
                    'seller:user_id,name,campus',
                ])
                ->latest('created_at')
                ->get(),
            'wishlist' => $wishlistsQuery()->with('category')->latest('created_at')->get(),
            'reviews' => $ratingsQuery()->orderByDesc('created_at')->get(),
        };

        $reviewers = $tab === 'reviews'
            ? User::whereIn('user_id', $items->pluck('reviewer_id'))->get()->keyBy('user_id')
            : collect();

        return view('profile.show', [
            'user' => $user,
            'isOwner' => $isOwner,
            'tab' => $tab,
            'items' => $items,
            'counts' => $counts,
            'reviewers' => $reviewers,
            'ratingAvg' => round((float) $ratingsQuery()->avg('stars'), 1),
        ]);
    }
}