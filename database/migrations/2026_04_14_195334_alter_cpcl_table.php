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
        Schema::table('cpcl', function (Blueprint $table) {
            // Mengubah kolom menjadi nullable
            $table->string('file_proposal')->nullable()->change();
            $table->string('file_ktp')->nullable()->change();
            $table->string('file_sk')->nullable()->change();
            $table->string('foto_lahan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpcl', function (Blueprint $table) {
        // Kembalikan ke kondisi semula (tidak boleh kosong) jika di-rollback
        $table->string('file_proposal')->nullable(false)->change();
        $table->string('file_ktp')->nullable(false)->change();
        
        // file_sk dan foto_lahan tetap nullable sesuai struktur lama Anda
        $table->string('file_sk')->nullable()->change();
        $table->string('foto_lahan')->nullable()->change();
    });
    }
};
