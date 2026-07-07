<?php

namespace App\Policies;

use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use App\Models\User;

class PermohonanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin() && $ability !== 'create') {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Permohonan $permohonan): bool
    {
        if ($user->isVerifikator()) {
            return true;
        }

        return $user->isPemohon() && $permohonan->pemohon_id === $user->id;
    }

    public function create(User $user, Permohonan $permohonan): bool
    {
        if (! $user->isPemohon() || $permohonan->pemohon_id !== $user->id) {
            return false;
        }

        return in_array($permohonan->status, [
            StatusPermohonan::Draft,
            StatusPermohonan::Ditolak,
        ]);
    }

    public function update(User $user, Permohonan $permohonan): bool
    {
        if (! $user->isPemohon() || $permohonan->pemohon_id !== $user->id) {
            return false;
        }

        return in_array($permohonan->status, [
            StatusPermohonan::Draft,
            StatusPermohonan::Ditolak,
        ], true);
    }

    /**
     * Soft delete permohonan hanya boleh dilakukan verifikator
     * (untuk permohonan yang sudah diajukan, bukan draft).
     */
    public function delete(User $user, Permohonan $permohonan): bool
    {
        return $user->isVerifikator()
            && $permohonan->status !== StatusPermohonan::Draft;
    }

    /**
     * Hapus permanen hanya untuk draft milik pemohon sendiri.
     */
    public function forceDelete(User $user, Permohonan $permohonan): bool
    {
        return $user->isPemohon()
            && $permohonan->pemohon_id === $user->id
            && $permohonan->status === StatusPermohonan::Draft;
    }

    /**
     * Verifikator dapat memproses permohonan yang menunggu verifikasi,
     * maupun menyelesaikan permohonan yang sedang diproses.
     */
    public function verifikasi(User $user, Permohonan $permohonan): bool
    {
        return $user->isVerifikator() && in_array($permohonan->status, [
            StatusPermohonan::MenungguVerifikasi,
            StatusPermohonan::Diproses,
        ], true);
    }
}
