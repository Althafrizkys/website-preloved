<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): RedirectResponse
    {
        User::create([
            'email' => $request->input('email'),
            'npm' => $request->input('npm'),
            'campus' => $request->campus(),
            'cohort' => $request->cohort(),
            'password' => $request->input('password'),
        ]);

        // Sementara kembali ke beranda; akan diganti ke halaman OTP pada tahap berikutnya.
        return redirect('/')->with('status', 'Pendaftaran berhasil.');
    }
}