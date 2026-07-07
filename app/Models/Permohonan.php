<?php

namespace App\Models;

use App\Enums\JenisPermohonan;
use App\Enums\StatusPermohonan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permohonan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'permohonan';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'nomor_permohonan',
        'pemohon_id',
        'jenis_permohonan',
        'status',
        'tanggal_pengajuan',
        'tanggal_verifikasi',
        'verifikator_id',
        'catatan_verifikator',
        'jumlah_pengajuan',
    ];

    protected function casts(): array
    {
        return [
            'jenis_permohonan' => JenisPermohonan::class,
            'status' => StatusPermohonan::class,
            'tanggal_pengajuan' => 'datetime',
            'tanggal_verifikasi' => 'datetime',
            'jumlah_pengajuan' => 'integer',
        ];
    }

    // ===== Relasi =====

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPermohonan::class, 'permohonan_id');
    }

    public function riwayatVerifikasi(): HasMany
    {
        return $this->hasMany(RiwayatVerifikasi::class, 'permohonan_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'permohonan_id');
    }

    // ===== Helper =====

    public function dokumenTerbaru()
    {
        return $this->dokumen()
            ->orderByDesc('versi')
            ->get()
            ->unique('jenis_dokumen');
    }
}
