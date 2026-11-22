<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request, null);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        return $this->render($request, $conversation);
    }

    public function start(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('start', [Conversation::class, $product]);

        $conversation = Conversation::firstOrCreate([
            'product_id' => $product->product_id,
            'buyer_id' => $request->user()->user_id,
            'seller_id' => $product->seller_id,
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $messages = $conversation->messages()
            ->where('message_id', '>', (int) $request->query('after', 0))
            ->orderBy('message_id')
            ->get(['message_id', 'sender_id', 'message_text', 'sent_at', 'read_at']);

        return response()->json(['messages' => $messages]);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->user_id,
            'message_text' => $request->validated('message_text'),
        ]);
        $conversation->touch();

        return response()->json(['message' => $message->fresh()], 201);
    }

    private function render(Request $request, ?Conversation $active): View
    {
        $user = $request->user();
        $userId = $user->user_id;
        $filter = in_array($request->query('filter'), ['penjual', 'pembeli', 'belum'], true)
            ? $request->query('filter')
            : 'semua';
        $search = trim((string) $request->query('q', ''));

        if ($active) {
            $active->messages()
                ->whereNull('read_at')
                ->where('sender_id', '!=', $userId)
                ->update(['read_at' => now()]);
        }

        $unreadForUser = fn ($query) => $query->whereNull('read_at')->where('sender_id', '!=', $userId);

        $conversations = Conversation::query()
            ->where(fn ($query) => $query->where('buyer_id', $userId)->orWhere('seller_id', $userId))
            ->when($filter === 'penjual', fn ($query) => $query->where('seller_id', $userId))
            ->when($filter === 'pembeli', fn ($query) => $query->where('buyer_id', $userId))
            ->when($filter === 'belum', fn ($query) => $query->whereHas('messages', $unreadForUser))
            ->when($search !== '', function ($query) use ($search, $userId) {
                $like = '%'.$search.'%';

                $query->where(function ($inner) use ($like, $userId) {
                    $inner->whereHas('product', fn ($product) => $product->where('product_name', 'like', $like))
                        ->orWhereHas('buyer', fn ($person) => $person->where('user_id', '!=', $userId)->where('name', 'like', $like))
                        ->orWhereHas('seller', fn ($person) => $person->where('user_id', '!=', $userId)->where('name', 'like', $like));
                });
            })
            ->with([
                'buyer:user_id,name,campus,profile_photo',
                'seller:user_id,name,campus,profile_photo',
                'product' => fn ($product) => $product->with([
                    'media' => fn ($media) => $media->where('media_type', 'foto')->orderBy('display_order'),
                ]),
                'latestMessage',
            ])
            ->withCount(['messages as unread_count' => $unreadForUser])
            ->orderByDesc('updated_at')
            ->orderByDesc('conversation_id')
            ->get();

        $timeline = collect();

        if ($active) {
            $active->load([
                'buyer:user_id,name,campus,profile_photo',
                'seller:user_id,name,campus,profile_photo',
                'product' => fn ($product) => $product->with([
                    'media' => fn ($media) => $media->where('media_type', 'foto')->orderBy('display_order'),
                ]),
                'messages' => fn ($messages) => $messages->orderBy('message_id'),
                'negotiations' => fn ($offers) => $offers->orderBy('created_at')->orderBy('negotiation_id'),
            ]);

            $timeline = $active->messages
                ->map(fn ($message) => ['type' => 'message', 'at' => $message->sent_at, 'item' => $message])
                ->concat($active->negotiations->map(fn ($offer) => ['type' => 'offer', 'at' => $offer->created_at, 'item' => $offer]))
                ->sortBy(fn ($entry) => $entry['at']?->getTimestamp() ?? 0)
                ->values();
        }

        return view('chat.index', [
            'conversations' => $conversations,
            'active' => $active,
            'timeline' => $timeline,
            'filter' => $filter,
            'search' => $search,
        ]);
    }
}