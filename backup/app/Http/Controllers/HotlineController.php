<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EmergencyHotline;

class HotlineController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $barangay = $user->barangay ?? null;
        $city = $user->cityMunicipality ?? null;

        $hotlines = EmergencyHotline::query()
            ->when($barangay, fn($q) => $q->where('barangay', $barangay))
            ->when($city, fn($q) => $q->where('cityMunicipality', $city))
            ->orderByDesc('createdAt')
            ->get();

        return view('hotlines', [
            'barangay' => $barangay ?? 'Barangay',
            'hotlines' => $hotlines,
            'user' => $user,
        ]);
    }
}
