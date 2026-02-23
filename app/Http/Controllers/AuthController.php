<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService; 

class AuthController extends Controller
{
    protected $authService;

  
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login()
    {
        return view('auth.login');
    }

    public function loginProses(Request $request)
    {
       
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

    
        $user = $this->authService->attemptLogin(
            $request->only('username', 'password'), 
            $request->has('remember')
        );

        if ($user) {
            $request->session()->regenerate();
            
           
            $redirectPath = $this->authService->getRedirectPath($user);
            
            return redirect()->intended($redirectPath);
        }

      
        return back()->withErrors([
            'username' => 'Login gagal! Cek username, password, atau status akun.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $this->authService->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}