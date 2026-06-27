<?php

namespace App\Imports;

use App\Models\Operator;
use App\Models\Periode;
use App\Services\AbsensiCalculator;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AbsensiImport implements ToArray, WithHeadingRow
{
    protected $periode;
    protected $calculator;
    protected $errors = [];
    protected $parsedData = [];

    public function __construct(Periode $periode, AbsensiCalculator $calculator)
    {
        $this->periode = $periode;
        $this->calculator = $calculator;
    }

    /**
     * Map Excel row to data and perform validation.
     *
     * @param array $rows
     */
    public function array(array $rows)
    {
        $expectedLength = $this->periode->total_hari;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // Row number in Excel (1-indexed + header row)
            
            // Clean keys (case insensitive / trimmed)
            $cleanedRow = [];
            foreach ($row as $key => $value) {
                $cleanedRow[trim(strtolower($key))] = trim($value);
            }

            $idOperator = $cleanedRow['id_operator'] ?? $cleanedRow['id operator'] ?? null;
            $namaExcel = $cleanedRow['nama_operator'] ?? $cleanedRow['nama operator'] ?? $cleanedRow['nama'] ?? null;
            $kodeString = $cleanedRow['kode_string'] ?? $cleanedRow['kode string'] ?? $cleanedRow['kode'] ?? null;
            $keterangan = $cleanedRow['keterangan'] ?? '';

            // Skip empty rows
            if (empty($idOperator) && empty($namaExcel) && empty($kodeString)) {
                continue;
            }

            $rowErrors = [];

            // 1. Validate ID Operator
            if (empty($idOperator)) {
                $rowErrors[] = "ID Operator tidak boleh kosong.";
                $operator = null;
            } else {
                $operator = Operator::where('id_operator', $idOperator)->first();
                if (!$operator) {
                    $rowErrors[] = "Operator dengan ID '{$idOperator}' tidak ditemukan di database.";
                } elseif ($operator->status !== 'Aktif') {
                    $rowErrors[] = "Operator '{$operator->nama}' (ID: {$idOperator}) berstatus Nonaktif.";
                }
            }

            // 2. Validate Kode String
            if (empty($kodeString)) {
                $rowErrors[] = "Kode string absensi tidak boleh kosong.";
            } else {
                $kodeString = strtoupper($kodeString);
                $len = strlen($kodeString);
                if ($len !== $expectedLength) {
                    $rowErrors[] = "Panjang kode string ({$len} karakter) tidak sesuai dengan jumlah hari periode ({$expectedLength} hari).";
                }

                // Check characters
                if (preg_match('/[^123HSIACKL]/', $kodeString)) {
                    $rowErrors[] = "Kode string mengandung karakter tidak valid. Hanya boleh berisi: 1, 2, 3, H, S, I, A, C, K, L.";
                }
            }

            // Calculate totals if no validation errors on code string
            $totals = [];
            if (!empty($kodeString) && !preg_match('/[^123HSIACK]/', $kodeString) && strlen($kodeString) === $expectedLength) {
                $totals = $this->calculator->calculateFromKodeString($kodeString);
            }

            if (!empty($rowErrors)) {
                $this->errors[] = [
                    'row' => $rowNum,
                    'id_operator' => $idOperator,
                    'nama' => $operator ? $operator->nama : ($namaExcel ?? '—'),
                    'errors' => $rowErrors,
                ];
            } else {
                $this->parsedData[] = [
                    'row' => $rowNum,
                    'operator_id' => $operator->id,
                    'id_operator' => $operator->id_operator,
                    'nama' => $operator->nama,
                    'unit' => $operator->unit,
                    'grup_shift' => $operator->grup_shift,
                    'kode_string' => $kodeString,
                    'totals' => $totals,
                    'keterangan' => $keterangan,
                ];
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getParsedData(): array
    {
        return $this->parsedData;
    }
}
