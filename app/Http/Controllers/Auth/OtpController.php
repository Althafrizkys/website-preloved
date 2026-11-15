<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('register');
        }

        return view('auth.otp', [
            'email' => $user->email,
            'wait' => $this->otp->secondsUntilResend($user),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('register');
        }

        $request->validate(
            ['code' => ['required', 'digits:6']],
            [
                'code.required' => 'Kode OTP wajib diisi.',
                'code.digits' => 'Kode OTP harus 6 digit angka.',
            ]
        );

        $result = $this->otp->verify($user, (string) $request->input('code'));

        if ($result === OtpService::OK) {
            $request->session()->forget('otp_user_id');

            return redirect()->route('login')->with('status', 'Email berhasil diverifikasi. Silakan login.');
        }

        $pesan = match ($result) {
            OtpService::INVALID => 'Kode OTP salah.',
            OtpService::EXPIRED => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode.',
            OtpService::LOCKED => 'Terlalu banyak percobaan salah. Kirim ulang kode.',
            default => 'Kode OTP tidak ditemukan. Kirim ulang kode.',
        };

        return back()->withErrors(['code' => $pesan]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('register');
        }

        $wait = $this->otp->secondsUntilResend($user);

        if ($wait > 0) {
            return back()->withErrors(['code' => "Tunggu {$wait} detik sebelum kirim ulang kode."]);
        }

        Mail::to($user->email)->send(new OtpMail($this->otp->issue($user)));

        return back()->with('status', 'Kode baru sudah dikirim.');
    }

    /**
     * User yang baru mendaftar dan belum terverifikasi (dari session).
     */
    private function pendingUser(Request $request): ?User
    {
        $user = User::find($request->session()->get('otp_user_id'));

        if ($user === null || $user->is_verified) {
            return null;
        }

        return $user;
    }
}