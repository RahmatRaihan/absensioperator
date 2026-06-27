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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periodes')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('operators')->onDelete('cascade');
            $table->string('kode_string', 31); // 28 to 31 chars depending on period total days
            $table->integer('jml_shift1')->default(0);
            $table->integer('jml_shift2')->default(0);
            $table->integer('jml_shift3')->default(0);
            $table->integer('jml_holiday')->default(0);
            $table->integer('jml_sakit')->default(0);
            $table->integer('jml_izin')->default(0);
            $table->integer('jml_alpa')->default(0);
            $table->integer('total_shift')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // An operator can only have one attendance record per period
            $table->unique(['periode_id', 'operator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
