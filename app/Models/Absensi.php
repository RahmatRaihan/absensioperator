<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'periode_id',
        'operator_id',
        'kode_string',
        'jml_shift1',
        'jml_shift2',
        'jml_shift3',
        'jml_holiday',
        'jml_libur_nasional',
        'jml_sakit',
        'jml_izin',
        'jml_alpa',
        'jml_cuti_biasa',
        'jml_cuti_khusus',
        'total_shift',
        'keterangan',
    ];

    protected $casts = [
        'periode_id' => 'integer',
        'operator_id' => 'integer',
        'jml_shift1' => 'integer',
        'jml_shift2' => 'integer',
        'jml_shift3' => 'integer',
        'jml_holiday' => 'integer',
        'jml_libur_nasional' => 'integer',
        'jml_sakit' => 'integer',
        'jml_izin' => 'integer',
        'jml_alpa' => 'integer',
        'jml_cuti_biasa' => 'integer',
        'jml_cuti_khusus' => 'integer',
        'total_shift' => 'integer',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function keteranganAbsensis(): HasMany
    {
        return $this->hasMany(KeteranganAbsensi::class);
    }
}
