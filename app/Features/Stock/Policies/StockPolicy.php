<?php

namespace App\Features\Stock\Policies;

use App\Features\Stock\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->isAdmin() || $user->id === $stock->user_id;
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function update(User $user, Stock $stock): bool
    {
        return ! $user->isAdmin() && $user->id === $stock->user_id;
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $this->update($user, $stock);
    }
}
