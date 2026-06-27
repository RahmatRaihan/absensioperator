<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Periode;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get all periods for the dropdown
        $periodes = Periode::orderBy('tgl_mulai', 'desc')->get();

        // 2. Determine selected period ID
        $selectedPeriodeId = $request->input('periode_id');
        if (!$selectedPeriodeId && $periodes->isNotEmpty()) {
            $active = $periodes->firstWhere('status', 'Aktif');
            $selectedPeriodeId = $active ? $active->id : $periodes->first()->id;
        }

        $selectedPeriode = null;
        if ($selectedPeriodeId) {
            $selectedPeriode = Periode::find($selectedPeriodeId);
        }

        // 3. Base statistics
        $totalOperators = Operator::where('status', 'Aktif')->count();
        $totalShift = 0;
        $totalSakitIzin = 0;
        $totalAlpa = 0;

        $chartUnitData = [
            'BTG' => 0,
            'C&AHS' => 0,
            'Power Distribution' => 0,
        ];

        $chartCodeData = [
            'Shift 1' => 0,
            'Shift 2' => 0,
            'Shift 3' => 0,
            'Holiday' => 0,
            'Sakit' => 0,
            'Izin' => 0,
            'Alpa' => 0,
            'Cuti Biasa' => 0,
            'Cuti Khusus' => 0,
        ];

        $recentUpdates = [];

        if ($selectedPeriode) {
            // Aggregate values
            $stats = Absensi::where('periode_id', $selectedPeriode->id)
                ->selectRaw('
                    SUM(total_shift) as total_shift,
                    SUM(jml_sakit + jml_izin) as total_sakit_izin,
                    SUM(jml_alpa) as total_alpa,
                    SUM(jml_shift1) as s1,
                    SUM(jml_shift2) as s2,
                    SUM(jml_shift3) as s3,
                    SUM(jml_holiday) as h,
                    SUM(jml_sakit) as s,
                    SUM(jml_izin) as i,
                    SUM(jml_alpa) as a,
                    SUM(jml_cuti_biasa) as ct,
                    SUM(jml_cuti_khusus) as ck
                ')
                ->first();

            if ($stats) {
                $totalShift = (int) $stats->total_shift;
                $totalSakitIzin = (int) $stats->total_sakit_izin;
                $totalAlpa = (int) $stats->total_alpa;

                $chartCodeData = [
                    'Shift 1' => (int) $stats->s1,
                    'Shift 2' => (int) $stats->s2,
                    'Shift 3' => (int) $stats->s3,
                    'Holiday' => (int) $stats->h,
                    'Sakit' => (int) $stats->s,
                    'Izin' => (int) $stats->i,
                    'Alpa' => (int) $stats->a,
                    'Cuti Biasa' => (int) $stats->ct,
                    'Cuti Khusus' => (int) $stats->ck,
                ];
            }

            // Aggregate by Unit
            $unitStats = Absensi::where('periode_id', $selectedPeriode->id)
                ->join('operators', 'absensis.operator_id', '=', 'operators.id')
                ->select('operators.unit', DB::raw('SUM(absensis.total_shift) as total_shift'))
                ->groupBy('operators.unit')
                ->get();

            foreach ($unitStats as $uStat) {
                if (isset($chartUnitData[$uStat->unit])) {
                    $chartUnitData[$uStat->unit] = (int) $uStat->total_shift;
                }
            }

            // Recent Updates
            $recentUpdates = Absensi::with('operator')
                ->where('periode_id', $selectedPeriode->id)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();
        }

        return inertia('Dashboard', [
            'periodes' => $periodes,
            'selectedPeriodeId' => $selectedPeriodeId ? (int)$selectedPeriodeId : null,
            'activePeriode' => $selectedPeriode,
            'stats' => [
                'total_operators' => $totalOperators,
                'total_shift' => $totalShift,
                'sakit_izin' => $totalSakitIzin,
                'alpa' => $totalAlpa,
            ],
            'chartUnitData' => $chartUnitData,
            'chartCodeData' => $chartCodeData,
            'recentUpdates' => $recentUpdates,
        ]);
    }
}
