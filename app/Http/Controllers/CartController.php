<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartCheckoutRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Models\CampusMeetingPoint;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $items = CartItem::query()
            ->where('user_id', $user->user_id)
            ->with([
                'product.seller:user_id,name,campus',
                'product.media' => fn ($query) => $query->where('media_type', 'foto')->orderBy('display_order'),
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('cart_item_id')
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null)
            ->each(function (CartItem $item) use ($user): void {
                $item->unit_price = $this->cartService->unitPrice($user, $item->product);
                $item->is_available = $item->product->status === 'tersedia'
                    && $item->product->stock >= $item->quantity
                    && (int) $item->product->seller_id !== (int) $user->user_id;
            });

        return view('cart.index', [
            'groups' => $items->groupBy(fn (CartItem $item) => $item->product->seller_id),
            'itemCount' => $items->count(),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));

        $this->cartService->add(
            $request->user(),
            $product,
            (int) ($request->validated('quantity') ?? 1),
        );

        return back()->with('success', '"'.$product->product_name.'" ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $this->authorizeItem($request, $cartItem);

        $quantity = filter_var($request->input('quantity'), FILTER_VALIDATE_INT);

        try {
            $this->cartService->updateQuantity($cartItem->load('product'), $quantity === false ? 0 : $quantity);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return redirect()->route('cart.index')->with('error', $exception->validator->errors()->first());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'quantity' => $cartItem->quantity,
                'stock' => (int) $cartItem->product->stock,
            ]);
        }

        return redirect()->route('cart.index');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeItem($request, $cartItem);

        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Barang dihapus dari keranjang.');
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $ids = array_map('intval', (array) $request->input('items', []));

        if ($ids === []) {
            return redirect()->route('cart.index')->with('error', 'Pilih barang yang ingin dihapus.');
        }

        $deleted = CartItem::query()
            ->where('user_id', $request->user()->user_id)
            ->whereIn('cart_item_id', $ids)
            ->delete();

        return redirect()->route('cart.index')->with('success', $deleted.' barang dihapus dari keranjang.');
    }

    public function checkout(CartCheckoutRequest $request): View|RedirectResponse
    {
        $user = $request->user();

        try {
            $items = $this->cartService->selectedItems($user, $request->validated('items'));
        } catch (ValidationException $exception) {
            return redirect()->route('cart.index')->with('error', $exception->validator->errors()->first());
        }

        $items->each(function (CartItem $item) use ($user): void {
            $item->unit_price = $this->cartService->unitPrice($user, $item->product);
        });

        $meetingPoints = CampusMeetingPoint::active()
            ->orderBy('campus')
            ->orderBy('point_name')
            ->get();

        $seller = $items->first()->product->seller;

        return view('cart.checkout', [
            'items' => $items,
            'seller' => $seller,
            'unsikaPoints' => $meetingPoints->where('campus', 'UNSIKA')->values(),
            'ubpPoints' => $meetingPoints->where('campus', 'UBP')->values(),
            'defaultCampus' => in_array($user->campus, ['UNSIKA', 'UBP'], true) ? $user->campus : 'UNSIKA',
            'totalPrice' => $items->sum(fn (CartItem $item) => $item->unit_price * $item->quantity),
            'totalUnits' => $items->sum('quantity'),
        ]);
    }

    public function placeOrder(CartCheckoutRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $transaction = $this->cartService->checkout(
                $request->user(),
                $data['items'],
                CampusMeetingPoint::findOrFail($data['meeting_point_id']),
                $data['buyer_notes'] ?? null,
            );
        } catch (ValidationException $exception) {
            if ($exception->validator->errors()->has('meeting_point_id')) {
                throw $exception;
            }

            return redirect()->route('cart.index')->with('error', $exception->validator->errors()->first());
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Pesanan COD berhasil dibuat! Silakan hubungi penjual dan pantau status transaksi.');
    }

    private function authorizeItem(Request $request, CartItem $cartItem): void
    {
        abort_unless((int) $cartItem->user_id === (int) $request->user()->user_id, 403);
    }
}
