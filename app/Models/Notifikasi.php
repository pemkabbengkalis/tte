<?php

namespace App\Models;

use App\Enums\TipeNotifikasi;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifikasi';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'permohonan_id',
        'judul',
        'pesan',
        'tipe',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeNotifikasi::class,
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // ===== Relasi =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }
}
