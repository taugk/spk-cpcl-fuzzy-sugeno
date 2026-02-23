<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    protected $table = 'cpcl_penilaian';

    protected $fillable = [
        'cpcl_id',
        'kriteria_id',
        'sub_kriteria_id',
        'nilai_input',
    ];

    public function cpcl()
    {
        return $this->belongsTo(Cpcl::class, 'cpcl_id');
    }

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id');
    }

    public function subKriteria()
    {
        return $this->belongsTo(SubKriteria::class, 'sub_kriteria_id');
    }
}
