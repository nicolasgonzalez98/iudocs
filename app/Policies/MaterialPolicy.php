<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Regla base: el dueño del material o un admin.
     * (Acá centralizamos el permiso; el día de mañana —moderadores por
     * materia, roles granulares, etc.— se cambia sólo este archivo.)
     */
    public function manage(User $user, Material $material): bool
    {
        return $user->isAdmin() || $material->user_id === $user->id;
    }

    public function update(User $user, Material $material): bool
    {
        return $this->manage($user, $material);
    }

    public function delete(User $user, Material $material): bool
    {
        return $this->manage($user, $material);
    }

    public function move(User $user, Material $material): bool
    {
        return $this->manage($user, $material);
    }
}
