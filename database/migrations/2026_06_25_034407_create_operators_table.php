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
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('id_operator')->unique();
            $table->string('nama');
            $table->enum('unit', ['BTG', 'C&AHS', 'Power Distribution']);
            $table->enum('grup_shift', ['A', 'B', 'C', 'D']);
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->string('npk')->unique();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
