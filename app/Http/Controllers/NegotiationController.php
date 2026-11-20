<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfferRequest;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Services\NegotiationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class NegotiationController extends Controller
{
    public function show(Request $request, Product $product): View|RedirectResponse
    {
        $user = $request->user();

        if ((int) $product->seller_id === (int) $user->user_id) {
            Gate::authorize('viewOffers', [Negotiation::class, $product]);

            if (! $request->ajax()) {
                return redirect()->route('products.edit', $product);
            }

            $conversations = Conversation::query()
                ->where('product_id', $product->product_id)
                ->where('seller_id', $user->user_id)
                ->with([
                    'buyer:user_id,name,campus',
                    'negotiations' => fn ($query) => $query
                        ->with('sender:user_id,name')
                        ->orderBy('created_at')
                        ->orderBy('negotiation_id'),
                ])
                ->orderByDesc('created_at')
                ->get();

            return view('negotiations._seller-conversations', compact('product', 'conversations'));
        }

        $conversation = Conversation::query()
            ->where('product_id', $product->product_id)
            ->where('buyer_id', $user->user_id)
            ->where('seller_id', $product->seller_id)
            ->with([
                'negotiations' => fn ($query) => $query
                    ->with('sender:user_id,name')
                    ->orderBy('created_at')
                    ->orderBy('negotiation_id'),
            ])
            ->first();

        if ($conversation) {
            Gate::authorize('viewConversation', [Negotiation::class, $conversation]);
        } else {
            Gate::authorize('create', [Negotiation::class, $product]);
        }

        if (! $request->ajax()) {
            return redirect()->route('products.show', [
                'product' => $product,
                'nego' => 'open',
            ]);
        }

        $product->loadMissing([
            'media',
            'category',
            'seller:user_id,name,campus',
        ]);
        $negotiations = $conversation?->negotiations ?? collect();
        $latestOffer = $negotiations->last();
        $agreedOffer = $conversation?->agreedOffer();
        $canOffer = $product->status === 'tersedia'
            && ! $negotiations->contains('status', 'menunggu')
            && ! $agreedOffer;
        $minimumOffer = max(
            (int) config('seklov.negotiations.minimum_price'),
            (int) ceil(
                (float) $product->price
                * (int) config('seklov.negotiations.minimum_price_percent')
                / 100,
            ),
        );

        return view('negotiations._buyer-modal', compact(
            'product',
            'conversation',
            'negotiations',
            'latestOffer',
            'agreedOffer',
            'canOffer',
            'minimumOffer',
        ));
    }

    public function store(
        StoreOfferRequest $request,
        Product $product,
        NegotiationService $negotiationService,
    ): RedirectResponse {
        try {
            $negotiationService->createOffer(
                $request->user(),
                $product,
                (int) $request->validated('offered_price'),
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('products.show', ['product' => $product, 'nego' => 'open'])
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->route('products.show', ['product' => $product, 'nego' => 'open'])
            ->with('success', 'Tawaran nego berhasil dikirim kepada penjual.');
    }

    public function accept(Negotiation $negotiation, NegotiationService $negotiationService): RedirectResponse
    {
        Gate::authorize('respond', $negotiation);
        $negotiation->loadMissing('conversation.product');
        $product = $negotiation->conversation->product;

        $negotiationService->acceptOffer(request()->user(), $negotiation);

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Tawaran berhasil diterima.');
    }

    public function reject(Negotiation $negotiation, NegotiationService $negotiationService): RedirectResponse
    {
        Gate::authorize('respond', $negotiation);
        $negotiation->loadMissing('conversation.product');
        $product = $negotiation->conversation->product;

        $negotiationService->rejectOffer(request()->user(), $negotiation);

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Tawaran berhasil ditolak.');
    }
}
