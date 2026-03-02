<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilFuzzy extends Model
{
    protected $table = 'hasil_fuzzy';
    protected $guarded = [];

    public function cpcl()
    {
        return $this->belongsTo(Cpcl::class, 'cpcl_id', 'id');
    }
}
