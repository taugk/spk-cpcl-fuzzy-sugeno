<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthService
{
    public function attemptLogin(array $credentials, bool $remember): ?User
    {
        // Tambahkan syarat status aktif
        $credentials['status'] = 'aktif';

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            
            // Update last login
            $user->last_login_at = now();
            $user->save();

            return $user;
        }

        return null;
    }

    public function getRedirectPath(User $user): string
    {
        // List semua role yang dianggap admin (Super, Pangan, Hartibun)
        $adminRoles = ['admin', 'admin_pangan', 'admin_hartibun'];

        if (in_array($user->role, $adminRoles)) {
            // Semua admin diarahkan ke SATU nama route yang sama
            return route('admin.dashboard');
        } 
        
        if ($user->role === 'uptd') {
            return route('uptd.dashboard');
        }

        return route('login'); 
    }

    public function logout(): void
    {
        Auth::logout();
    }
}