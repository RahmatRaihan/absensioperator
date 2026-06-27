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
        Schema::table('absensis', function (Blueprint $table) {
            $table->integer('jml_cuti_biasa')->default(0)->after('jml_alpa');
            $table->integer('jml_cuti_khusus')->default(0)->after('jml_cuti_biasa');
        });

        Schema::table('keterangan_absensis', function (Blueprint $table) {
            $table->string('kode', 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keterangan_absensis', function (Blueprint $table) {
            $table->enum('kode', ['S', 'I', 'A'])->change();
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['jml_cuti_biasa', 'jml_cuti_khusus']);
        });
    }
};
