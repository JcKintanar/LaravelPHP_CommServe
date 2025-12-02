<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        return view('dashboard', [
            'username' => $user->name ?? $user->username ?? 'User',
            'role' => $user->role ?? 'user',
            'barangay' => $user->barangay ?? 'N/A',
            'cityMunicipality' => $user->cityMunicipality ?? 'N/A',
            'user' => $user,
        ]);
    }
}
