<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
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
}