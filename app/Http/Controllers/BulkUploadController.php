<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Periode;
use App\Models\Absensi;
use App\Models\KeteranganAbsensi;
use App\Services\AbsensiCalculator;
use App\Imports\AbsensiImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class BulkUploadController extends Controller
{
    protected $calculator;

    public function __construct(AbsensiCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function index()
    {
        $periodes = Periode::orderBy('tgl_mulai', 'desc')->get();
        
        return Inertia::render('Absensi/BulkUpload', [
            'periodes' => $periodes,
        ]);
    }

    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
        ]);

        $periode = Periode::findOrFail($request->periode_id);
        $operators = Operator::where('status', 'Aktif')->orderBy('id_operator', 'asc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Upload Absensi');

        // Headers
        $sheet->setCellValue('A1', 'ID Operator');
        $sheet->setCellValue('B1', 'Nama Operator');
        $sheet->setCellValue('C1', 'Kode String');
        $sheet->setCellValue('D1', 'Keterangan');

        // Style header
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(30);

        // Pre-fill operators
        $rowIdx = 2;
        foreach ($operators as $op) {
            $sheet->setCellValue('A' . $rowIdx, $op->id_operator);
            $sheet->setCellValue('B' . $rowIdx, $op->nama);
            
            // Format column C as Text explicitly
            $sheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
            
            // Provide a default kode string for convenience (weekdays 1, weekends H)
            $defaultCodes = [];
            $startDate = Carbon::parse($periode->tgl_mulai);
            $endDate = Carbon::parse($periode->tgl_selesai);
            while ($startDate->lte($endDate)) {
                $defaultCodes[] = $startDate->isWeekend() ? 'H' : '1';
                $startDate->addDay();
            }
            $sheet->setCellValue('C' . $rowIdx, implode('', $defaultCodes));
            
            $rowIdx++;
        }

        // Add instructions tab
        $instSheet = $spreadsheet->createSheet();
        $instSheet->setTitle('Petunjuk Pengisian');
        $instSheet->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE UPLOAD BULK');
        $instSheet->setCellValue('A2', '1. Kolom "ID Operator" & "Nama Operator" sudah terisi otomatis. Mohon jangan diubah.');
        $instSheet->setCellValue('A3', '2. Kolom "Kode String" harus diisi dengan kode absensi harian sepanjang total hari periode (' . $periode->total_hari . ' karakter).');
        $instSheet->setCellValue('A4', '3. Karakter yang diperbolehkan dalam Kode String:');
        $instSheet->setCellValue('A5', '   - 1 = Shift 1 (Pagi)');
        $instSheet->setCellValue('A6', '   - 2 = Shift 2 (Siang)');
        $instSheet->setCellValue('A7', '   - 3 = Shift 3 (Malam)');
        $instSheet->setCellValue('A8', '   - H = Off / Libur (Holiday)');
        $instSheet->setCellValue('A9', '   - S = Sakit');
        $instSheet->setCellValue('A10', '  - I = Izin');
        $instSheet->setCellValue('A11', '  - A = Alpa / Mangkir');
        $instSheet->setCellValue('A12', '  - C = Cuti Biasa (CT)');
        $instSheet->setCellValue('A13', '  - K = Cuti Khusus (CK)');
        $instSheet->setCellValue('A14', '  - L = Libur Nasional (LN)');
        $instSheet->setCellValue('A15', '4. Kolom "Keterangan" bersifat opsional untuk memberikan catatan umum.');
        $instSheet->setCellValue('A16', '5. Pengisian alasan sakit/izin/alpa/cuti/libur nasional (S/I/A/C/K/L) spesifik per tanggal dapat dilakukan secara online di menu "Entry Absensi" setelah upload ini berhasil.');

        $instSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instSheet->getColumnDimension('A')->setWidth(100);

        // Set active sheet back to first
        $spreadsheet->setActiveSheetIndex(0);

        // Export file
        $fileName = 'Template_Absensi_' . $periode->kode_periode . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $periode = Periode::findOrFail($request->periode_id);

        $import = new AbsensiImport($periode, $this->calculator);
        Excel::import($import, $request->file('file'));

        $errors = $import->getErrors();
        $parsedData = $import->getParsedData();

        return back()->with([
            'uploadErrors' => $errors,
            'uploadPreview' => $parsedData,
            'uploadPeriodeId' => $periode->id,
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'entries' => 'required|array|min:1',
            'entries.*.operator_id' => 'required|exists:operators,id',
            'entries.*.kode_string' => 'required|string',
            'entries.*.keterangan' => 'nullable|string',
        ]);

        $periodeId = $request->periode_id;

        DB::transaction(function () use ($periodeId, $request) {
            foreach ($request->entries as $entry) {
                $kodeString = strtoupper($entry['kode_string']);
                $totals = $this->calculator->calculateFromKodeString($kodeString);

                Absensi::updateOrCreate(
                    [
                        'periode_id' => $periodeId,
                        'operator_id' => $entry['operator_id'],
                    ],
                    [
                        'kode_string' => $kodeString,
                        'jml_shift1' => $totals['jml_shift1'],
                        'jml_shift2' => $totals['jml_shift2'],
                        'jml_shift3' => $totals['jml_shift3'],
                        'jml_holiday' => $totals['jml_holiday'],
                        'jml_sakit' => $totals['jml_sakit'],
                        'jml_izin' => $totals['jml_izin'],
                        'jml_alpa' => $totals['jml_alpa'],
                        'jml_libur_nasional' => $totals['jml_libur_nasional'],
                        'jml_cuti_biasa' => $totals['jml_cuti_biasa'],
                        'jml_cuti_khusus' => $totals['jml_cuti_khusus'],
                        'total_shift' => $totals['total_shift'],
                        'keterangan' => $entry['keterangan'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('rekap.index', ['periode_id' => $periodeId])
            ->with('success', count($request->entries) . ' data absensi berhasil di-import massal.');
    }
}
