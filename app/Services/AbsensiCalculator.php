<?php

namespace App\Services;

class AbsensiCalculator
{
    /**
     * Calculate attendance totals from a code string.
     *
     * @param string $kodeString
     * @return array
     */
    public function calculateFromKodeString(string $kodeString): array
    {
        // Trim or pad to prevent out of bounds
        $chars = str_split($kodeString);
        
        $s1 = 0;
        $s2 = 0;
        $s3 = 0;
        $h = 0;
        $ln = 0;
        $s = 0;
        $i = 0;
        $a = 0;
        $ct = 0;
        $ck = 0;

        foreach ($chars as $char) {
            switch (strtoupper($char)) {
                case '1':
                    $s1++;
                    break;
                case '2':
                    $s2++;
                    break;
                case '3':
                    $s3++;
                    break;
                case 'H':
                    $h++;
                    break;
                case 'L':
                    $ln++;
                    break;
                case 'S':
                    $s++;
                    break;
                case 'I':
                    $i++;
                    break;
                case 'A':
                    $a++;
                    break;
                case 'C':
                    $ct++;
                    break;
                case 'K':
                    $ck++;
                    break;
            }
        }

        $totalShift = $s1 + $s2 + $s3;

        return [
            'jml_shift1' => $s1,
            'jml_shift2' => $s2,
            'jml_shift3' => $s3,
            'jml_holiday' => $h,
            'jml_libur_nasional' => $ln,
            'jml_sakit' => $s,
            'jml_izin' => $i,
            'jml_alpa' => $a,
            'jml_cuti_biasa' => $ct,
            'jml_cuti_khusus' => $ck,
            'total_shift' => $totalShift,
        ];
    }

    /**
     * Generate rollover kode_string for an operator based on the 12-day cycle: 333H111222HH
     *
     * @param \App\Models\Operator $operator
     * @param \App\Models\Periode $newPeriode
     * @return string
     */
    public function generateRolloverKodeString(\App\Models\Operator $operator, \App\Models\Periode $newPeriode): string
    {
        $cycle = ['3', '3', '3', 'H', '1', '1', '1', '2', '2', '2', 'H', 'H'];
        $totalDays = $newPeriode->total_hari;

        // Find the previous period whose tgl_selesai is closest to but before the start of the new period
        $prevPeriode = \App\Models\Periode::where('tgl_selesai', '<', $newPeriode->tgl_mulai)
            ->orderBy('tgl_selesai', 'desc')
            ->first();

        if ($prevPeriode) {
            $prevAbsensi = \App\Models\Absensi::where('periode_id', $prevPeriode->id)
                ->where('operator_id', $operator->id)
                ->first();

            if ($prevAbsensi && !empty($prevAbsensi->kode_string)) {
                $prevCodes = str_split($prevAbsensi->kode_string);
                $N = count($prevCodes);

                // Align the pattern to find the best match offset k
                $bestK = 0;
                $maxMatches = -1;
                for ($k = 0; $k < 12; $k++) {
                    $matches = 0;
                    $validDays = 0;
                    for ($j = 0; $j < $N; $j++) {
                        if (!isset($prevCodes[$j])) continue;
                        $char = strtoupper($prevCodes[$j]);
                        if (in_array($char, ['1', '2', '3', 'H'])) {
                            $validDays++;
                            if ($char === $cycle[($j + $k) % 12]) {
                                $matches++;
                            }
                        }
                    }
                    if ($validDays > 0 && $matches > $maxMatches) {
                        $maxMatches = $matches;
                        $bestK = $k;
                    }
                }

                // The cycle index for day 0 of the new period is (N + bestK) % 12
                $startCycleIndex = ($N + $bestK) % 12;

                $newCodes = [];
                for ($j = 0; $j < $totalDays; $j++) {
                    $newCodes[] = $cycle[($j + $startCycleIndex) % 12];
                }
                return implode('', $newCodes);
            }
        }

        // Fallback default: generate pattern starting at 0
        $newCodes = [];
        for ($j = 0; $j < $totalDays; $j++) {
            $newCodes[] = $cycle[$j % 12];
        }
        return implode('', $newCodes);
    }

    /**
     * Ensure all active operators have an absensi record for the given period.
     *
     * @param \App\Models\Periode $periode
     * @return void
     */
    public function ensureAbsensiRecordsExist(\App\Models\Periode $periode)
    {
        $activeOperatorIds = \App\Models\Operator::where('status', 'Aktif')->pluck('id')->toArray();
        $existingOperatorIds = \App\Models\Absensi::where('periode_id', $periode->id)
            ->pluck('operator_id')
            ->toArray();
            
        $missingOperatorIds = array_diff($activeOperatorIds, $existingOperatorIds);
        
        if (!empty($missingOperatorIds)) {
            $missingOperators = \App\Models\Operator::whereIn('id', $missingOperatorIds)->get();
            foreach ($missingOperators as $operator) {
                $kodeString = $this->generateRolloverKodeString($operator, $periode);
                $totals = $this->calculateFromKodeString($kodeString);
                
                \App\Models\Absensi::create([
                    'periode_id' => $periode->id,
                    'operator_id' => $operator->id,
                    'kode_string' => $kodeString,
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
                    'keterangan' => 'Auto-generated for New Operator',
                ]);
            }
        }
    }
}
