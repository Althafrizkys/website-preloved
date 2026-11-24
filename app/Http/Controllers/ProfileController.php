<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
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