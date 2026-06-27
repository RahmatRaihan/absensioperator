<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('keterangan_absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_id')->constrained('absensis')->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('kode', ['S', 'I', 'A']); // Sakit, Izin, Alpa
            $table->text('alasan');
            $table->timestamps();

            // Only one specific code reason per date per operator absensi
            $table->unique(['absensi_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keterangan_absensis');
    }
};
