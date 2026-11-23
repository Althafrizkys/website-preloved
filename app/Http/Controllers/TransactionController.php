<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\CampusMeetingPoint;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab', 'pembelian');

        $query = $tab === 'penjualan'
            ? $user->sellerTransactions()
            : $user->buyerTransactions();

        $transactions = $query
            ->with([
                'meetingPoint',
                'buyer:user_id,name,campus,email',
                'seller:user_id,name,campus,email',
                'items.product.media',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('transaction_id')
            ->paginate(10)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'currentTab' => $tab,
        ]);
    }

    public function create(Request $request, Product $product): View|RedirectResponse
    {
        $user = $request->user();

        if ((int) $product->seller_id === (int) $user->user_id) {
            return redirect()
                ->route('products.show', $product)
                ->with('error', 'Kamu tidak dapat membeli barang milikmu sendiri.');
        }

        if ($product->status !== 'tersedia' || $product->stock < 1) {
            return redirect()
                ->route('products.show', $product)
                ->with('error', 'Stok barang ini sudah habis atau tidak tersedia.');
        }

        $requestedQuantity = filter_var(
            $request->query('quantity', 1),
            FILTER_VALIDATE_INT,
        );

        if ($requestedQuantity === false || $requestedQuantity < 1 || $requestedQuantity > $product->stock) {
            return redirect()
                ->route('products.show', $product)
                ->with('error', 'Jumlah pembelian melebihi stok yang tersedia.');
        }

        $product->loadMissing([
            'media',
            'category',
            'seller:user_id,name,campus',
        ]);

        $meetingPoints = CampusMeetingPoint::active()
            ->orderBy('campus')
            ->orderBy('point_name')
            ->get();

        $unsikaPoints = $meetingPoints->where('campus', 'UNSIKA')->values();
        $ubpPoints = $meetingPoints->where('campus', 'UBP')->values();

        // Cari tawaran yang disepakati (jika ada)
        $conversation = Conversation::query()
            ->where('product_id', $product->product_id)
            ->where('buyer_id', $user->user_id)
            ->where('seller_id', $product->seller_id)
            ->first();

        $agreedOffer = $conversation?->agreedOffer();

        $negotiationId = $request->query('negotiation_id');
        $activeNegotiation = null;
        if ($negotiationId) {
            if (! $agreedOffer || (int) $agreedOffer->negotiation_id !== (int) $negotiationId) {
                return redirect()
                    ->route('products.show', $product)
                    ->with('error', 'Tawaran nego tidak berlaku untuk barang ini.');
            }

            $activeNegotiation = $agreedOffer;
        }

        $unitPrice = $activeNegotiation?->status === 'diterima'
            ? (int) $activeNegotiation->offered_price
            : ($agreedOffer ? (int) $agreedOffer->offered_price : (int) $product->price);

        $defaultQuantity = $requestedQuantity;
        $subtotal = $unitPrice * $defaultQuantity;
        $totalPrice = $subtotal ;

        // Default kampus mengikuti kampus penjual atau user
        $defaultCampus = in_array($user->campus, ['UNSIKA', 'UBP']) ? $user->campus : 'UNSIKA';

        return view('transactions.create', [
            'product' => $product,
            'unsikaPoints' => $unsikaPoints,
            'ubpPoints' => $ubpPoints,
            'defaultCampus' => $defaultCampus,
            'unitPrice' => $unitPrice,
            'defaultQuantity' => $defaultQuantity,
            'subtotal' => $subtotal,
            'totalPrice' => $totalPrice,
            'agreedOffer' => $agreedOffer,
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $product = Product::findOrFail($data['product_id']);
        $meetingPoint = CampusMeetingPoint::findOrFail($data['meeting_point_id']);

        $transaction = $this->transactionService->createCodTransaction(
            buyer: $user,
            product: $product,
            meetingPoint: $meetingPoint,
            quantity: (int) $data['quantity'],
            buyerNotes: $data['buyer_notes'] ?? null,
            negotiationId: isset($data['negotiation_id']) ? (int) $data['negotiation_id'] : null,
        );

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Pesanan COD berhasil dibuat! Silakan hubungi penjual dan pantau status transaksi.');
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $user = $request->user();

        if (
            (int) $transaction->buyer_id !== (int) $user->user_id
            && (int) $transaction->seller_id !== (int) $user->user_id
            && ! $user->isAdmin()
        ) {
            abort(403, 'Akses transaksi tidak diizinkan.');
        }

        $transaction->loadMissing([
            'meetingPoint',
            'buyer:user_id,name,campus,cohort',
            'seller:user_id,name,campus,cohort',
            'items.product.media',
        ]);

        $isBuyer = (int) $transaction->buyer_id === (int) $user->user_id;
        $isSeller = (int) $transaction->seller_id === (int) $user->user_id;

        return view('transactions.show', [
            'transaction' => $transaction,
            'isBuyer' => $isBuyer,
            'isSeller' => $isSeller,
        ]);
    }

    public function confirm(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->transactionService->confirmSchedule($request->user(), $transaction);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Jadwal COD berhasil dikonfirmasi.');
    }

    public function complete(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->transactionService->completeTransaction($request->user(), $transaction);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi COD telah ditandai selesai.');
    }

    public function cancel(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->transactionService->cancelTransaction($request->user(), $transaction);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Pesanan COD berhasil dibatalkan.');
    }
}
