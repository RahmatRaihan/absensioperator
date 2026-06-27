<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class PeriodeController extends Controller
{
    private $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    private $monthCodes = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
        5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGS',
        9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
    ];

    public function index()
    {
        // Load periodes and count how many absensi records are registered for each
        $periodes = Periode::withCount('absensis')
            ->orderBy('tgl_mulai', 'desc')
            ->get();

        return Inertia::render('Periode/Index', [
            'periodes' => $periodes,
            'months' => $this->months,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2099',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $kodePeriode = $this->monthCodes[$bulan] . $tahun;
        $label = $this->months[$bulan] . ' ' . $tahun;

        // Check for duplicate code
        if (Periode::where('kode_periode', $kodePeriode)->exists()) {
            return back()->withErrors(['bulan' => 'Periode ' . $label . ' sudah ada.']);
        }

        // Calculate tgl_mulai (21st of previous month) and tgl_selesai (20th of chosen month)
        $tglSelesai = Carbon::create($tahun, $bulan, 20);
        $tglMulai = Carbon::create($tahun, $bulan, 20)->subMonth()->addDay();
        $totalHari = $tglMulai->diffInDays($tglSelesai) + 1;

        DB::transaction(function () use ($kodePeriode, $label, $tglMulai, $tglSelesai, $totalHari) {
            // Deactivate other periodes (set to Selesai)
            Periode::query()->update(['status' => 'Selesai']);

            // Create new active period
            $periode = Periode::create([
                'kode_periode' => $kodePeriode,
                'label' => $label,
                'tgl_mulai' => $tglMulai->toDateString(),
                'tgl_selesai' => $tglSelesai->toDateString(),
                'total_hari' => $totalHari,
                'status' => 'Aktif',
            ]);

            // Auto-populate Absensi for all active operators with rollover schedule
            app(\App\Services\AbsensiCalculator::class)->ensureAbsensiRecordsExist($periode);
        });

        return redirect()->route('periode.index')->with('success', 'Periode baru berhasil dibuat.');
    }

    public function update(Request $request, Periode $periode)
    {
        $request->validate([
            'status' => 'nullable|in:Aktif,Selesai',
            'libur_nasional' => 'nullable|array',
            'libur_nasional.*' => 'date|date_format:Y-m-d',
        ]);

        DB::transaction(function () use ($periode, $request) {
            if ($request->has('status') && $request->status !== null) {
                if ($request->status === 'Aktif') {
                    // Set other periodes to Selesai
                    Periode::where('id', '!=', $periode->id)->update(['status' => 'Selesai']);
                }
                $periode->update([
                    'status' => $request->status,
                ]);
            }

            if ($request->has('libur_nasional')) {
                $dates = $request->libur_nasional ?? [];
                sort($dates);
                $periode->update([
                    'libur_nasional' => $dates,
                ]);
            }
        });

        return redirect()->route('periode.index')->with('success', 'Periode berhasil diperbarui.');
    }

    public function destroy(Periode $periode)
    {
        $periode->delete();
        return redirect()->route('periode.index')->with('success', 'Periode berhasil dihapus.');
    }
}
