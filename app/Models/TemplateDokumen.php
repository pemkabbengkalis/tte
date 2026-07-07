<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateDokumen extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'template_dokumen';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'nama_template',
        'deskripsi',
        'nama_file',
        'path_file',
        'versi',
        'is_active',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ===== Relasi =====

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
