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
        Schema::create('cpcl', function (Blueprint $table) {
            $table->id();
 
            // BAGIAN 1: INFORMASI KELOMPOK & USULAN
            $table->string('nama_kelompok');
            $table->string('nama_ketua');
            $table->string('nik_ketua', 16);
            $table->string('bidang');
            $table->string('rencana_usaha');
            $table->text('lokasi');

            // BAGIAN 2: DATA OPERASIONAL & TEKNIS
            $table->decimal('luas_lahan', 8, 2);
            $table->integer('lama_berdiri'); // dalam tahun
            $table->decimal('hasil_panen', 8, 2); // ton/ha
            $table->string('status_lahan'); // milik sendiri / sewa / bagi hasil

            // DATA SPASIAL (KOORDINAT)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // BAGIAN 3: LAMPIRAN DOKUMEN
            $table->string('file_proposal');
            $table->string('file_ktp');
            $table->string('file_sk')->nullable();
            $table->string('foto_lahan')->nullable();

            // STATUS TRACKING
            $table->string('status')->default('baru'); // baru, terverifikasi, ditolak
            $table->text('catatan_verifikator')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpcl');
    }
};