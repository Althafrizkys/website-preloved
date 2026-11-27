<?php

namespace App\Services;

use App\Models\CampusMeetingPoint;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WishlistRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TransactionService
{
    public const SERVICE_FEE = 0;

    public function createCodTransaction(
        User $buyer,
        Product $product,
        CampusMeetingPoint $meetingPoint,
        int $quantity = 1,
        ?string $buyerNotes = null,
        ?int $negotiationId = null,
    ): Transaction {
        return DB::transaction(function () use ($buyer, $product, $meetingPoint, $quantity, $buyerNotes, $negotiationId): Transaction {
            $lockedProduct = Product::query()
                ->where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->seller_id === (int) $buyer->user_id) {
                throw new AccessDeniedHttpException('Penjual tidak dapat membeli barangnya sendiri.');
            }

            if ($lockedProduct->status !== 'tersedia') {
                throw ValidationException::withMessages([
                    'product_id' => 'Barang ini sudah terjual atau tidak lagi tersedia.',
                ]);
            }

            if ($quantity < 1 || $quantity > $lockedProduct->stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pesanan melebihi stok yang tersedia ('.$lockedProduct->stock.' unit).',
                ]);
            }

            if (! $meetingPoint->is_active) {
                throw ValidationException::withMessages([
                    'meeting_point_id' => 'Titik temu COD yang dipilih tidak aktif.',
                ]);
            }

            $unitPrice = (int) $lockedProduct->price;
            $agreedDiscount = 0;

            $conversation = null;
            $agreedOffer = null;
            if ($negotiationId) {
                $offer = Negotiation::query()
                    ->where('negotiation_id', $negotiationId)
                    ->lockForUpdate()
                    ->first();

                $offerConversation = $offer
                    ? Conversation::query()
                        ->where('conversation_id', $offer->conversation_id)
                        ->lockForUpdate()
                        ->first()
                    : null;
                $isProductOffer = $offerConversation
                    && (int) $offerConversation->product_id === (int) $lockedProduct->product_id
                    && $offer->product_id === null;
                $isWishlistOffer = $offerConversation
                    && $offerConversation->wishlist_request_id !== null
                    && (int) $offer->product_id === (int) $lockedProduct->product_id;

                if (
                    ! $offer
                    || $offer->status !== 'diterima'
                    || (int) $offerConversation?->buyer_id !== (int) $buyer->user_id
                    || (int) $offerConversation?->seller_id !== (int) $lockedProduct->seller_id
                    || (! $isProductOffer && ! $isWishlistOffer)
                ) {
                    throw ValidationException::withMessages([
                        'negotiation_id' => 'Tawaran nego yang dipilih tidak berlaku untuk barang ini.',
                    ]);
                }

                $conversation = $offerConversation;
                $agreedOffer = $offer;
            } else {
                $conversation = Conversation::query()
                    ->where('product_id', $lockedProduct->product_id)
                    ->where('buyer_id', $buyer->user_id)
                    ->where('seller_id', $lockedProduct->seller_id)
                    ->first();
                $agreedOffer = $conversation?->agreedOffer();
            }

            if ($agreedOffer) {
                $unitPrice = (int) $agreedOffer->offered_price;
                $agreedDiscount = max(0, (int) $lockedProduct->price - $unitPrice);
            }

            $wishlistRequest = null;
            if ($conversation?->wishlist_request_id !== null) {
                $wishlistRequest = WishlistRequest::query()
                    ->where('request_id', $conversation->wishlist_request_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $agreedOffer
                    || (int) $wishlistRequest->user_id !== (int) $buyer->user_id
                    || $wishlistRequest->status !== 'aktif'
                    || $wishlistRequest->expired_at?->isPast()
                ) {
                    throw ValidationException::withMessages([
                        'negotiation_id' => 'Kesepakatan wishlist ini sudah tidak berlaku.',
                    ]);
                }
            }

            $subtotal = $unitPrice * $quantity;
            $totalPrice = $subtotal ;

            $transaction = Transaction::create([
                'buyer_id' => $buyer->user_id,
                'seller_id' => $lockedProduct->seller_id,
                'meeting_point_id' => $meetingPoint->meeting_point_id,
                'subtotal_price' => $subtotal,
                'service_fee' => 0,
                'agreed_discount' => $agreedDiscount,
                'total_price' => $totalPrice,
                'payment_method' => 'cod',
                'transaction_status' => 'menunggu',
                'buyer_notes' => $buyerNotes,
                'created_at' => now(),
            ]);

            $transaction->items()->create([
                'product_id' => $lockedProduct->product_id,
                'quantity' => $quantity,
                'agreed_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);

            $lockedProduct->stock -= $quantity;
            $lockedProduct->update([
                'stock' => $lockedProduct->stock,
                'status' => $lockedProduct->stock === 0 ? 'terjual' : 'tersedia',
            ]);

            if ($wishlistRequest) {
                $wishlistRequest->update(['status' => 'terpenuhi']);
            }

            // Notifikasi untuk Penjual
            UserNotification::create([
                'user_id' => $lockedProduct->seller_id,
                'product_id' => $lockedProduct->product_id,
                'role' => 'penjual',
                'notification_type' => 'transaksi',
                'title' => 'Pembelian masuk — '.$lockedProduct->product_name,
                'message' => $buyer->name.' membeli "'.$lockedProduct->product_name.'" seharga Rp '.number_format($unitPrice, 0, ',', '.').' lewat COD di '.$meetingPoint->point_name.'.',
                'original_price' => (int) $lockedProduct->price,
                'offered_price' => $unitPrice,
            ]);

            // Notifikasi untuk Pembeli
            UserNotification::create([
                'user_id' => $buyer->user_id,
                'product_id' => $lockedProduct->product_id,
                'role' => 'pembeli',
                'notification_type' => 'transaksi',
                'title' => 'Pesanan berhasil dibuat — '.$lockedProduct->product_name,
                'message' => 'Pesananmu untuk "'.$lockedProduct->product_name.'" (Rp '.number_format($totalPrice, 0, ',', '.').') berhasil dibuat. Tunggu konfirmasi jadwal COD dari penjual.',
                'original_price' => (int) $lockedProduct->price,
                'offered_price' => $unitPrice,
            ]);

            return $transaction;
        });
    }

    public function createWishlistCodTransaction(
        User $buyer,
        CampusMeetingPoint $meetingPoint,
        int $quantity,
        ?string $buyerNotes,
        int $negotiationId,
    ): Transaction {
        return DB::transaction(function () use ($buyer, $meetingPoint, $quantity, $buyerNotes, $negotiationId): Transaction {
            $offer = Negotiation::query()
                ->where('negotiation_id', $negotiationId)
                ->lockForUpdate()
                ->firstOrFail();
            $conversation = Conversation::query()
                ->where('conversation_id', $offer->conversation_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $offer->status !== 'diterima'
                || $offer->product_id !== null
                || ! $offer->offered_item_name
                || (int) $conversation->buyer_id !== (int) $buyer->user_id
                || (int) $conversation->seller_id !== (int) $offer->sender_id
                || $conversation->wishlist_request_id === null
            ) {
                throw ValidationException::withMessages([
                    'negotiation_id' => 'Kesepakatan wishlist tidak berlaku.',
                ]);
            }

            if ($quantity !== 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah barang wishlist harus 1.',
                ]);
            }

            if (! $meetingPoint->is_active) {
                throw ValidationException::withMessages([
                    'meeting_point_id' => 'Titik temu COD yang dipilih tidak aktif.',
                ]);
            }

            $wishlist = WishlistRequest::query()
                ->where('request_id', $conversation->wishlist_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $wishlist->user_id !== (int) $buyer->user_id
                || $wishlist->status !== 'aktif'
                || $wishlist->expired_at?->isPast()
            ) {
                throw ValidationException::withMessages([
                    'negotiation_id' => 'Permintaan wishlist ini sudah tidak aktif.',
                ]);
            }

            if ($offer->transactionItem()->exists()) {
                throw ValidationException::withMessages([
                    'negotiation_id' => 'Kesepakatan ini sudah digunakan untuk transaksi.',
                ]);
            }

            $price = (int) $offer->offered_price;
            $transaction = Transaction::create([
                'buyer_id' => $buyer->user_id,
                'seller_id' => $conversation->seller_id,
                'meeting_point_id' => $meetingPoint->meeting_point_id,
                'subtotal_price' => $price,
                'service_fee' => 0,
                'agreed_discount' => 0,
                'total_price' => $price,
                'payment_method' => 'cod',
                'transaction_status' => 'menunggu',
                'buyer_notes' => $buyerNotes,
                'created_at' => now(),
            ]);

            $transaction->items()->create([
                'product_id' => null,
                'wishlist_request_id' => $wishlist->request_id,
                'item_name_snapshot' => $offer->offered_item_name,
                'negotiation_id' => $offer->negotiation_id,
                'quantity' => 1,
                'agreed_price' => $price,
                'subtotal' => $price,
            ]);

            $wishlist->update(['status' => 'terpenuhi']);

            UserNotification::create([
                'user_id' => $conversation->seller_id,
                'product_id' => null,
                'negotiation_id' => $offer->negotiation_id,
                'role' => 'penjual',
                'notification_type' => 'transaksi',
                'title' => 'Pesanan wishlist masuk — '.$offer->offered_item_name,
                'message' => $buyer->name.' membuat pesanan COD untuk "'.$offer->offered_item_name.'" seharga Rp '.number_format($price, 0, ',', '.').' di '.$meetingPoint->point_name.'.',
                'offered_price' => $price,
            ]);

            UserNotification::create([
                'user_id' => $buyer->user_id,
                'product_id' => null,
                'negotiation_id' => $offer->negotiation_id,
                'role' => 'pembeli',
                'notification_type' => 'transaksi',
                'title' => 'Pesanan berhasil dibuat — '.$offer->offered_item_name,
                'message' => 'Pesanan COD untuk "'.$offer->offered_item_name.'" (Rp '.number_format($price, 0, ',', '.').') berhasil dibuat.',
                'offered_price' => $price,
            ]);

            return $transaction;
        });
    }

    public function confirmSchedule(User $user, Transaction $transaction, ?string $scheduledAt = null): Transaction
    {
        if ((int) $transaction->seller_id !== (int) $user->user_id && ! $user->isAdmin()) {
            throw new AccessDeniedHttpException('Hanya penjual yang dapat mengonfirmasi jadwal COD.');
        }

        $transaction->update([
            'transaction_status' => 'dijadwalkan',
            'scheduled_at' => $scheduledAt ? now()->parse($scheduledAt) : now(),
        ]);

        $item = $transaction->items()->with('product')->first();
        $productName = $item?->item_name_snapshot ?? $item?->product?->product_name ?? 'Barang';

        UserNotification::create([
            'user_id' => $transaction->buyer_id,
            'product_id' => $item?->product_id,
            'negotiation_id' => $item?->negotiation_id,
            'role' => 'pembeli',
            'notification_type' => 'transaksi',
            'title' => 'Jadwal COD dikonfirmasi — '.$productName,
            'message' => 'Penjual telah mengonfirmasi jadwal COD untuk "'.$productName.'". Siapkan uang tunai saat bertemu di titik COD.',
        ]);

        return $transaction;
    }

    public function completeTransaction(User $user, Transaction $transaction): Transaction
    {
        if ((int) $transaction->seller_id !== (int) $user->user_id
            && (int) $transaction->buyer_id !== (int) $user->user_id
            && ! $user->isAdmin()
        ) {
            throw new AccessDeniedHttpException('Akses tidak diizinkan.');
        }

        $transaction->update([
            'transaction_status' => 'selesai',
            'completed_at' => now(),
        ]);

        $item = $transaction->items()->with('product')->first();
        $productName = $item?->item_name_snapshot ?? $item?->product?->product_name ?? 'Barang';

        UserNotification::create([
            'user_id' => $transaction->buyer_id,
            'product_id' => $item?->product_id,
            'negotiation_id' => $item?->negotiation_id,
            'role' => 'pembeli',
            'notification_type' => 'transaksi',
            'title' => 'Transaksi selesai — '.$productName,
            'message' => 'Transaksi COD untuk "'.$productName.'" telah selesai. Terima kasih telah berbelanja di Seklov!',
        ]);

        UserNotification::create([
            'user_id' => $transaction->seller_id,
            'product_id' => $item?->product_id,
            'negotiation_id' => $item?->negotiation_id,
            'role' => 'penjual',
            'notification_type' => 'transaksi',
            'title' => 'Barang berhasil terjual — '.$productName,
            'message' => '"'.$productName.'" telah berhasil terjual lewat COD. Transaksi dinyatakan selesai.',
        ]);

        return $transaction;
    }

    public function cancelTransaction(User $user, Transaction $transaction): Transaction
    {
        if ((int) $transaction->seller_id !== (int) $user->user_id
            && (int) $transaction->buyer_id !== (int) $user->user_id
            && ! $user->isAdmin()
        ) {
            throw new AccessDeniedHttpException('Akses tidak diizinkan.');
        }

        if ($transaction->transaction_status === 'selesai') {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi yang sudah selesai tidak dapat dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($user, $transaction): Transaction {
            $lockedTransaction = Transaction::query()
                ->where('transaction_id', $transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->transaction_status === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'transaction' => 'Pesanan ini sudah dibatalkan.',
                ]);
            }

            if ($lockedTransaction->transaction_status === 'selesai') {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi yang sudah selesai tidak dapat dibatalkan.',
                ]);
            }

            $buyerCancelled = (int) $lockedTransaction->buyer_id === (int) $user->user_id;
            $items = $lockedTransaction->items()->orderBy('product_id')->get();
            $products = Product::query()
                ->whereIn('product_id', $items->pluck('product_id'))
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $lockedTransaction->update([
                'transaction_status' => 'dibatalkan',
            ]);

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if ($product) {
                    $product->stock += $item->quantity;
                    $product->update([
                        'stock' => $product->stock,
                        'status' => 'tersedia',
                    ]);
                }

                if ($item->product_id === null && $item->wishlist_request_id !== null) {
                    $wishlist = WishlistRequest::query()
                        ->where('request_id', $item->wishlist_request_id)
                        ->lockForUpdate()
                        ->first();

                    if ($wishlist?->status === 'terpenuhi') {
                        $wishlist->update([
                            'status' => $wishlist->expired_at?->isPast() ? 'kedaluwarsa' : 'aktif',
                        ]);
                    }
                }
            }

            if ($buyerCancelled) {
                $item = $items->first();
                $productName = $item?->item_name_snapshot ?? $item?->product?->product_name ?? 'Barang';

                UserNotification::create([
                    'user_id' => $lockedTransaction->seller_id,
                    'product_id' => $item?->product_id,
                    'negotiation_id' => $item?->negotiation_id,
                    'role' => 'penjual',
                    'notification_type' => 'transaksi',
                    'title' => 'Pesanan dibatalkan — '.$productName,
                    'message' => $user->name.' membatalkan pesanan untuk "'.$productName.'".',
                ]);
            }

            return $lockedTransaction;
        });
    }
}
