<?php

namespace App\Policies;

use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiPolicy
{
    public function view(User $user, Notifikasi $notifikasi): bool
    {
        return $notifikasi->user_id === $user->id;
    }

    public function update(User $user, Notifikasi $notifikasi): bool
    {
        return $notifikasi->user_id === $user->id;
    }
}
