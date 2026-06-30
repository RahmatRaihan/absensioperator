<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Periode;
use App\Models\Absensi;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Inertia\Inertia;

class ExportController extends Controller
{
    public function excel(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'search' => 'nullable|string',
            'unit' => 'nullable|string',
            'grup_shift' => 'nullable|string',
            'exceptions_only' => 'nullable|string',
        ]);

        $periode = Periode::findOrFail($request->periode_id);

        // Fetch records applying the same filters as Rekap Laporan
        $query = Absensi::with(['operator'])
            ->where('periode_id', $periode->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('operator', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_operator', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit')) {
            $query->whereHas('operator', function ($q) use ($request) {
                $q->where('unit', $request->unit);
            });
        }

        if ($request->filled('grup_shift')) {
            $query->whereHas('operator', function ($q) use ($request) {
                $q->where('grup_shift', $request->grup_shift);
            });
        }

        if ($request->exceptions_only === 'true') {
            $query->where(function ($q) {
                $q->where('jml_sakit', '>', 0)
                  ->orWhere('jml_izin', '>', 0)
                  ->orWhere('jml_alpa', '>', 0);
            });
        }

        $records = $query->get()->sortBy(function($absensi) {
            return ($absensi->operator->grup_shift ?? '') . '_' . ($absensi->operator->id_operator ?? '');
        });

        // Initialize Spreadsheet
        $spreadsheet = new Spreadsheet();
        // Remove default sheet
        $spreadsheet->removeSheetByIndex(0);

        // Define sheet groups: Semua, BTG, C&AHS, Power Distribution
        $groups = [
            'Semua' => $records,
            'BTG' => $records->filter(fn($r) => $r->operator->unit === 'BTG'),
            'C&AHS' => $records->filter(fn($r) => $r->operator->unit === 'C&AHS'),
            'Power Distribution' => $records->filter(fn($r) => $r->operator->unit === 'Power Distribution'),
        ];

        foreach ($groups as $groupName => $groupRecords) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($groupName);

            // Generate calendar dates
            $days = [];
            $start = new \DateTime($periode->tgl_mulai);
            $end = new \DateTime($periode->tgl_selesai);
            $curr = clone $start;
            while ($curr <= $end) {
                $days[] = [
                    'dayOfMonth' => $curr->format('j'),
                    'dateStr' => $curr->format('Y-m-d')
                ];
                $curr->modify('+1 day');
            }
            $dayCount = count($days);

            // Calculate columns dynamically
            $s1ColIndex = 5 + $dayCount;
            $s2ColIndex = $s1ColIndex + 1;
            $s3ColIndex = $s1ColIndex + 2;
            $hlColIndex = $s1ColIndex + 3;
            $hkColIndex = $s1ColIndex + 4;
            $lnColIndex = $s1ColIndex + 5;
            $menitColIndex = $s1ColIndex + 6;
            $lemburColIndex = $s1ColIndex + 7;
            $selisihColIndex = $s1ColIndex + 8;
            $cutiColIndex = $s1ColIndex + 9;
            $ckColIndex = $s1ColIndex + 10;
            $sColIndex = $s1ColIndex + 11;
            $iColIndex = $s1ColIndex + 12;
            $aColIndex = $s1ColIndex + 13;

            $s1Col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($s1ColIndex);
            $s2Col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($s2ColIndex);
            $s3Col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($s3ColIndex);
            $hlCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($hlColIndex);
            $hkCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($hkColIndex);
            $lnCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lnColIndex);
            $menitCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($menitColIndex);
            $lemburCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lemburColIndex);
            $selisihCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($selisihColIndex);
            $cutiCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cutiColIndex);
            $ckCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ckColIndex);
            $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($sColIndex);
            $iCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($iColIndex);
            $aCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($aColIndex);

            $lastColLetter = $aCol;

            // Define row height
            $sheet->getRowDimension(1)->setRowHeight(25);
            $sheet->getRowDimension(2)->setRowHeight(25);

            // Column widths
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(8);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(28);

            $firstDayColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5);
            $lastDayColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $dayCount - 1);

            // Set column widths for days
            for ($i = 0; $i < $dayCount; $i++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $i);
                $sheet->getColumnDimension($colLetter)->setWidth(4);
            }

            // Set column widths for summary columns
            $summaryCols = [
                $s1Col, $s2Col, $s3Col, $hlCol, $hkCol, $lnCol, 
                $cutiCol, $ckCol, $sCol, $iCol, $aCol
            ];
            foreach ($summaryCols as $col) {
                $sheet->getColumnDimension($col)->setWidth(5);
            }
            $sheet->getColumnDimension($menitCol)->setWidth(12);
            $sheet->getColumnDimension($lemburCol)->setWidth(18);
            $sheet->getColumnDimension($selisihCol)->setWidth(18);

            // 1. Merge A1:A2, B1:B2, C1:C2, D1:D2 for static headers
            $sheet->mergeCells('A1:A2');
            $sheet->setCellValue('A1', 'NO');

            $sheet->mergeCells('B1:B2');
            $sheet->setCellValue('B1', 'Team');

            $sheet->mergeCells('C1:C2');
            $sheet->setCellValue('C1', 'Personnel');

            $sheet->mergeCells('D1:D2');
            $sheet->setCellValue('D1', 'Discipline');

            // 2. Month headers in Row 1 (dynamic based on period)
            $monthNames = [
                1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
                7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
            ];

            $formatMonth = function($dateTime) use ($monthNames) {
                $m = (int)$dateTime->format('n');
                $y = $dateTime->format('y');
                $Y = $dateTime->format('Y');
                if ($m === 4) {
                    return 'Apr-' . $y;
                }
                return $monthNames[$m] . ' ' . $Y;
            };

            $monthGroups = [];
            foreach ($days as $idx => $day) {
                $dateObj = new \DateTime($day['dateStr']);
                $monthKey = $dateObj->format('Y-m');
                if (!isset($monthGroups[$monthKey])) {
                    $monthGroups[$monthKey] = [
                        'monthName' => $formatMonth($dateObj),
                        'startIndex' => 5 + $idx,
                        'endIndex' => 5 + $idx,
                    ];
                } else {
                    $monthGroups[$monthKey]['endIndex'] = 5 + $idx;
                }
            }

            foreach ($monthGroups as $group) {
                $startLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($group['startIndex']);
                $endLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($group['endIndex']);
                $sheet->mergeCells($startLetter . '1:' . $endLetter . '1');
                $sheet->setCellValue($startLetter . '1', $group['monthName']);
            }

            // 3. Merged Category header in Row 1 for summary columns
            $sheet->mergeCells($s1Col . '1:' . $aCol . '1');
            $sheet->setCellValue($s1Col . '1', 'Kode Absen');

            // 4. Sub-headers in Row 2 for days and summary columns
            foreach ($days as $idx => $day) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $idx);
                $dateStr = $day['dateStr'];
                $dateObj = new \DateTime($dateStr);
                $isHN = in_array($dateStr, $periode->libur_nasional ?? []);

                $sheet->setCellValue($colLetter . '2', $day['dayOfMonth']);

                if ($isHN) {
                    $sheet->getStyle($colLetter . '2')->getFont()
                        ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('A83E42'))
                        ->setBold(true);
                }
            }

            $sheet->setCellValue($s1Col . '2', '1');
            $sheet->setCellValue($s2Col . '2', '2');
            $sheet->setCellValue($s3Col . '2', '3');
            $sheet->setCellValue($hlCol . '2', 'HL');
            $sheet->setCellValue($hkCol . '2', 'HK');
            $sheet->setCellValue($lnCol . '2', 'LN');
            $sheet->setCellValue($menitCol . '2', 'Menit Kerja');
            $sheet->setCellValue($lemburCol . '2', 'Total Lembur (Jam)');
            $sheet->setCellValue($selisihCol . '2', 'Selisih Jam Kerja');
            $sheet->setCellValue($cutiCol . '2', 'Cuti');
            $sheet->setCellValue($ckCol . '2', 'CK');
            $sheet->setCellValue($sCol . '2', 'S');
            $sheet->setCellValue($iCol . '2', 'I');
            $sheet->setCellValue($aCol . '2', 'A');

            // Header styling
            $globalHeaderStyle = $sheet->getStyle('A1:' . $lastColLetter . '2');
            $globalHeaderStyle->getFont()->setBold(true);
            $globalHeaderStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $globalHeaderStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $globalHeaderStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');

            // Apply soft peach fill to calendar days header in Row 2
            $sheet->getStyle($firstDayColLetter . '2:' . $lastDayColLetter . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FCE4D6'));

            // Apply summary header fills in Row 2
            $sheet->getStyle($s1Col . '2:' . $s3Col . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFF2CC'));

            $sheet->getStyle($hlCol . '2:' . $lemburCol . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E2EFDA'));

            $sheet->getStyle($selisihCol . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E2EFDA'));

            $sheet->getStyle($cutiCol . '2:' . $ckCol . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FCE4D6'));

            $sheet->getStyle($sCol . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E2EFDA'));

            $sheet->getStyle($iCol . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFF2CC'));

            $sheet->getStyle($aCol . '2')->getFill()
                ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('A83E42'));
            $sheet->getStyle($aCol . '2')->getFont()
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));

            // 5. Populate Data Rows
            $rowIdx = 3;
            $no = 1;
            $currentTeam = null;
            $teamStartRow = 3;
            $teamRanges = [];
            $separatorRows = [];

            foreach ($groupRecords as $rec) {
                $team = $rec->operator->grup_shift ?? '—';
                
                if ($currentTeam !== null && $currentTeam !== $team) {
                    // Record previous team range
                    $teamRanges[] = ['start' => $teamStartRow, 'end' => $rowIdx - 1, 'team' => $currentTeam];
                    
                    // Add yellow separator row
                    $separatorRows[] = $rowIdx;
                    $rowIdx++;
                    
                    // Reset variables for next team
                    $no = 1;
                    $teamStartRow = $rowIdx;
                }

                if ($currentTeam === null) {
                    $currentTeam = $team;
                    $teamStartRow = $rowIdx;
                }
                $currentTeam = $team;

                // Write operator details
                $sheet->setCellValue('A' . $rowIdx, $no);
                $sheet->setCellValue('B' . $rowIdx, $team);
                $sheet->setCellValue('C' . $rowIdx, $rec->operator->nama);
                $sheet->setCellValue('D' . $rowIdx, $rec->operator->unit); // Discipline column maps to operator's unit

                // Set row height for data
                $sheet->getRowDimension($rowIdx)->setRowHeight(20);

                // Write calendar day status codes
                foreach ($days as $dayIdx => $day) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $dayIdx);
                    $char = isset($rec->kode_string[$dayIdx]) ? $rec->kode_string[$dayIdx] : ' ';
                    
                    if ($char === ' ') {
                        $char = '-';
                    } elseif ($char === 'C') {
                        $char = 'CT';
                    } elseif ($char === 'K') {
                        $char = 'CK';
                    } elseif ($char === 'L') {
                        $char = 'LN';
                    }
                    
                    $sheet->setCellValue($colLetter . $rowIdx, $char);

                    if ($char === 'H') {
                        $sheet->getStyle($colLetter . $rowIdx)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('820509'));
                        $sheet->getStyle($colLetter . $rowIdx)->getFont()
                            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE))
                            ->setBold(true);
                    } elseif ($char === 'LN') {
                        $sheet->getStyle($colLetter . $rowIdx)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('A83E42'));
                        $sheet->getStyle($colLetter . $rowIdx)->getFont()
                            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE))
                            ->setBold(true);
                    } elseif ($char === 'CT' || $char === 'CK') {
                        $sheet->getStyle($colLetter . $rowIdx)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FCE4D6'));
                        $sheet->getStyle($colLetter . $rowIdx)->getFont()->setBold(true);
                    } elseif ($char === 'S') {
                        $sheet->getStyle($colLetter . $rowIdx)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFF2CC'));
                        $sheet->getStyle($colLetter . $rowIdx)->getFont()->setBold(true);
                    } elseif ($char === 'I') {
                        $sheet->getStyle($colLetter . $rowIdx)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FCE4D6'));
                        $sheet->getStyle($colLetter . $rowIdx)->getFont()->setBold(true);
                    } elseif ($char === 'A') {
                        $sheet->getStyle($colLetter . $rowIdx)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('A83E42'));
                        $sheet->getStyle($colLetter . $rowIdx)->getFont()
                            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE))
                            ->setBold(true);
                    }
                }

                // National holiday shifts count calculation
                $ln_count = 0;
                $ln_dates = $periode->libur_nasional ?? [];
                $tgl_mulai = Carbon::parse($periode->tgl_mulai);
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

                // Write summary column values
                $sheet->setCellValue($s1Col . $rowIdx, $rec->jml_shift1);
                $sheet->setCellValue($s2Col . $rowIdx, $rec->jml_shift2);
                $sheet->setCellValue($s3Col . $rowIdx, $rec->jml_shift3);
                $sheet->setCellValue($hlCol . $rowIdx, $rec->jml_holiday);
                
                // HK = Shift 1 + Shift 2 + Shift 3 - LN
                $sheet->setCellValue($hkCol . $rowIdx, "=SUM(" . $s1Col . $rowIdx . ":" . $s3Col . $rowIdx . ")-" . $lnCol . $rowIdx);
                
                // LN is the count of shift workdays falling on holiday
                $sheet->setCellValue($lnCol . $rowIdx, $ln_count);
                
                // Menit Kerja = HK * 460
                $sheet->setCellValue($menitCol . $rowIdx, "=" . $hkCol . $rowIdx . "*460");
                
                // Total Lembur (Jam) = Menit Kerja / 60
                $sheet->setCellValue($lemburCol . $rowIdx, "=" . $menitCol . $rowIdx . "/60");
                $sheet->getStyle($lemburCol . $rowIdx)->getNumberFormat()->setFormatCode('0.0');
                
                // Selisih Jam Kerja = Total Lembur - 173
                $sheet->setCellValue($selisihCol . $rowIdx, "=" . $lemburCol . $rowIdx . "-173");
                $sheet->getStyle($selisihCol . $rowIdx)->getNumberFormat()->setFormatCode('0.0');
                
                $sheet->setCellValue($cutiCol . $rowIdx, $rec->jml_cuti_biasa);
                $sheet->setCellValue($ckCol . $rowIdx, $rec->jml_cuti_khusus);
                $sheet->setCellValue($sCol . $rowIdx, $rec->jml_sakit);
                $sheet->setCellValue($iCol . $rowIdx, $rec->jml_izin);
                $sheet->setCellValue($aCol . $rowIdx, $rec->jml_alpa);

                // Grid borders
                $sheet->getStyle('A' . $rowIdx . ':' . $lastColLetter . $rowIdx)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');

                // Alignments
                $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                
                $sheet->getStyle($firstDayColLetter . $rowIdx . ':' . $lastColLetter . $rowIdx)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $no++;
                $rowIdx++;
            }

            // Record last team range
            if ($currentTeam !== null) {
                $teamRanges[] = ['start' => $teamStartRow, 'end' => $rowIdx - 1, 'team' => $currentTeam];
            }

            // Apply Team Merging
            foreach ($teamRanges as $range) {
                if ($range['start'] < $range['end']) {
                    $sheet->mergeCells('B' . $range['start'] . ':B' . $range['end']);
                }
                $sheet->setCellValue('B' . $range['start'], $range['team']);
                $sheet->getStyle('B' . $range['start'] . ':B' . $range['end'])->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }

            // Apply Separator Rows Styling
            foreach ($separatorRows as $sepRow) {
                $sheet->getRowDimension($sepRow)->setRowHeight(20);
                $sheet->getStyle('A' . $sepRow . ':' . $lastColLetter . $sepRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFC000'));
                $sheet->getStyle('A' . $sepRow . ':' . $lastColLetter . $sepRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
            }

            // 6. Grand Total Row
            $totalRow = $rowIdx;
            $sheet->setCellValue('A' . $totalRow, 'GRAND TOTAL');
            $sheet->mergeCells('A' . $totalRow . ':D' . $totalRow);
            
            // Formulas
            $sheet->setCellValue($s1Col . $totalRow, '=SUM(' . $s1Col . '3:' . $s1Col . ($totalRow - 1) . ')');
            $sheet->setCellValue($s2Col . $totalRow, '=SUM(' . $s2Col . '3:' . $s2Col . ($totalRow - 1) . ')');
            $sheet->setCellValue($s3Col . $totalRow, '=SUM(' . $s3Col . '3:' . $s3Col . ($totalRow - 1) . ')');
            $sheet->setCellValue($hlCol . $totalRow, '=SUM(' . $hlCol . '3:' . $hlCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($hkCol . $totalRow, '=SUM(' . $hkCol . '3:' . $hkCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($lnCol . $totalRow, '=SUM(' . $lnCol . '3:' . $lnCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($menitCol . $totalRow, '=SUM(' . $menitCol . '3:' . $menitCol . ($totalRow - 1) . ')');
            
            $sheet->setCellValue($lemburCol . $totalRow, '=SUM(' . $lemburCol . '3:' . $lemburCol . ($totalRow - 1) . ')');
            $sheet->getStyle($lemburCol . $totalRow)->getNumberFormat()->setFormatCode('0.0');
            
            $sheet->setCellValue($selisihCol . $totalRow, '=SUM(' . $selisihCol . '3:' . $selisihCol . ($totalRow - 1) . ')');
            $sheet->getStyle($selisihCol . $totalRow)->getNumberFormat()->setFormatCode('0.0');
            
            $sheet->setCellValue($cutiCol . $totalRow, '=SUM(' . $cutiCol . '3:' . $cutiCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($ckCol . $totalRow, '=SUM(' . $ckCol . '3:' . $ckCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($sCol . $totalRow, '=SUM(' . $sCol . '3:' . $sCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($iCol . $totalRow, '=SUM(' . $iCol . '3:' . $iCol . ($totalRow - 1) . ')');
            $sheet->setCellValue($aCol . $totalRow, '=SUM(' . $aCol . '3:' . $aCol . ($totalRow - 1) . ')');

            // Style total row
            $sheet->getRowDimension($totalRow)->setRowHeight(22);
            $totalStyle = $sheet->getStyle('A' . $totalRow . ':' . $lastColLetter . $totalRow);
            $totalStyle->getFont()->setBold(true);
            $totalStyle->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            $totalStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
            $totalStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle('A' . $totalRow . ':' . $lastColLetter . $totalRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
            $sheet->getStyle('A' . $totalRow . ':' . $lastColLetter . $totalRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            $sheet->getStyle('A' . $totalRow . ':' . $lastColLetter . $totalRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

            // 7. Signature Section
            $signRow = $totalRow + 3;
            $supCol1 = 'B';
            $supCol2 = 'D';
            $admCol1 = $menitCol;
            $admCol2 = $lastColLetter;

            $sheet->setCellValue($supCol1 . $signRow, 'Mengetahui,');
            $sheet->setCellValue($supCol1 . ($signRow + 1), 'Supervisor Operator');
            
            $sheet->setCellValue($admCol1 . $signRow, 'Dibuat oleh,');
            $sheet->setCellValue($admCol1 . ($signRow + 1), 'Admin Operator');

            $sheet->setCellValue($supCol1 . ($signRow + 5), '...............................................');
            $sheet->setCellValue($admCol1 . ($signRow + 5), '...............................................');

            $sheet->getStyle($supCol1 . $signRow . ':' . $supCol2 . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($admCol1 . $signRow . ':' . $admCol2 . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Set active sheet to 'Semua'
        $spreadsheet->setActiveSheetIndex(0);

        // Download Excel
        $fileName = 'Rekap_Absensi_' . $periode->kode_periode . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function pdf(Request $request)
    {
        return $this->printView($request);
    }

    public function printView(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'search' => 'nullable|string',
            'unit' => 'nullable|string',
            'grup_shift' => 'nullable|string',
            'exceptions_only' => 'nullable|string',
        ]);

        $periode = Periode::findOrFail($request->periode_id);

        $query = Absensi::with(['operator', 'keteranganAbsensis'])
            ->where('periode_id', $periode->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('operator', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_operator', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit')) {
            $query->whereHas('operator', function ($q) use ($request) {
                $q->where('unit', $request->unit);
            });
        }

        if ($request->filled('grup_shift')) {
            $query->whereHas('operator', function ($q) use ($request) {
                $q->where('grup_shift', $request->grup_shift);
            });
        }

        if ($request->exceptions_only === 'true') {
            $query->where(function ($q) {
                $q->where('jml_sakit', '>', 0)
                  ->orWhere('jml_izin', '>', 0)
                  ->orWhere('jml_alpa', '>', 0);
            });
        }

        $records = $query->get()->sortBy(function($absensi) {
            return ($absensi->operator->grup_shift ?? '') . '_' . ($absensi->operator->id_operator ?? '');
        })->values()->all();

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
            $grandTotal['total_shift'] += $rec->total_shift;
            
            $grandTotal['total_masuk'] += $rec->total_masuk;
            $grandTotal['ln_count'] += $rec->ln_count;
            $grandTotal['hk_reguler'] += $rec->hk_reguler;
            $grandTotal['jam_kerja'] += $rec->jam_kerja;
            $grandTotal['selisih_jam'] += $rec->selisih_jam;
        }

        return Inertia::render('Rekap/PrintView', [
            'periode' => $periode,
            'records' => $records,
            'grandTotal' => $grandTotal,
        ]);
    }
}
