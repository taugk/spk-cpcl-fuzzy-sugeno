<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Cek apakah role user ada di dalam parameter middleware
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Redirect cerdas jika mencoba melompati pagar akses
        $adminRoles = ['admin', 'admin_pangan', 'admin_hartibun'];
        
        if (in_array($user->role, $adminRoles)) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Anda tidak memiliki izin akses ke halaman tersebut.');
        } 
        
        if ($user->role === 'uptd') {
            return redirect()->route('uptd.dashboard')
                ->with('error', 'Halaman ini hanya untuk Administrator.');
        }

        abort(403, 'Akses Ditolak');
    }
}