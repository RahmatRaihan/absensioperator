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
        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->string('kode_periode')->unique(); // e.g. NOV2025
            $table->string('label'); // e.g. November 2025
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->integer('total_hari');
            $table->enum('status', ['Aktif', 'Selesai'])->default('Aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodes');
    }
};
