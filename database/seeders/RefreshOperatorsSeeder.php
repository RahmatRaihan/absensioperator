<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Operator;
use App\Models\Periode;
use App\Models\Absensi;

class RefreshOperatorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear existing data to avoid unique constraint violations
        Schema::disableForeignKeyConstraints();
        DB::table('keterangan_absensis')->truncate();
        DB::table('absensis')->truncate();
        DB::table('operators')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Compile list of operators from the image (excluding red text)
        $operators = [
            // TEAM A
            ['nama' => 'Apri Renaldo', 'grup_shift' => 'A', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Kamaruddin', 'grup_shift' => 'A', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Hainal Anwar', 'grup_shift' => 'A', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Vicky', 'grup_shift' => 'A', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Syahwahiddy', 'grup_shift' => 'A', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Alfandrie', 'grup_shift' => 'A', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Muhammad Andika Rahmadillah', 'grup_shift' => 'A', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Satria Pemanda', 'grup_shift' => 'A', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Muhammad Alfarizi', 'grup_shift' => 'A', 'discipline' => 'Operator Generator'],
            ['nama' => 'Siti Juniarsih', 'grup_shift' => 'A', 'discipline' => 'Operator Generator'],
            ['nama' => 'Muhammad Zunaidi', 'grup_shift' => 'A', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Fajar Djumansyah', 'grup_shift' => 'A', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Al Amin', 'grup_shift' => 'A', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Chairul Saputra', 'grup_shift' => 'A', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Diki Ashari', 'grup_shift' => 'A', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Tegar Firmansyah', 'grup_shift' => 'A', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Nursyafiah Syarah', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Rahman Na\'im', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Hisyam Fahmi', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Yopi Sanjaya', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'M. Kabarsen Nishasi', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Ari Riyadi', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Rangga Zuliansyah', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Irfianni', 'grup_shift' => 'A', 'discipline' => 'Operator Power Distribution'],

            // TEAM B
            ['nama' => 'Hainul', 'grup_shift' => 'B', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Agil Arya Irwansyah', 'grup_shift' => 'B', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Muhammad Rendy Lazuardi', 'grup_shift' => 'B', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Tooni Fadzli', 'grup_shift' => 'B', 'discipline' => 'Operator Boiler'],
            ['nama' => 'M.Rasyidul Mualimi', 'grup_shift' => 'B', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Bayu Andika', 'grup_shift' => 'B', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Yudha Setiadi', 'grup_shift' => 'B', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Andrianto Prabowo', 'grup_shift' => 'B', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Raffy Vernanda', 'grup_shift' => 'B', 'discipline' => 'Operator Generator'],
            // Nur Asmiati (red text) is ignored
            ['nama' => 'Supriyadi', 'grup_shift' => 'B', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Abdul Mujib', 'grup_shift' => 'B', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Alfin Rohmatul Ilmi', 'grup_shift' => 'B', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Muhammad Fauzi', 'grup_shift' => 'B', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Yusuf Mulyana', 'grup_shift' => 'B', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Sy Panji', 'grup_shift' => 'B', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Syarifah Meita Purdianti', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'M. Aldi Saputra', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Hendra Perdi', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Zaini Marianto', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Syamsul Maarif', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Muhammad Yunus', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Abdul Hadi', 'grup_shift' => 'B', 'discipline' => 'Operator Power Distribution'],

            // TEAM C
            ['nama' => 'Putra Muhamad Ashandi', 'grup_shift' => 'C', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Ari Safriansyah', 'grup_shift' => 'C', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Adrian Ervan Parulian', 'grup_shift' => 'C', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Qolbi Husen', 'grup_shift' => 'C', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'M.Irfan Fitrazuliana', 'grup_shift' => 'C', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Sunarso', 'grup_shift' => 'C', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Muhammad Ede Suvanto', 'grup_shift' => 'C', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Andre Pratama', 'grup_shift' => 'C', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Hano Agung Prambudi', 'grup_shift' => 'C', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Tegar Yudiansyah', 'grup_shift' => 'C', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Fatriyanto', 'grup_shift' => 'C', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Qoida Risna', 'grup_shift' => 'C', 'discipline' => 'Operator Generator'],
            ['nama' => 'Zulfan', 'grup_shift' => 'C', 'discipline' => 'Operator Generator'],
            ['nama' => 'Fedhlia Athira Riani', 'grup_shift' => 'C', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Aprilianus Dendi', 'grup_shift' => 'C', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Bella Alnanda', 'grup_shift' => 'C', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Muhammad Ibu Agustio', 'grup_shift' => 'C', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Abdullah', 'grup_shift' => 'C', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Zulkarnain', 'grup_shift' => 'C', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Gita Adzaniah', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Deki', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Zainul Fuad', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Muhammad Thomas', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Ramadani', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Yoga Indryantoro', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Faqih Hariansyah', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Gusti Sandi Waskito', 'grup_shift' => 'C', 'discipline' => 'Operator Power Distribution'],

            // TEAM D
            ['nama' => 'Syahrul Rozi', 'grup_shift' => 'D', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Irfan Anjas', 'grup_shift' => 'D', 'discipline' => 'Operator DCS Boiler'],
            ['nama' => 'Andika Erfiadi', 'grup_shift' => 'D', 'discipline' => 'Operator Boiler'],
            ['nama' => 'M.Yusri Alfani', 'grup_shift' => 'D', 'discipline' => 'Operator Boiler'],
            ['nama' => 'Khafif Arif Ramadhan', 'grup_shift' => 'D', 'discipline' => 'Operator DCS Turbine'],
            ['nama' => 'Junada', 'grup_shift' => 'D', 'discipline' => 'Operator Turbine'],
            ['nama' => 'M. Rinaldi', 'grup_shift' => 'D', 'discipline' => 'Operator Turbine'],
            ['nama' => 'Muhammad Qudusbi Islam', 'grup_shift' => 'D', 'discipline' => 'Operator Generator'],
            ['nama' => 'Mikha', 'grup_shift' => 'D', 'discipline' => 'Operator Generator'],
            ['nama' => 'Unasih Adawiyah', 'grup_shift' => 'D', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Abdul Azis', 'grup_shift' => 'D', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Muhammad Rasyid Ridho', 'grup_shift' => 'D', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Riswan Zazuri', 'grup_shift' => 'D', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Syaiful Priyadi', 'grup_shift' => 'D', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Zaini Roni', 'grup_shift' => 'D', 'discipline' => 'Operator C&AHS'],
            ['nama' => 'Nabila Mitas', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Haikal Pratama', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Prasetiawan Winarno', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Rahim Ramawan', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Syarif Muhammad Raflic', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Muhammad Dani', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
            ['nama' => 'Rafi Putratama', 'grup_shift' => 'D', 'discipline' => 'Operator Power Distribution'],
        ];

        // 3. Helper to resolve Unit from Discipline description
        $getUnitFromDiscipline = function ($discipline) {
            $desc = strtolower($discipline);
            if (str_contains($desc, 'boiler') || str_contains($desc, 'turbine') || str_contains($desc, 'generator')) {
                return 'BTG';
            } elseif (str_contains($desc, 'c&ahs')) {
                return 'C&AHS';
            } elseif (str_contains($desc, 'power distribution')) {
                return 'Power Distribution';
            }
            return 'BTG'; // fallback
        };

        // 4. Insert Operators
        $createdOperators = [];
        foreach ($operators as $idx => $op) {
            $num = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
            $unit = $getUnitFromDiscipline($op['discipline']);
            
            $created = Operator::create([
                'id_operator' => 'OP' . $num,
                'nama' => $op['nama'],
                'unit' => $unit,
                'grup_shift' => $op['grup_shift'],
                'status' => 'Aktif',
                'npk' => '10' . $num,
                'keterangan' => $op['discipline'], // Simpan disiplin di kolom keterangan
            ]);
            $createdOperators[] = $created;
        }

        // 5. Create Absensis for all periodes in the database
        $periodes = Periode::all();
        foreach ($periodes as $periode) {
            $totalDays = $periode->total_hari;
            $isMei = $periode->kode_periode === 'MEI2026'; // active period

            foreach ($createdOperators as $operator) {
                $codes = [];
                if ($isMei) {
                    // Active period gets seeded with spaces (empty)
                    $kodeString = str_repeat(' ', $totalDays);
                    $s1 = $s2 = $s3 = $h = $s = $i_val = $a = $ct = $ck = $totalShift = 0;
                } else {
                    // Other periods get seeded with realistic random codes
                    for ($d = 0; $d < $totalDays; $d++) {
                        $rand = rand(0, 100);
                        if ($rand < 28) {
                            $codes[] = '1';
                        } elseif ($rand < 56) {
                            $codes[] = '2';
                        } elseif ($rand < 84) {
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
                }

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
                    'jml_libur_nasional' => 0,
                    'jml_cuti_biasa' => 0,
                    'jml_cuti_khusus' => 0,
                    'total_shift' => $totalShift,
                    'keterangan' => $isMei ? 'Entri Awal' : 'Data Seeding Historis',
                ]);
            }
        }
    }
}
