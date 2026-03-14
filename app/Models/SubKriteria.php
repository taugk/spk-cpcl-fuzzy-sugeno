<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubKriteria extends Model
{
    protected $table = 'sub_kriteria';

    protected $fillable = [
    'kriteria_id', 
    'nama_sub_kriteria', 
    'tipe_kurva', 
    'batas_bawah', 
    'batas_tengah_1', 
    'batas_tengah_2', 
    'batas_atas', 
    'nilai_konsekuen'
];

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id');
    }
}
