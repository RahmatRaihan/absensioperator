<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Operator;
use App\Models\Periode;
use App\Models\KeteranganAbsensi;
use App\Services\AbsensiCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class AbsensiController extends Controller
{
    protected $calculator;

    public function __construct(AbsensiCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function entry(Request $request)
    {
        $periodes = Periode::orderBy('tgl_mulai', 'desc')->get();
        $operators = Operator::where('status', 'Aktif')->orderBy('nama', 'asc')->get();

        $selectedPeriodeId = $request->input('periode_id');
        $selectedOperatorId = $request->input('operator_id');

        $absensi = null;
        $calendarDays = [];
        $existingKeterangan = [];

        if ($selectedPeriodeId && $selectedOperatorId) {
            $periode = Periode::findOrFail($selectedPeriodeId);
            $operator = Operator::findOrFail($selectedOperatorId);

            // Ensure all active operators have an absensi record for this period
            $this->calculator->ensureAbsensiRecordsExist($periode);

            // Fetch or initialize absensi record
            $absensi = Absensi::with('keteranganAbsensis')
                ->where('periode_id', $selectedPeriodeId)
                ->where('operator_id', $selectedOperatorId)
                ->first();

            // Generate calendar dates
            $startDate = Carbon::parse($periode->tgl_mulai);
            $endDate = Carbon::parse($periode->tgl_selesai);
            
            $dayIndex = 0;
            while ($startDate->lte($endDate)) {
                $isWeekend = $startDate->isWeekend();
                $dayName = $this->getIndonesianDayName($startDate->dayOfWeek);
                
                $calendarDays[] = [
                    'date' => $startDate->toDateString(),
                    'day_of_month' => $startDate->day,
                    'day_name' => $dayName,
                    'is_weekend' => $isWeekend,
                    'index' => $dayIndex,
                ];

                $startDate->addDay();
                $dayIndex++;
            }

            // If absensi exists, extract keterangan (reasons) keyed by date
            if ($absensi) {
                foreach ($absensi->keteranganAbsensis as $ket) {
                    $existingKeterangan[$ket->tanggal->toDateString()] = [
                        'kode' => $ket->kode,
                        'alasan' => $ket->alasan,
                    ];
                }
            } else {
                // Initialize default codes using the rollover pattern
                $defaultCodes = $this->calculator->generateRolloverKodeString($operator, $periode);
                $totals = $this->calculator->calculateFromKodeString($defaultCodes);
                
                // Return a temporary object for frontend
                $absensi = [
                    'periode_id' => (int) $selectedPeriodeId,
                    'operator_id' => (int) $selectedOperatorId,
                    'kode_string' => $defaultCodes,
                    'jml_shift1' => $totals['jml_shift1'],
                    'jml_shift2' => $totals['jml_shift2'],
                    'jml_shift3' => $totals['jml_shift3'],
                    'jml_holiday' => $totals['jml_holiday'],
                    'jml_libur_nasional' => 0,
                    'jml_sakit' => 0,
                    'jml_izin' => 0,
                    'jml_alpa' => 0,
                    'jml_cuti_biasa' => 0,
                    'jml_cuti_khusus' => 0,
                    'total_shift' => $totals['total_shift'],
                    'keterangan' => '',
                ];
            }
        }

        return Inertia::render('Absensi/Entry', [
            'periodes' => $periodes,
            'operators' => $operators,
            'selectedPeriodeId' => $selectedPeriodeId ? (int)$selectedPeriodeId : null,
            'selectedOperatorId' => $selectedOperatorId ? (int)$selectedOperatorId : null,
            'absensi' => $absensi,
            'calendarDays' => $calendarDays,
            'existingKeterangan' => (object) $existingKeterangan,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'operator_id' => 'required|exists:operators,id',
            'kode_string' => 'required|string',
            'keterangan' => 'nullable|string',
            'reasons' => 'nullable|array', // key: YYYY-MM-DD, value: { kode: S|I|A, alasan: string }
        ]);

        $result = $this->saveAttendance(
            $request->periode_id,
            $request->operator_id,
            $request->kode_string,
            $request->keterangan,
            $request->reasons ?? []
        );

        return redirect()->route('absensi.entry', [
            'periode_id' => $request->periode_id,
            'operator_id' => $request->operator_id
        ])->with('success', 'Data absensi berhasil disimpan.');
    }

    public function autoSave(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'operator_id' => 'required|exists:operators,id',
            'kode_string' => 'required|string',
            'keterangan' => 'nullable|string',
            'reasons' => 'nullable|array',
        ]);

        $absensi = $this->saveAttendance(
            $request->periode_id,
            $request->operator_id,
            $request->kode_string,
            $request->keterangan,
            $request->reasons ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Autosave berhasil.',
            'absensi' => $absensi,
        ]);
    }

    private function saveAttendance($periodeId, $operatorId, $kodeString, $generalKeterangan, array $reasons)
    {
        $totals = $this->calculator->calculateFromKodeString($kodeString);

        return DB::transaction(function () use ($periodeId, $operatorId, $kodeString, $generalKeterangan, $reasons, $totals) {
            // Find or create Absensi
            $absensi = Absensi::updateOrCreate(
                [
                    'periode_id' => $periodeId,
                    'operator_id' => $operatorId,
                ],
                [
                    'kode_string' => $kodeString,
                    'jml_shift1' => $totals['jml_shift1'],
                    'jml_shift2' => $totals['jml_shift2'],
                    'jml_shift3' => $totals['jml_shift3'],
                    'jml_holiday' => $totals['jml_holiday'],
                    'jml_libur_nasional' => $totals['jml_libur_nasional'],
                    'jml_sakit' => $totals['jml_sakit'],
                    'jml_izin' => $totals['jml_izin'],
                    'jml_alpa' => $totals['jml_alpa'],
                    'jml_cuti_biasa' => $totals['jml_cuti_biasa'],
                    'jml_cuti_khusus' => $totals['jml_cuti_khusus'],
                    'total_shift' => $totals['total_shift'],
                    'keterangan' => $generalKeterangan,
                ]
            );

            // Sync keterangan_absensis (S/I/A reasons)
            // Delete existing reasons first for simplicity
            $absensi->keteranganAbsensis()->delete();

            // Save new reasons
            foreach ($reasons as $dateStr => $reasonData) {
                if (empty($reasonData) || empty($reasonData['kode']) || empty($reasonData['alasan'])) {
                    continue;
                }

                KeteranganAbsensi::create([
                    'absensi_id' => $absensi->id,
                    'tanggal' => $dateStr,
                    'kode' => $reasonData['kode'],
                    'alasan' => $reasonData['alasan'],
                ]);
            }

            return $absensi;
        });
    }

    private function getIndonesianDayName($dayOfWeek)
    {
        // Carbon: 0 = Sunday, 1 = Monday, ... 6 = Saturday
        $names = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $names[$dayOfWeek] ?? '';
    }
}
