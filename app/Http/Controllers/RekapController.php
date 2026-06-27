<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Periode;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $periodes = Periode::orderBy('tgl_mulai', 'desc')->get();
        
        $selectedPeriodeId = $request->input('periode_id');
        if (!$selectedPeriodeId && $periodes->isNotEmpty()) {
            // Find active, else latest
            $active = $periodes->firstWhere('status', 'Aktif');
            $selectedPeriodeId = $active ? $active->id : $periodes->first()->id;
        }

        $records = [];
        $periode = null;
        $subtotals = [];
        $grandTotal = [
            'jml_shift1' => 0,
            'jml_shift2' => 0,
            'jml_shift3' => 0,
            'jml_holiday' => 0,
            'jml_libur_nasional' => 0,
            'jml_sakit' => 0,
            'jml_izin' => 0,
            'jml_alpa' => 0,
            'jml_cuti_biasa' => 0,
            'jml_cuti_khusus' => 0,
            'total_masuk' => 0,
            'ln_count' => 0,
            'hk_reguler' => 0,
            'jam_kerja' => 0,
            'selisih_jam' => 0,
            'total_shift' => 0,
        ];

        if ($selectedPeriodeId) {
            $periode = Periode::findOrFail($selectedPeriodeId);
            
            // Ensure all active operators have an absensi record for this period
            app(\App\Services\AbsensiCalculator::class)->ensureAbsensiRecordsExist($periode);

            $query = Absensi::with(['operator', 'keteranganAbsensis'])
                ->where('periode_id', $selectedPeriodeId);

            // Filter search operator
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->whereHas('operator', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('id_operator', 'like', "%{$search}%");
                });
            }

            // Filter Unit
            if ($request->has('unit') && $request->unit != '') {
                $query->whereHas('operator', function ($q) use ($request) {
                    $q->where('unit', $request->unit);
                });
            }

            // Filter Grup Shift
            if ($request->has('grup_shift') && $request->grup_shift != '') {
                $query->whereHas('operator', function ($q) use ($request) {
                    $q->where('grup_shift', $request->grup_shift);
                });
            }

            // Filter Exceptions Only (S/I/A > 0)
            if ($request->has('exceptions_only') && $request->exceptions_only === 'true') {
                $query->where(function ($q) {
                    $q->where('jml_sakit', '>', 0)
                      ->orWhere('jml_izin', '>', 0)
                      ->orWhere('jml_alpa', '>', 0);
                });
            }

            $records = $query->get()->sortBy(function($absensi) {
                return ($absensi->operator->grup_shift ?? '') . '_' . ($absensi->operator->id_operator ?? '');
            })->values()->all();

            $ln_dates = $periode->libur_nasional ?? [];
            $tgl_mulai = Carbon::parse($periode->tgl_mulai);

            foreach ($records as $rec) {
                $total_masuk = $rec->jml_shift1 + $rec->jml_shift2 + $rec->jml_shift3;
                $ln_count = 0;

                if (!empty($ln_dates)) {
                    $chars = str_split($rec->kode_string);
                    foreach ($chars as $idx => $char) {
                        if (in_array($char, ['1', '2', '3'])) {
                            $dateStr = $tgl_mulai->copy()->addDays($idx)->toDateString();
                            if (in_array($dateStr, $ln_dates)) {
                                $ln_count++;
                            }
                        }
                    }
                }

                $rec->total_masuk = $total_masuk;
                $rec->ln_count = $ln_count;
                $rec->hk_reguler = $total_masuk - $ln_count;
                $rec->menit_kerja = $rec->hk_reguler * 460;
                $rec->jam_kerja = round($rec->menit_kerja / 60, 2);
                $rec->selisih_jam = round($rec->jam_kerja - 173, 2);
            }

            // Calculate Subtotals per Unit and Grand Totals
            $units = ['BTG', 'C&AHS', 'Power Distribution'];
            foreach ($units as $unit) {
                $subtotals[$unit] = [
                    'jml_shift1' => 0,
                    'jml_shift2' => 0,
                    'jml_shift3' => 0,
                    'jml_holiday' => 0,
                    'jml_libur_nasional' => 0,
                    'jml_sakit' => 0,
                    'jml_izin' => 0,
                    'jml_alpa' => 0,
                    'jml_cuti_biasa' => 0,
                    'jml_cuti_khusus' => 0,
                    'total_masuk' => 0,
                    'ln_count' => 0,
                    'hk_reguler' => 0,
                    'jam_kerja' => 0,
                    'selisih_jam' => 0,
                    'total_shift' => 0,
                    'count' => 0,
                ];
            }

            foreach ($records as $rec) {
                $u = $rec->operator->unit;
                if (isset($subtotals[$u])) {
                    $subtotals[$u]['jml_shift1'] += $rec->jml_shift1;
                    $subtotals[$u]['jml_shift2'] += $rec->jml_shift2;
                    $subtotals[$u]['jml_shift3'] += $rec->jml_shift3;
                    $subtotals[$u]['jml_holiday'] += $rec->jml_holiday;
                    $subtotals[$u]['jml_libur_nasional'] += $rec->jml_libur_nasional;
                    $subtotals[$u]['jml_sakit'] += $rec->jml_sakit;
                    $subtotals[$u]['jml_izin'] += $rec->jml_izin;
                    $subtotals[$u]['jml_alpa'] += $rec->jml_alpa;
                    $subtotals[$u]['jml_cuti_biasa'] += $rec->jml_cuti_biasa;
                    $subtotals[$u]['jml_cuti_khusus'] += $rec->jml_cuti_khusus;
                    $subtotals[$u]['total_masuk'] += $rec->total_masuk;
                    $subtotals[$u]['ln_count'] += $rec->ln_count;
                    $subtotals[$u]['hk_reguler'] += $rec->hk_reguler;
                    $subtotals[$u]['jam_kerja'] += $rec->jam_kerja;
                    $subtotals[$u]['selisih_jam'] += $rec->selisih_jam;
                    $subtotals[$u]['total_shift'] += $rec->total_shift;
                    $subtotals[$u]['count']++;
                }

                $grandTotal['jml_shift1'] += $rec->jml_shift1;
                $grandTotal['jml_shift2'] += $rec->jml_shift2;
                $grandTotal['jml_shift3'] += $rec->jml_shift3;
                $grandTotal['jml_holiday'] += $rec->jml_holiday;
                $grandTotal['jml_libur_nasional'] += $rec->jml_libur_nasional;
                $grandTotal['jml_sakit'] += $rec->jml_sakit;
                $grandTotal['jml_izin'] += $rec->jml_izin;
                $grandTotal['jml_alpa'] += $rec->jml_alpa;
                $grandTotal['jml_cuti_biasa'] += $rec->jml_cuti_biasa;
                $grandTotal['jml_cuti_khusus'] += $rec->jml_cuti_khusus;
                $grandTotal['total_masuk'] += $rec->total_masuk;
                $grandTotal['ln_count'] += $rec->ln_count;
                $grandTotal['hk_reguler'] += $rec->hk_reguler;
                $grandTotal['jam_kerja'] += $rec->jam_kerja;
                $grandTotal['selisih_jam'] += $rec->selisih_jam;
                $grandTotal['total_shift'] += $rec->total_shift;
            }
        }

        return Inertia::render('Rekap/Index', [
            'periodes' => $periodes,
            'selectedPeriodeId' => $selectedPeriodeId ? (int)$selectedPeriodeId : null,
            'periode' => $periode,
            'records' => $records,
            'subtotals' => $subtotals,
            'grandTotal' => $grandTotal,
            'filters' => $request->only(['search', 'unit', 'grup_shift', 'exceptions_only']),
        ]);
    }
}
