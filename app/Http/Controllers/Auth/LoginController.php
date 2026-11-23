<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, OtpService $otp): RedirectResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        // Pesan sama untuk email tidak terdaftar, akun admin (login lewat /admin/login), maupun password salah.
        if ($user === null || $user->isAdmin() || ! Hash::check($request->input('password'), $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        // Akun dinonaktifkan oleh admin.
        if (! $user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun kamu telah dinonaktifkan oleh admin.']);
        }

        // Akun belum diverifikasi: lanjutkan ke halaman OTP.
        if (! $user->is_verified) {
            if ($otp->secondsUntilResend($user) === 0) {
                Mail::to($user->email)->send(new OtpMail($otp->issue($user)));
            }

            $request->session()->put('otp_user_id', $user->user_id);

            return redirect()->route('otp.show');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}