<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periode extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_periode',
        'label',
        'tgl_mulai',
        'tgl_selesai',
        'total_hari',
        'libur_nasional',
        'status',
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'total_hari' => 'integer',
        'libur_nasional' => 'array',
    ];

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }
}
