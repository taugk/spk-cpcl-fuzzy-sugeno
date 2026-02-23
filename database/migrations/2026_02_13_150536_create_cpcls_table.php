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
            $table->integer('lama_berdiri'); // Dalam tahun
            $table->decimal('hasil_panen', 8, 2); // Ton/Ha
            $table->string('status_lahan'); // Milik sendiri / Sewa / Bagi hasil
            
            // DATA SPASIAL (KOORDINAT)
            // Menggunakan decimal(10,8) dan (11,8) adalah standar presisi koordinat peta
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // BAGIAN 3: LAMPIRAN DOKUMEN (Path File)
            $table->string('file_proposal');
            $table->string('file_ktp');
            $table->string('file_sk')->nullable();
            $table->string('foto_lahan')->nullable();
            
            // STATUS TRACKING
            // 'baru', 'terverifikasi', 'ditolak'
            $table->string('status')->default('baru');
            $table->text('catatan_verifikator')->nullable(); // Untuk alasan jika ditolak
            
            $table->timestamps();
            $table->softDeletes(); // Opsional: agar data tidak langsung hilang saat dihapus
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