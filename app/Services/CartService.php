<?php

namespace App\Services;

use App\Models\CampusMeetingPoint;
use App\Models\CartItem;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Tambahkan barang ke keranjang. Barang yang sudah ada akan bertambah jumlahnya.
     */
    public function add(User $user, Product $product, int $quantity = 1): CartItem
    {
        if ((int) $product->seller_id === (int) $user->user_id) {
            throw ValidationException::withMessages([
                'product_id' => 'Kamu tidak dapat memasukkan barang milikmu sendiri ke keranjang.',
            ]);
        }

        if ($product->status !== 'tersedia' || $product->stock < 1) {
            throw ValidationException::withMessages([
                'product_id' => 'Barang ini sudah terjual atau stoknya habis.',
            ]);
        }

        $item = CartItem::firstOrNew([
            'user_id' => $user->user_id,
            'product_id' => $product->product_id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + max(1, $quantity);

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah di keranjang melebihi stok yang tersedia ('.$product->stock.' unit).',
            ]);
        }

        $item->quantity = $newQuantity;
        $item->save();

        return $item;
    }

    /**
     * Ubah jumlah satu barang di keranjang (dibatasi stok).
     */
    public function updateQuantity(CartItem $item, int $quantity): CartItem
    {
        $stock = (int) $item->product->stock;

        if ($quantity < 1 || $quantity > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah harus antara 1 dan '.$stock.' unit.',
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $item;
    }

    /**
     * Harga satuan untuk pembeli: harga nego yang sudah diterima, atau harga normal.
     */
    public function unitPrice(User $buyer, Product $product): int
    {
        $conversation = Conversation::query()
            ->where('product_id', $product->product_id)
            ->where('buyer_id', $buyer->user_id)
            ->where('seller_id', $product->seller_id)
            ->whereNull('wishlist_request_id')
            ->first();

        $agreedOffer = $conversation?->agreedOffer();

        return $agreedOffer ? (int) $agreedOffer->offered_price : (int) $product->price;
    }

    /**
     * Ambil barang keranjang yang dipilih dan pastikan semuanya bisa di-checkout bersama.
     *
     * @param  array<int, int|string>  $itemIds
     * @return Collection<int, CartItem>
     */
    public function selectedItems(User $user, array $itemIds): Collection
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        $items = CartItem::query()
            ->where('user_id', $user->user_id)
            ->whereIn('cart_item_id', $itemIds)
            ->with([
                'product.seller:user_id,name,campus',
                'product.media' => fn ($query) => $query->where('media_type', 'foto')->orderBy('display_order'),
            ])
            ->orderBy('cart_item_id')
            ->get();

        if ($items->isEmpty() || $items->count() !== count($itemIds)) {
            throw ValidationException::withMessages([
                'items' => 'Barang yang dipilih tidak ditemukan di keranjangmu.',
            ]);
        }

        if ($items->pluck('product.seller_id')->unique()->count() > 1) {
            throw ValidationException::withMessages([
                'items' => 'Checkout hanya bisa untuk barang dari satu penjual. Buat transaksi terpisah untuk penjual lain.',
            ]);
        }

        foreach ($items as $item) {
            $this->assertPurchasable($user, $item->product, $item->quantity);
        }

        return $items;
    }

    /**
     * Buat satu transaksi COD untuk beberapa barang dari satu penjual.
     *
     * @param  array<int, int|string>  $itemIds
     */
    public function checkout(User $buyer, array $itemIds, CampusMeetingPoint $meetingPoint, ?string $buyerNotes = null): Transaction
    {
        if (! $meetingPoint->is_active) {
            throw ValidationException::withMessages([
                'meeting_point_id' => 'Titik temu COD yang dipilih tidak aktif.',
            ]);
        }

        return DB::transaction(function () use ($buyer, $itemIds, $meetingPoint, $buyerNotes): Transaction {
            $items = $this->selectedItems($buyer, $itemIds);

            $products = Product::query()
                ->whereIn('product_id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $sellerId = (int) $items->first()->product->seller_id;
            $lines = [];
            $subtotal = 0;
            $discount = 0;

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                $this->assertPurchasable($buyer, $product, $item->quantity);

                $unitPrice = $this->unitPrice($buyer, $product);
                $lineSubtotal = $unitPrice * $item->quantity;

                $lines[] = [$product, $item->quantity, $unitPrice, $lineSubtotal];
                $subtotal += $lineSubtotal;
                $discount += max(0, (int) $product->price - $unitPrice) * $item->quantity;
            }

            $transaction = Transaction::create([
                'buyer_id' => $buyer->user_id,
                'seller_id' => $sellerId,
                'meeting_point_id' => $meetingPoint->meeting_point_id,
                'subtotal_price' => $subtotal,
                'service_fee' => 0,
                'agreed_discount' => $discount,
                'total_price' => $subtotal,
                'payment_method' => 'cod',
                'transaction_status' => 'menunggu',
                'buyer_notes' => $buyerNotes,
            ]);

            foreach ($lines as [$product, $quantity, $unitPrice, $lineSubtotal]) {
                $transaction->items()->create([
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'agreed_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $product->stock -= $quantity;
                $product->update([
                    'stock' => $product->stock,
                    'status' => $product->stock === 0 ? 'terjual' : 'tersedia',
                ]);
            }

            CartItem::query()
                ->where('user_id', $buyer->user_id)
                ->whereIn('cart_item_id', $items->pluck('cart_item_id'))
                ->delete();

            $firstProduct = $lines[0][0];
            $itemSummary = count($lines) === 1
                ? '"'.$firstProduct->product_name.'"'
                : '"'.$firstProduct->product_name.'" dan '.(count($lines) - 1).' barang lain';
            $formattedTotal = 'Rp '.number_format($subtotal, 0, ',', '.');

            UserNotification::create([
                'user_id' => $sellerId,
                'product_id' => $firstProduct->product_id,
                'role' => 'penjual',
                'notification_type' => 'transaksi',
                'title' => 'Pembelian masuk — '.$firstProduct->product_name.(count($lines) > 1 ? ' +'.(count($lines) - 1) : ''),
                'message' => $buyer->name.' membeli '.$itemSummary.' seharga '.$formattedTotal.' lewat COD di '.$meetingPoint->point_name.'.',
                'original_price' => (int) $firstProduct->price,
                'offered_price' => (int) $lines[0][2],
            ]);

            UserNotification::create([
                'user_id' => $buyer->user_id,
                'product_id' => $firstProduct->product_id,
                'role' => 'pembeli',
                'notification_type' => 'transaksi',
                'title' => 'Pesanan berhasil dibuat — '.$firstProduct->product_name.(count($lines) > 1 ? ' +'.(count($lines) - 1) : ''),
                'message' => 'Pesananmu untuk '.$itemSummary.' ('.$formattedTotal.') berhasil dibuat. Tunggu konfirmasi jadwal COD dari penjual.',
                'original_price' => (int) $firstProduct->price,
                'offered_price' => (int) $lines[0][2],
            ]);

            return $transaction;
        });
    }

    private function assertPurchasable(User $buyer, ?Product $product, int $quantity): void
    {
        if (! $product) {
            throw ValidationException::withMessages(['items' => 'Barang di keranjang sudah tidak tersedia.']);
        }

        if ((int) $product->seller_id === (int) $buyer->user_id) {
            throw ValidationException::withMessages([
                'items' => 'Kamu tidak dapat membeli barang milikmu sendiri ("'.$product->product_name.'").',
            ]);
        }

        if ($product->status !== 'tersedia' || $product->stock < 1) {
            throw ValidationException::withMessages([
                'items' => '"'.$product->product_name.'" sudah terjual atau stoknya habis. Hapus dari keranjang untuk melanjutkan.',
            ]);
        }

        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'items' => 'Jumlah "'.$product->product_name.'" melebihi stok yang tersedia ('.$product->stock.' unit).',
            ]);
        }
    }
}
