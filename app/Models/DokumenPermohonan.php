<?php

namespace App\Models;

use App\Enums\JenisDokumen;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenPermohonan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'dokumen_permohonan';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'permohonan_id',
        'jenis_dokumen',
        'nama_file',
        'path_file',
        'ukuran_file',
        'mime_type',
        'versi',
    ];

    protected function casts(): array
    {
        return [
            'jenis_dokumen' => JenisDokumen::class,
            'ukuran_file' => 'integer',
            'versi' => 'integer',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ===== Relasi =====

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    // ===== Helper =====

    public function ukuranTerbaca(): string
    {
        $bytes = $this->ukuran_file;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . 'MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }
        return $bytes . 'B';
    }
}
