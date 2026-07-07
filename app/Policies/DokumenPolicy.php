<?php

namespace App\Policies;

use App\Models\DokumenPermohonan;
use App\Models\User;

class DokumenPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, DokumenPermohonan $dokumen): bool
    {
        if ($user->isVerifikator()) {
            return true;
        }

        return $user->isPemohon() && $dokumen->permohonan->pemohon_id === $user->id;
    }

    public function download(User $user, DokumenPermohonan $dokumen): bool
    {
        return $this->view($user, $dokumen);
    }
}
