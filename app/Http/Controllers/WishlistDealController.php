<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishlistDealOfferRequest;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Services\WishlistDealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistDealController extends Controller
{
    public function store(
        StoreWishlistDealOfferRequest $request,
        Conversation $conversation,
        WishlistDealService $dealService,
    ): RedirectResponse {
        $dealService->createOffer(
            $request->user(),
            $conversation,
            (int) $request->validated('offered_price'),
        );

        return redirect()
            ->route('chat.show', $conversation)
            ->with('success', 'Penawaran barang berhasil dikirim.');
    }

    public function accept(
        Request $request,
        Negotiation $negotiation,
        WishlistDealService $dealService,
    ): RedirectResponse {
        $offer = $dealService->acceptOffer($request->user(), $negotiation);

        if ($offer->product_id !== null) {
            return redirect()->route('transactions.create', [
                'product' => $offer->product_id,
                'negotiation_id' => $offer->negotiation_id,
            ]);
        }

        return redirect()->route('transactions.create-wishlist', $offer);
    }

    public function reject(
        Request $request,
        Negotiation $negotiation,
        WishlistDealService $dealService,
    ): RedirectResponse {
        $offer = $dealService->rejectOffer($request->user(), $negotiation);

        return redirect()
            ->route('chat.show', $offer->conversation_id)
            ->with('success', 'Penawaran ditolak.');
    }
}
