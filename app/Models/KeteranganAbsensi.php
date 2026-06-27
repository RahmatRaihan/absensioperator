<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeteranganAbsensi extends Model
{
    use HasFactory;

    protected $table = 'keterangan_absensis';

    protected $fillable = [
        'absensi_id',
        'tanggal',
        'kode',
        'alasan',
    ];

    protected $casts = [
        'absensi_id' => 'integer',
        'tanggal' => 'date',
    ];

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }
}
