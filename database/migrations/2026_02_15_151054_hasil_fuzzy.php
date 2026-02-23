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
        Schema::create('hasil_fuzzy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpcl_id')->constrained('cpcl')->cascadeOnDelete();
            
            // Tambahkan kolom ini untuk menyimpan detail perhitungan
            $table->decimal('nilai_alpha', 8, 4)->nullable(); // Menyimpan Firing Strength (min)
            $table->decimal('nilai_z', 8, 4)->nullable();     // Menyimpan hasil pembagian (ki)
            
            $table->decimal('skor_akhir', 8, 2);              // Nilai akhir (0-100)
            $table->string('status_kelayakan');               // Layak / Tidak Layak
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_fuzzy');
    }
};
