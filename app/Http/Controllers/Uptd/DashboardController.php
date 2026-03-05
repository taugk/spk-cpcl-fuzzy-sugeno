<?php

namespace App\Http\Controllers\Uptd;

use App\Http\Controllers\Controller;
use App\Models\Cpcl; // Sesuaikan dengan model Anda
use App\Models\Verifikasi;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_cpcl'    => Cpcl::count(),
            'pending'       => Cpcl::where('status', 'pending')->count(),
            'verified'      => Cpcl::where('status', 'terverifikasi')->count(),
            'urgent_tasks'  => Cpcl::where('created_at', '>=', now()->subDays(3))->count(),
        ];

        $recent_activities = Cpcl::latest()->take(5)->get();

        return view('uptd.dashboard.index', compact('stats', 'recent_activities'));
    }
}