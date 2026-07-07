<?php

namespace App\Policies;

use App\Enums\RoleUser;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $aktor): bool
    {
        return $aktor->isAdmin();
    }

    public function view(User $aktor, User $target): bool
    {
        if (! $aktor->isAdmin()) {
            return false;
        }

        return $target->role !== RoleUser::Admin;
    }

    public function update(User $aktor, User $target): bool
    {
        if (! $aktor->isAdmin()) {
            return false;
        }

        if ($target->id === $aktor->id) {
            return false;
        }

        if ($target->role === RoleUser::Admin) {
            return false;
        }

        return ! $target->hasRiwayat();
    }

    public function toggleAktivasi(User $aktor, User $target): bool
    {
        if (! $aktor->isAdmin() && ! $aktor->isVerifikator()) {
            return false;
        }

        if ($target->id === $aktor->id) {
            return false;
        }

        if ($aktor->isVerifikator()) {
            return $target->isPemohon();
        }

        return $target->role !== RoleUser::Admin;
    }

    public function delete(User $aktor, User $target): bool
    {
        return $this->update($aktor, $target);
    }
}