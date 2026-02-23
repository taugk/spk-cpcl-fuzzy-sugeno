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
        Schema::create('cpcl_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpcl_id')->constrained('cpcl')->cascadeOnDelete(); 
            $table->foreignId('kriteria_id')->constrained('kriteria')->cascadeOnDelete();
            
            // Kolom ini yang dicari oleh SQL:
            $table->string('nilai'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpcl_penilaian');
    }
};
