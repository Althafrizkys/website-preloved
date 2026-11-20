<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isOwner($user, $product);
    }

    public function updateStatus(User $user, Product $product): bool
    {
        return $this->isOwner($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $this->isOwner($user, $product);
    }

    private function isOwner(User $user, Product $product): bool
    {
        return (int) $product->seller_id === (int) $user->user_id;
    }
}
