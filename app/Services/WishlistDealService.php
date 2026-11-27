<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WishlistRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WishlistDealService
{
    public function createOffer(User $offerer, Conversation $conversation, int $offeredPrice): Negotiation
    {
        return DB::transaction(function () use ($offerer, $conversation, $offeredPrice): Negotiation {
            $lockedConversation = Conversation::query()
                ->where('conversation_id', $conversation->conversation_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedConversation->wishlist_request_id === null
                || (int) $lockedConversation->seller_id !== (int) $offerer->user_id
            ) {
                throw new AccessDeniedHttpException('Hanya pihak yang menghubungi pemilik wishlist yang dapat mengajukan penawaran.');
            }

            $wishlist = WishlistRequest::query()
                ->where('request_id', $lockedConversation->wishlist_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $wishlist->user_id !== (int) $lockedConversation->buyer_id) {
                throw new AccessDeniedHttpException('Pemilik wishlist tidak sesuai dengan percakapan ini.');
            }

            if ($wishlist->status !== 'aktif' || $wishlist->expired_at?->isPast()) {
                $this->fail('Permintaan wishlist ini sudah tidak aktif.');
            }

            $offers = $lockedConversation->negotiations()
                ->orderByDesc('created_at')
                ->orderByDesc('negotiation_id')
                ->lockForUpdate()
                ->get();

            if ($offers->contains('status', 'menunggu')) {
                $this->fail('Tawaran sebelumnya masih menunggu jawaban.');
            }

            $acceptedOffers = $offers->where('status', 'diterima');
            $hasActiveAcceptedOffer = $acceptedOffers->contains(function (Negotiation $acceptedOffer): bool {
                $transactionItem = $acceptedOffer->transactionItem()
                    ->with('transaction')
                    ->first();

                return $transactionItem?->transaction?->transaction_status !== 'dibatalkan';
            });

            if ($hasActiveAcceptedOffer) {
                $this->fail('Kesepakatan sudah dibuat untuk permintaan ini.');
            }

            $offer = $lockedConversation->negotiations()->create([
                'product_id' => null,
                'offered_item_name' => $wishlist->item_name,
                'sender_id' => $offerer->user_id,
                'offered_price' => $offeredPrice,
                'quantity' => 1,
                'status' => 'menunggu',
            ]);

            UserNotification::create([
                'user_id' => $lockedConversation->buyer_id,
                'product_id' => null,
                'negotiation_id' => $offer->negotiation_id,
                'role' => 'pembeli',
                'notification_type' => 'negosiasi',
                'title' => 'Penawaran wishlist — '.$wishlist->item_name,
                'message' => $offerer->name.' menawarkan "'.$wishlist->item_name.'" seharga Rp '.number_format($offeredPrice, 0, ',', '.').'.',
                'offered_price' => $offeredPrice,
                'original_price' => (int) $wishlist->budget,
            ]);

            return $offer->load('sender');
        });
    }

    public function acceptOffer(User $wishlistOwner, Negotiation $offer): Negotiation
    {
        return $this->respond($wishlistOwner, $offer, 'diterima');
    }

    public function rejectOffer(User $wishlistOwner, Negotiation $offer): Negotiation
    {
        return $this->respond($wishlistOwner, $offer, 'ditolak');
    }

    private function respond(User $wishlistOwner, Negotiation $offer, string $status): Negotiation
    {
        return DB::transaction(function () use ($wishlistOwner, $offer, $status): Negotiation {
            $initialOffer = Negotiation::query()
                ->where('negotiation_id', $offer->negotiation_id)
                ->firstOrFail();
            $product = $initialOffer->product_id
                ? Product::query()
                    ->where('product_id', $initialOffer->product_id)
                    ->lockForUpdate()
                    ->first()
                : null;
            $conversation = Conversation::query()
                ->where('conversation_id', $initialOffer->conversation_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $conversation->wishlist_request_id === null
                || (int) $conversation->buyer_id !== (int) $wishlistOwner->user_id
                || (int) $conversation->seller_id !== (int) $initialOffer->sender_id
                || ($product && (int) $conversation->seller_id !== (int) $product->seller_id)
            ) {
                throw new AccessDeniedHttpException('Hanya pemilik wishlist yang dapat menanggapi penawaran ini.');
            }

            $wishlist = WishlistRequest::query()
                ->where('request_id', $conversation->wishlist_request_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOffer = Negotiation::query()
                ->where('negotiation_id', $offer->negotiation_id)
                ->where('conversation_id', $conversation->conversation_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $wishlist->user_id !== (int) $wishlistOwner->user_id) {
                throw new AccessDeniedHttpException('Pemilik wishlist tidak sesuai dengan percakapan ini.');
            }

            if ($lockedOffer->status !== 'menunggu') {
                $this->fail('Penawaran ini sudah ditanggapi.');
            }

            if ($status === 'diterima') {
                if ($wishlist->status !== 'aktif' || $wishlist->expired_at?->isPast()) {
                    $this->fail('Permintaan wishlist ini sudah tidak aktif.');
                }

                if (
                    ($initialOffer->product_id === null && ! $initialOffer->offered_item_name)
                    || ($initialOffer->product_id !== null && (! $product || $product->status !== 'tersedia' || $product->stock < 1))
                ) {
                    $this->fail('Barang yang ditawarkan sudah tidak tersedia.');
                }
            }

            $lockedOffer->update([
                'status' => $status,
                'responded_at' => now(),
            ]);

            $decision = $status === 'diterima' ? 'diterima' : 'ditolak';
            UserNotification::create([
                'user_id' => $conversation->seller_id,
                'product_id' => $product?->product_id,
                'negotiation_id' => $lockedOffer->negotiation_id,
                'role' => 'penjual',
                'notification_type' => 'negosiasi',
                'title' => 'Penawaran wishlist '.$decision.' — '.$wishlist->item_name,
                'message' => $wishlistOwner->name.' '.$decision.' penawaran untuk "'.($lockedOffer->offered_item_name ?? $product?->product_name ?? $wishlist->item_name).'" seharga Rp '.number_format((int) $lockedOffer->offered_price, 0, ',', '.').'.',
                'offered_price' => (int) $lockedOffer->offered_price,
                'original_price' => (int) $wishlist->budget,
            ]);

            return $lockedOffer->load(['sender', 'product']);
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'offered_price' => $message,
        ]);
    }
}
