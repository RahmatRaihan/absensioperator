<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_operator',
        'nama',
        'unit',
        'grup_shift',
        'status',
        'npk',
        'keterangan',
    ];

    protected $casts = [
    ];

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }
}
