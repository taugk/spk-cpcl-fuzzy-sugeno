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
        Schema::create('sub_kriteria', function (Blueprint $table) {
            $table->id();

            // Relasi ke kriteria induk
            $table->foreignId('kriteria_id')
                ->constrained('kriteria')
                ->cascadeOnDelete();

            $table->string('nama_sub_kriteria');

            // TIPE KURVA: Kunci agar sistem tahu cara menghitungnya
            $table->enum('tipe_kurva', [
                'bahu_kiri', 
                'trapesium', 
                'bahu_kanan', 
                'diskrit'
            ]);

            // PARAMETER KONTINU (a, b, c, d) - Diubah jadi nullable()
            $table->decimal('batas_bawah', 10, 2)->nullable();    // Batas a
            $table->decimal('batas_tengah_1', 10, 2)->nullable(); // Batas b
            $table->decimal('batas_tengah_2', 10, 2)->nullable(); // Batas c
            $table->decimal('batas_atas', 10, 2)->nullable();     // Batas d

            // PARAMETER DISKRIT - Diubah jadi nullable()
            $table->decimal('nilai_diskrit', 4, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_kriteria');
    }
};