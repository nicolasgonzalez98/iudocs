<?php

namespace App\Policies;

use App\Models\Subcarpeta;
use App\Models\User;

class SubcarpetaPolicy
{
    /** Solo la admin arma/gestiona la estructura de carpetas. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Subcarpeta $subcarpeta): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Subcarpeta $subcarpeta): bool
    {
        return $user->isAdmin();
    }
}
