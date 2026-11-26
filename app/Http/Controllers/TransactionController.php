<?php

namespace App\Http\Controllers;

use App\Exports\SellerTransactionsExport;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\CampusMeetingPoint;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Transaction;
use App\Services\RewardService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab', 'pembelian');

        [$start, $end] = $tab === 'penjualan'
            ? $this->resolveDateRange($request)
            : [null, null];

        $query = $tab === 'penjualan'
            ? $user->sellerTransactions()->createdBetween($start, $end)
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
            'startDate' => $start?->format('Y-m-d'),
            'endDate' => $end?->format('Y-m-d'),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        [$start, $end] = $this->resolveDateRange($request);

        $filename = match (true) {
            $start !== null && $end !== null => 'riwayat-penjualan-cod-'.$start->format('Ymd').'-'.$end->format('Ymd').'.xlsx',
            $start !== null => 'riwayat-penjualan-cod-mulai-'.$start->format('Ymd').'.xlsx',
            $end !== null => 'riwayat-penjualan-cod-sampai-'.$end->format('Ymd').'.xlsx',
            default => 'riwayat-penjualan-cod-'.now()->format('Ymd').'.xlsx',
        };

        return Excel::download(
            new SellerTransactionsExport((int) $user->user_id, $start, $end),
            $filename,
        );
    }

    /**
     * Parse optional date range query parameters for sales listing and export.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $endRules = ['nullable', 'date'];
        if ($request->filled('start')) {
            $endRules[] = 'after_or_equal:start';
        }

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => $endRules,
        ], [
            'start.date' => 'Format tanggal awal tidak valid.',
            'end.date' => 'Format tanggal akhir tidak valid.',
            'end.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);

        $start = ! empty($validated['start'])
            ? Carbon::parse($validated['start'])->startOfDay()
            : null;

        $end = ! empty($validated['end'])
            ? Carbon::parse($validated['end'])->endOfDay()
            : null;

        return [$start, $end];
    }

    public function chat(Request $request, Transaction $transaction): RedirectResponse
    {
        $userId = (int) $request->user()->user_id;

        abort_unless(
            in_array($userId, [(int) $transaction->buyer_id, (int) $transaction->seller_id], true),
            403,
        );

        $productId = $transaction->items()->value('product_id');

        abort_unless($productId, 404);

        $conversation = Conversation::firstOrCreate([
            'product_id' => $productId,
            'buyer_id' => $transaction->buyer_id,
            'seller_id' => $transaction->seller_id,
        ]);

        return redirect()->route('chat.show', $conversation);
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
        $totalPrice = $subtotal;

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

        $hasRated = Rating::where('transaction_id', $transaction->transaction_id)
            ->where('reviewer_id', $user->user_id)
            ->exists();

        return view('transactions.show', [
            'transaction' => $transaction,
            'isBuyer' => $isBuyer,
            'isSeller' => $isSeller,
            'hasRated' => $hasRated,
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

    public function storeRating(Request $request, Transaction $transaction, RewardService $rewardService): RedirectResponse
    {
        if (! in_array($transaction->transaction_status, ['selesai', 'completed'])) {
            return back()->with('error', 'Rating hanya dapat diberikan setelah transaksi selesai.');
        }

        $userId = (int) $request->user()->user_id;

        if ($userId !== (int) $transaction->buyer_id && $userId !== (int) $transaction->seller_id) {
            abort(403, 'Kamu tidak memiliki akses untuk memberi rating pada transaksi ini.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:500'],
        ]);

        $reviewedUserId = ($userId === (int) $transaction->buyer_id)
            ? (int) $transaction->seller_id
            : (int) $transaction->buyer_id;

        $alreadyRated = Rating::where('transaction_id', $transaction->transaction_id)
            ->where('reviewer_id', $userId)
            ->exists();

        if ($alreadyRated) {
            return back()->with('error', 'Kamu sudah memberikan rating untuk transaksi ini.');
        }

        Rating::create([
            'transaction_id'   => $transaction->transaction_id,
            'reviewer_id'      => $userId,
            'reviewed_user_id' => $reviewedUserId,
            'stars'            => $validated['rating'],
            'review'           => $validated['review'] ?? null,
        ]);

        // Tambah poin reward untuk penjual dan pembeli
        // Tambah poin reward untuk penjual dan pembeli
        $pointsEarned = 25;

        $rewardService->giveReward(
            userId: $userId,
            points: $pointsEarned,
            type: 'poin',
            description: "Memberikan rating pada Transaksi #{$transaction->transaction_id}"
        );

        $rewardService->giveReward(
            userId: $reviewedUserId,
            points: $pointsEarned,
            type: 'poin',
            description: "Transaksi #{$transaction->transaction_id} selesai dan menerima rating"
        );

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('reward_popup', [
                'points'  => $pointsEarned,
                'title'   => 'Selamat! Kamu Mendapat Poin Reward!',
                'message' => "Rating berhasil dikirim! Baik penjual maupun pembeli mendapatkan +{$pointsEarned} poin reward.",
            ]);
    }
}