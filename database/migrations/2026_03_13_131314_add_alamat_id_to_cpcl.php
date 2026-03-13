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
            // Tambahkan kolom alamat_id sebagai foreign key
            $table->foreignId('alamat_id')
                  ->nullable()
                  ->constrained('alamat')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpcl', function (Blueprint $table) {
            $table->dropForeign(['alamat_id']);
            $table->dropColumn('alamat_id');
        });
    }
};
