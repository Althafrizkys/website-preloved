<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Negotiation;
use App\Models\Product;
use App\Models\User;

class NegotiationPolicy
{
    public function create(User $user, Product $product): bool
    {
        return ! $user->isAdmin()
            && (int) $product->seller_id !== (int) $user->user_id
            && $product->status === 'tersedia'
            && $product->stock > 0
            && $product->is_negotiable;
    }

    public function viewConversation(User $user, Conversation $conversation): bool
    {
        return (int) $conversation->buyer_id === (int) $user->user_id
            || (int) $conversation->seller_id === (int) $user->user_id;
    }

    public function viewOffers(User $user, Product $product): bool
    {
        return (int) $product->seller_id === (int) $user->user_id;
    }

    public function respond(User $user, Negotiation $negotiation): bool
    {
        $negotiation->loadMissing(['conversation.product']);

        return (int) $negotiation->conversation->seller_id === (int) $user->user_id
            && $negotiation->status === 'menunggu'
            && $negotiation->conversation->product->status === 'tersedia';
    }
}
