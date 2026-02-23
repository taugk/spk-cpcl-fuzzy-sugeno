<?php

namespace App\Http\Controllers\Uptd;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('uptd.dashboard.index');
    }
}
