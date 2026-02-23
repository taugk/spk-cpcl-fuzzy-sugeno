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
            
            // Logika bisnis: Update last login
            $user->last_login_at = now();
            $user->save();

            return $user;
        }

        return null;
    }

    
    public function getRedirectPath(User $user): string
    {
        if ($user->role === 'admin') {
            return route('admin.dashboard');
        } elseif ($user->role === 'uptd') {
            return route('uptd.dashboard');
        }

        return route('login'); 
    }

    public function logout(): void
    {
        Auth::logout();
    }
}