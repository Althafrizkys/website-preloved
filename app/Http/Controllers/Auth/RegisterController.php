<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, OtpService $otp): RedirectResponse
    {
        $user = User::create([
            'email' => $request->input('email'),
            'npm' => $request->input('npm'),
            'campus' => $request->campus(),
            'cohort' => $request->cohort(),
            'password' => $request->input('password'),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp->issue($user)));

        $request->session()->put('otp_user_id', $user->user_id);

        return redirect()->route('otp.show');
    }
}