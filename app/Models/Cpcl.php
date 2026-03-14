<?php

namespace App\Models;

use App\Models\HasilFuzzy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Cpcl extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cpcl';

    protected $fillable = [
        'nama_kelompok',
        'nama_ketua',
        'nik_ketua',
        'bidang',
        'rencana_usaha',
        
        'lokasi',
        'luas_lahan',
        'status_lahan',
        'lama_berdiri',
        'hasil_panen',
        'latitude',
        'longitude',        
        'file_proposal',
        'file_ktp',
        'file_sk',
        'foto_lahan',
        'status',
        'catatan_verifikator',
        'alamat_id',
    ];

    protected $casts = [
        'luas_lahan' => 'float',
        'hasil_panen' => 'float',
        'lama_berdiri' => 'integer',
    ];

    // ============================================================
    // FILE ACCESSORS (AMAN UNTUK VIEW & DOWNLOAD)
    // ============================================================

    public function getFotoLahanUrlAttribute()
{
    return $this->foto_lahan && Storage::disk('public')->exists($this->foto_lahan)
        ? asset('storage/'.$this->foto_lahan)
        : asset('assets/img/illustrations/no-image.png');
}

public function getFileProposalUrlAttribute()
{
    return $this->file_proposal && Storage::disk('public')->exists($this->file_proposal)
        ? asset('storage/'.$this->file_proposal)
        : null;
}

public function getFileKtpUrlAttribute()
{
    return $this->file_ktp && Storage::disk('public')->exists($this->file_ktp)
        ? asset('storage/'.$this->file_ktp)
        : null;
}

public function getFileSkUrlAttribute()
{
    return $this->file_sk && Storage::disk('public')->exists($this->file_sk)
        ? asset('storage/'.$this->file_sk)
        : null;
}

    // ============================================================
    // STATUS & UI HELPER
    // ============================================================

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'baru' => 'Baru Masuk',
            'terverifikasi' => 'Terverifikasi',
            'perlu_perbaikan' => 'Perlu Perbaikan',
            'diproses' => 'Sedang Diproses',
            default => 'Tidak Diketahui',
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'baru' => 'bg-label-info',
            'terverifikasi' => 'bg-label-success',
            'perlu_perbaikan' => 'bg-label-warning',
            'diproses' => 'bg-label-primary',
            default => 'bg-label-secondary',
        };
    }

    //jika berhasil buat cpcl, maka buat alamat baru, lalu hubungkan cpcl dengan alamat tsb melalui alamat_id


    // ============================================================
    // BUSINESS LOGIC
    // ============================================================

    public function isVerified()
    {
        return $this->status === 'terverifikasi';
    }

    public function needRevision()
    {
        return $this->status === 'perlu_perbaikan';
    }

    public function Penilaian()
    {
        return $this->hasMany(Penilaian::class);
    }

    public function hasilFuzzy()
    {
        return $this->hasOne(HasilFuzzy::class);
    }

    public function alamat()
    {
        return $this->belongsTo(Alamat::class, 'alamat_id');
    }
}
