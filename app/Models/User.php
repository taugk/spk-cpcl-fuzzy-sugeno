<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use  HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'users';
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',           // 'admin' atau 'uptd'
        'status',         // 'aktif' atau 'nonaktif'
        'last_login_at',
        'created_by',     // ID user yang membuat akun ini
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime', // Agar otomatis jadi object Carbon/Date
        'password' => 'hashed',
    ];

    /**
     * Relasi ke user yang membuat akun ini (Self-referencing).
     * Contoh: User B dibuat oleh User A.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper untuk cek apakah user adalah admin.
     * Cara pakai: if ($user->isAdmin()) { ... }
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Helper untuk cek apakah user adalah tim UPTD.
     */
    public function isUptd(): bool
    {
        return $this->role === 'uptd';
    }

    /**
     * Helper untuk cek status aktif.
     */
    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }
}