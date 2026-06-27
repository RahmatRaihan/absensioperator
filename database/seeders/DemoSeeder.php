<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operator;
use App\Models\Periode;
use App\Models\Absensi;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a Periode (November 2025: 21 Oct - 20 Nov)
        $tglMulai = Carbon::create(2025, 10, 21);
        $tglSelesai = Carbon::create(2025, 11, 20);
        $totalHari = $tglMulai->diffInDays($tglSelesai) + 1;

        $periode = Periode::create([
            'kode_periode' => 'NOV2025',
            'label' => 'November 2025',
            'tgl_mulai' => $tglMulai->toDateString(),
            'tgl_selesai' => $tglSelesai->toDateString(),
            'total_hari' => $totalHari,
            'status' => 'Aktif',
        ]);

        // Create another completed period for history
        $tglMulaiPrev = Carbon::create(2025, 9, 21);
        $tglSelesaiPrev = Carbon::create(2025, 10, 20);
        $totalHariPrev = $tglMulaiPrev->diffInDays($tglSelesaiPrev) + 1;

        $periodePrev = Periode::create([
            'kode_periode' => 'OKT2025',
            'label' => 'Oktober 2025',
            'tgl_mulai' => $tglMulaiPrev->toDateString(),
            'tgl_selesai' => $tglSelesaiPrev->toDateString(),
            'total_hari' => $totalHariPrev,
            'status' => 'Selesai',
        ]);

        // 2. Create Operators
        $operatorsData = [
            // BTG
            ['id_operator' => 'OP001', 'nama' => 'Aditya Nugraha', 'unit' => 'BTG', 'grup_shift' => 'A', 'status' => 'Aktif', 'npk' => '11001'],
            ['id_operator' => 'OP002', 'nama' => 'Budi Santoso', 'unit' => 'BTG', 'grup_shift' => 'B', 'status' => 'Aktif', 'npk' => '11002'],
            ['id_operator' => 'OP003', 'nama' => 'Candra Wijaya', 'unit' => 'BTG', 'grup_shift' => 'C', 'status' => 'Aktif', 'npk' => '11003'],
            ['id_operator' => 'OP004', 'nama' => 'Dedi Prasetyo', 'unit' => 'BTG', 'grup_shift' => 'D', 'status' => 'Aktif', 'npk' => '11004'],
            ['id_operator' => 'OP005', 'nama' => 'Eko Susilo', 'unit' => 'BTG', 'grup_shift' => 'A', 'status' => 'Aktif', 'npk' => '11005'],

            // C&AHS
            ['id_operator' => 'OP006', 'nama' => 'Fajar Ramadhan', 'unit' => 'C&AHS', 'grup_shift' => 'B', 'status' => 'Aktif', 'npk' => '11006'],
            ['id_operator' => 'OP007', 'nama' => 'Gunawan Wibisono', 'unit' => 'C&AHS', 'grup_shift' => 'C', 'status' => 'Aktif', 'npk' => '11007'],
            ['id_operator' => 'OP008', 'nama' => 'Hendra Wijaya', 'unit' => 'C&AHS', 'grup_shift' => 'D', 'status' => 'Aktif', 'npk' => '11008'],
            ['id_operator' => 'OP009', 'nama' => 'Indra Lesmana', 'unit' => 'C&AHS', 'grup_shift' => 'A', 'status' => 'Aktif', 'npk' => '11009'],
            ['id_operator' => 'OP010', 'nama' => 'Joko Widodo', 'unit' => 'C&AHS', 'grup_shift' => 'B', 'status' => 'Aktif', 'npk' => '11010'],

            // Power Distribution
            ['id_operator' => 'OP011', 'nama' => 'Kurniawan Dwi', 'unit' => 'Power Distribution', 'grup_shift' => 'C', 'status' => 'Aktif', 'npk' => '11011'],
            ['id_operator' => 'OP012', 'nama' => 'Lutfi Hakim', 'unit' => 'Power Distribution', 'grup_shift' => 'D', 'status' => 'Aktif', 'npk' => '11012'],
            ['id_operator' => 'OP013', 'nama' => 'Mulyadi', 'unit' => 'Power Distribution', 'grup_shift' => 'A', 'status' => 'Aktif', 'npk' => '11013'],
            ['id_operator' => 'OP014', 'nama' => 'Nugroho Adhi', 'unit' => 'Power Distribution', 'grup_shift' => 'B', 'status' => 'Aktif', 'npk' => '11014'],
            ['id_operator' => 'OP015', 'nama' => 'Oki Setiawan', 'unit' => 'Power Distribution', 'grup_shift' => 'C', 'status' => 'Aktif', 'npk' => '11015'],

            // Mixed status & inactive
            ['id_operator' => 'OP016', 'nama' => 'Prabowo Subianto', 'unit' => 'BTG', 'grup_shift' => 'B', 'status' => 'Nonaktif', 'npk' => '11016', 'keterangan' => 'Resigned per Oct 2025'],
            ['id_operator' => 'OP017', 'nama' => 'Rian Hidayat', 'unit' => 'C&AHS', 'grup_shift' => 'A', 'status' => 'Aktif', 'npk' => '11017'],
            ['id_operator' => 'OP018', 'nama' => 'Slamet Riyadi', 'unit' => 'Power Distribution', 'grup_shift' => 'D', 'status' => 'Aktif', 'npk' => '11018'],
            ['id_operator' => 'OP019', 'nama' => 'Taufik Hidayat', 'unit' => 'BTG', 'grup_shift' => 'C', 'status' => 'Aktif', 'npk' => '11019'],
            ['id_operator' => 'OP020', 'nama' => 'Wahyu Hidayat', 'unit' => 'C&AHS', 'grup_shift' => 'D', 'status' => 'Aktif', 'npk' => '11020'],
        ];

        foreach ($operatorsData as $data) {
            Operator::create($data);
        }

        // 3. Create Absensis for completed period (OKT2025)
        $activeOperators = Operator::where('status', 'Aktif')->get();
        
        foreach ($activeOperators as $index => $operator) {
            // Generate some pattern of codes (e.g. shift 1, 2, 3, holiday, etc.)
            // Let's generate a string of length 30
            // Codes: 1, 2, 3, H, S, I, A
            $codes = [];
            for ($i = 0; $i < 30; $i++) {
                $rand = rand(0, 100);
                if ($rand < 25) {
                    $codes[] = '1';
                } elseif ($rand < 50) {
                    $codes[] = '2';
                } elseif ($rand < 75) {
                    $codes[] = '3';
                } elseif ($rand < 95) {
                    $codes[] = 'H';
                } elseif ($rand < 97) {
                    $codes[] = 'S';
                } elseif ($rand < 99) {
                    $codes[] = 'I';
                } else {
                    $codes[] = 'A';
                }
            }
            $kodeString = implode('', $codes);

            $counts = array_count_values($codes);
            $s1 = $counts['1'] ?? 0;
            $s2 = $counts['2'] ?? 0;
            $s3 = $counts['3'] ?? 0;
            $h = $counts['H'] ?? 0;
            $s = $counts['S'] ?? 0;
            $i_val = $counts['I'] ?? 0;
            $a = $counts['A'] ?? 0;

            $totalShift = $s1 + $s2 + $s3;

            $absensi = Absensi::create([
                'periode_id' => $periodePrev->id,
                'operator_id' => $operator->id,
                'kode_string' => $kodeString,
                'jml_shift1' => $s1,
                'jml_shift2' => $s2,
                'jml_shift3' => $s3,
                'jml_holiday' => $h,
                'jml_sakit' => $s,
                'jml_izin' => $i_val,
                'jml_alpa' => $a,
                'total_shift' => $totalShift,
                'keterangan' => 'Data seeded for Oct 2025',
            ]);

            // Add details for S/I/A if any
            $usedDays = [];
            
            $getUniqueDay = function() use (&$usedDays) {
                $day = rand(0, 29);
                while (in_array($day, $usedDays)) {
                    $day = rand(0, 29);
                }
                $usedDays[] = $day;
                return $day;
            };

            if ($s > 0) {
                $absensi->keteranganAbsensis()->create([
                    'tanggal' => Carbon::create(2025, 9, 21)->addDays($getUniqueDay())->toDateString(),
                    'kode' => 'S',
                    'alasan' => 'Sakit demam',
                ]);
            }
            if ($i_val > 0) {
                $absensi->keteranganAbsensis()->create([
                    'tanggal' => Carbon::create(2025, 9, 21)->addDays($getUniqueDay())->toDateString(),
                    'kode' => 'I',
                    'alasan' => 'Urusan keluarga',
                ]);
            }
            if ($a > 0) {
                $absensi->keteranganAbsensis()->create([
                    'tanggal' => Carbon::create(2025, 9, 21)->addDays($getUniqueDay())->toDateString(),
                    'kode' => 'A',
                    'alasan' => 'Mangkir tanpa kabar',
                ]);
            }
        }

        // For the active period (NOV2025), let's keep some operators with partial entries
        // and some with empty entries to allow test inputs
        foreach ($activeOperators->take(5) as $operator) {
            $codes = [];
            for ($i = 0; $i < 31; $i++) {
                $rand = rand(0, 100);
                if ($rand < 25) {
                    $codes[] = '1';
                } elseif ($rand < 50) {
                    $codes[] = '2';
                } elseif ($rand < 75) {
                    $codes[] = '3';
                } else {
                    $codes[] = 'H';
                }
            }
            $kodeString = implode('', $codes);
            $counts = array_count_values($codes);
            $s1 = $counts['1'] ?? 0;
            $s2 = $counts['2'] ?? 0;
            $s3 = $counts['3'] ?? 0;
            $h = $counts['H'] ?? 0;
            $totalShift = $s1 + $s2 + $s3;

            Absensi::create([
                'periode_id' => $periode->id,
                'operator_id' => $operator->id,
                'kode_string' => $kodeString,
                'jml_shift1' => $s1,
                'jml_shift2' => $s2,
                'jml_shift3' => $s3,
                'jml_holiday' => $h,
                'jml_sakit' => 0,
                'jml_izin' => 0,
                'jml_alpa' => 0,
                'total_shift' => $totalShift,
                'keterangan' => 'Entri awal',
            ]);
        }
    }
}
