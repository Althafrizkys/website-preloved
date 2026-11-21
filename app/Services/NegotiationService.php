<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NegotiationService
{
    public function createOffer(User $buyer, Product $product, int $offeredPrice): Negotiation
    {
        return DB::transaction(function () use ($buyer, $product, $offeredPrice): Negotiation {
            $lockedProduct = Product::query()
                ->where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProduct->status !== 'tersedia') {
                $this->fail('Barang sudah terjual; negosiasi tidak lagi tersedia.');
            }

            if ((int) $lockedProduct->seller_id === (int) $buyer->user_id) {
                throw new AccessDeniedHttpException('Penjual tidak dapat menawar barangnya sendiri.');
            }

            $minimumOffer = max(
                (int) config('seklov.negotiations.minimum_price'),
                (int) ceil(
                    (float) $lockedProduct->price
                    * (int) config('seklov.negotiations.minimum_price_percent')
                    / 100,
                ),
            );

            if ($offeredPrice < $minimumOffer) {
                $this->fail(
                    'Tawaran minimal Rp '.number_format($minimumOffer, 0, ',', '.').'.',
                );
            }

            if ($offeredPrice > (int) $lockedProduct->price) {
                $this->fail('Tawaran tidak boleh lebih besar dari harga barang.');
            }

            $conversation = Conversation::query()
                ->where('product_id', $lockedProduct->product_id)
                ->where('buyer_id', $buyer->user_id)
                ->where('seller_id', $lockedProduct->seller_id)
                ->lockForUpdate()
                ->first();

            if (! $conversation) {
                $conversation = Conversation::create([
                    'product_id' => $lockedProduct->product_id,
                    'buyer_id' => $buyer->user_id,
                    'seller_id' => $lockedProduct->seller_id,
                ]);
            }

            $history = $conversation->negotiations()
                ->orderByDesc('created_at')
                ->orderByDesc('negotiation_id')
                ->lockForUpdate()
                ->get();

            if ($history->contains('status', 'diterima')) {
                $this->fail('Tawaran sudah diterima; negosiasi ini telah selesai.');
            }

            if ($history->contains('status', 'menunggu')) {
                $this->fail('Tawaran sebelumnya masih menunggu jawaban penjual.');
            }

            $latestOffer = $history->first();

            if ($latestOffer?->status === 'ditolak' && (int) $latestOffer->offered_price === $offeredPrice) {
                $this->fail('Tawaran baru harus berbeda dari tawaran yang ditolak.');
            }

            $offer = $conversation->negotiations()->create([
                'sender_id' => $buyer->user_id,
                'parent_offer_id' => null,
                'offered_price' => $offeredPrice,
                'quantity' => 1,
                'status' => 'menunggu',
            ]);

            UserNotification::create([
                'user_id' => $lockedProduct->seller_id,
                'product_id' => $lockedProduct->product_id,
                'negotiation_id' => $offer->negotiation_id,
                'role' => 'penjual',
                'notification_type' => 'negosiasi',
                'title' => 'Tawaran nego baru',
                'message' => $buyer->name.' menawar '.$lockedProduct->product_name
                    .' seharga Rp '.number_format($offeredPrice, 0, ',', '.').'.',
                'offered_price' => $offeredPrice,
                'original_price' => (int) $lockedProduct->price,
            ]);

            return $offer->load('sender');
        });
    }

    public function acceptOffer(User $seller, Negotiation $offer): Negotiation
    {
        return $this->respond($seller, $offer, 'diterima');
    }

    public function rejectOffer(User $seller, Negotiation $offer): Negotiation
    {
        return $this->respond($seller, $offer, 'ditolak');
    }

    private function respond(User $seller, Negotiation $offer, string $status): Negotiation
    {
        return DB::transaction(function () use ($seller, $offer, $status): Negotiation {
            $initialConversation = Conversation::query()
                ->where('conversation_id', $offer->conversation_id)
                ->firstOrFail();

            $product = Product::query()
                ->where('product_id', $initialConversation->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $conversation = Conversation::query()
                ->where('conversation_id', $initialConversation->conversation_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOffer = Negotiation::query()
                ->where('negotiation_id', $offer->negotiation_id)
                ->where('conversation_id', $conversation->conversation_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $conversation->seller_id !== (int) $seller->user_id) {
                throw new AccessDeniedHttpException('Hanya penjual barang yang dapat menanggapi tawaran.');
            }

            if ($product->status !== 'tersedia') {
                $this->fail('Barang sudah terjual; tawaran menunggu tidak lagi aktif.');
            }

            if ($lockedOffer->status !== 'menunggu') {
                $this->fail('Tawaran ini sudah ditanggapi.');
            }

            $lockedOffer->update([
                'status' => $status,
                'responded_at' => now(),
            ]);

            $decision = $status === 'diterima' ? 'diterima' : 'ditolak';
            UserNotification::create([
                'user_id' => $conversation->buyer_id,
                'product_id' => $product->product_id,
                'negotiation_id' => $lockedOffer->negotiation_id,
                'role' => 'pembeli',
                'notification_type' => 'negosiasi',
                'title' => 'Tawaran nego '.$decision,
                'message' => 'Tawaranmu untuk '.$product->product_name.' sebesar Rp '
                    .number_format((int) $lockedOffer->offered_price, 0, ',', '.')
                    .' telah '.$decision.'.',
                'offered_price' => (int) $lockedOffer->offered_price,
                'original_price' => (int) $product->price,
            ]);

            return $lockedOffer->load('sender');
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'offered_price' => $message,
        ]);
    }
}
