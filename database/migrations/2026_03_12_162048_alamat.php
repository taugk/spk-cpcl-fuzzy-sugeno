<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alamat', function (Blueprint $table) {
            $table->id();

            // kode wilayah
            $table->string('kd_kab');
            $table->string('kd_kec');
            $table->string('kd_desa');

            // nama wilayah
            $table->string('kabupaten');
            $table->string('kecamatan');
            $table->string('desa');

            $table->timestamps();

            // 1 kombinasi wilayah hanya boleh ada 1 baris
            $table->unique(['kd_kab', 'kd_kec', 'kd_desa'], 'alamat_wilayah_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alamat');
    }
};