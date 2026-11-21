<?php

namespace App\Services;

use App\Models\CampusMeetingPoint;
use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
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

            if (! $meetingPoint->is_active) {
                throw ValidationException::withMessages([
                    'meeting_point_id' => 'Titik temu COD yang dipilih tidak aktif.',
                ]);
            }

            $unitPrice = (int) $lockedProduct->price;
            $agreedDiscount = 0;

            // Jika ada tawaran nego yang disepakati untuk pembeli ini
            $conversation = Conversation::query()
                ->where('product_id', $lockedProduct->product_id)
                ->where('buyer_id', $buyer->user_id)
                ->where('seller_id', $lockedProduct->seller_id)
                ->first();

            $agreedOffer = $conversation?->agreedOffer();

            if ($negotiationId) {
                $offer = Negotiation::find($negotiationId);
                if ($offer && $offer->status === 'diterima' && (int) $offer->conversation?->buyer_id === (int) $buyer->user_id) {
                    $unitPrice = (int) $offer->offered_price;
                    $agreedDiscount = max(0, (int) $lockedProduct->price - $unitPrice);
                }
            } elseif ($agreedOffer) {
                $unitPrice = (int) $agreedOffer->offered_price;
                $agreedDiscount = max(0, (int) $lockedProduct->price - $unitPrice);
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

            // Tandai produk sebagai terjual
            $lockedProduct->update(['status' => 'terjual']);

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
        $productName = $item?->product?->product_name ?? 'Barang';

        UserNotification::create([
            'user_id' => $transaction->buyer_id,
            'product_id' => $item?->product_id,
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
        $productName = $item?->product?->product_name ?? 'Barang';

        UserNotification::create([
            'user_id' => $transaction->buyer_id,
            'product_id' => $item?->product_id,
            'role' => 'pembeli',
            'notification_type' => 'transaksi',
            'title' => 'Transaksi selesai — '.$productName,
            'message' => 'Transaksi COD untuk "'.$productName.'" telah selesai. Terima kasih telah berbelanja di Seklov!',
        ]);

        UserNotification::create([
            'user_id' => $transaction->seller_id,
            'product_id' => $item?->product_id,
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
            $buyerCancelled = (int) $transaction->buyer_id === (int) $user->user_id
                && $transaction->transaction_status !== 'dibatalkan';
            $items = $transaction->items()->with('product')->get();

            $transaction->update([
                'transaction_status' => 'dibatalkan',
            ]);

            // Kembalikan produk menjadi tersedia
            foreach ($items as $item) {
                $item->product?->update(['status' => 'tersedia']);
            }

            if ($buyerCancelled) {
                $item = $items->first();
                $productName = $item?->product?->product_name ?? 'Barang';

                UserNotification::create([
                    'user_id' => $transaction->seller_id,
                    'product_id' => $item?->product_id,
                    'role' => 'penjual',
                    'notification_type' => 'transaksi',
                    'title' => 'Pesanan dibatalkan — '.$productName,
                    'message' => $user->name.' membatalkan pesanan untuk "'.$productName.'".',
                ]);
            }

            return $transaction;
        });
    }
}
