<?php

namespace App\Models;

use App\Models\HasilFuzzy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class Alamat extends Model
{
    use HasFactory;
    protected $table = 'alamat';
    
    protected $fillable = [
        'kd_kab',
        'kabupaten',
        'kd_kec',
        'kecamatan',
        'kd_desa',
        'desa'
    ];

    


    public function cpcl()
    {
        return $this->hasOne(Cpcl::class, 'alamat_id');
    }


}
