<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string ...$roles  (Menangkap parameter role dari route, misal: 'admin')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. Ambil user yang sedang login
        $user = Auth::user();

        // 3. LOGIKA UTAMA (JANGAN DI-OVERWRITE)
        // Kita cek apakah role user saat ini ada di dalam daftar $roles yang dikirim dari Route
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // 4. PENANGANAN JIKA ROLE TIDAK COCOK (Smart Redirect)
        // Jika UPTD coba masuk Admin, atau Admin coba masuk UPTD -> Kembalikan ke dashboard asli mereka
        
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        } 
        
        if ($user->role === 'uptd') {
            return redirect()->route('uptd.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman Admin.');
        }

        // Fallback terakhir jika role aneh/tidak dikenali
        abort(403, 'Akses Ditolak');
    }
}