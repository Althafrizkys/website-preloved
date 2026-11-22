<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return (int) $conversation->buyer_id === (int) $user->user_id
            || (int) $conversation->seller_id === (int) $user->user_id;
    }

    public function start(User $user, Product $product): bool
    {
        return ! $user->isAdmin()
            && (int) $product->seller_id !== (int) $user->user_id;
    }
}