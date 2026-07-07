<?php

namespace App\Models;

use App\Enums\RoleUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'nama_lengkap',
        'nip',
        'nik',
        'email',
        'password',
        'no_hp',
        'pangkat_gol',
        'jabatan',
        'instansi',
        'unit_kerja',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleUser::class,
        ];
    }

    // ===== Relasi =====

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'pemohon_id');
    }

    public function permohonanDiverifikasi(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'verifikator_id');
    }

    public function riwayatVerifikasi(): HasMany
    {
        return $this->hasMany(RiwayatVerifikasi::class, 'verifikator_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function templateDokumen(): HasMany
    {
        return $this->hasMany(TemplateDokumen::class, 'uploaded_by');
    }

    // ===== Helper Role =====

    public function isPemohon(): bool
    {
        return $this->role === RoleUser::Pemohon;
    }

    public function isVerifikator(): bool
    {
        return $this->role === RoleUser::Verifikator;
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleUser::Admin;
    }

    // ===== Helper Aktivasi =====

    public function isAktif(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function aktifkan(): void
    {
        $this->forceFill(['email_verified_at' => now()])->save();
    }

    public function nonaktifkan(): void
    {
        $this->forceFill(['email_verified_at' => null])->save();
    }

    public function hasRiwayat(): bool
    {
        if ($this->isPemohon()) {
            return $this->permohonan()->withTrashed()->exists();
        }

        if ($this->isVerifikator()) {
            return $this->riwayatVerifikasi()->exists();
        }

        return false;
    }
}