<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    // Email yang sedang menunggu kode (tersimpan di session, tanpa membuka apakah email terdaftar).
    private const SESSION_EMAIL = 'password_reset_email';

    // User yang kodenya sudah benar dan boleh mengisi password baru.
    private const SESSION_VERIFIED = 'password_reset_verified';

    // Batas waktu mengisi password baru setelah kode benar.
    private const VERIFIED_WINDOW_MINUTES = 10;

    public function __construct(private PasswordResetService $resets) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate(
            ['email' => ['required', 'email']],
            [
                'email.required' => 'Email kampus wajib diisi.',
                'email.email' => 'Format email tidak valid.',
            ]
        );

        $email = (string) $request->input('email');
        $user = User::where('email', $email)->first();

        // Pesan ke user selalu sama, terdaftar atau tidak. Kode hanya dikirim bila akun ada
        // dan kode sebelumnya sudah lewat 60 detik.
        if ($user !== null && $this->resets->secondsUntilResend($user) === 0) {
            Mail::to($user->email)->send(new PasswordResetMail($this->resets->issue($user)));
        }

        $request->session()->forget(self::SESSION_VERIFIED);
        $request->session()->put(self::SESSION_EMAIL, $email);

        return redirect()->route('password.otp');
    }

    public function otp(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('password.request');
        }

        $user = $this->userForEmail($email);

        return view('auth.otp', [
            'email' => $email,
            'wait' => $user === null ? 0 : $this->resets->secondsUntilResend($user),
            'verifyRoute' => 'password.otp.verify',
            'resendRoute' => 'password.otp.resend',
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('password.request');
        }

        $request->validate(
            ['code' => ['required', 'digits:6']],
            [
                'code.required' => 'Kode OTP wajib diisi.',
                'code.digits' => 'Kode OTP harus 6 digit angka.',
            ]
        );

        $user = $this->userForEmail($email);

        // Email yang tidak terdaftar selalu dijawab "kode salah".
        $result = $user === null
            ? PasswordResetService::INVALID
            : $this->resets->verify($user, (string) $request->input('code'));

        if ($result === PasswordResetService::OK) {
            $request->session()->forget(self::SESSION_EMAIL);
            $request->session()->put(self::SESSION_VERIFIED, [
                'user_id' => $user->user_id,
                'at' => now()->getTimestamp(),
            ]);

            return redirect()->route('password.reset');
        }

        $pesan = match ($result) {
            PasswordResetService::INVALID => 'Kode OTP salah.',
            PasswordResetService::EXPIRED => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode.',
            PasswordResetService::LOCKED => 'Terlalu banyak percobaan salah. Kirim ulang kode.',
            default => 'Kode OTP tidak ditemukan. Kirim ulang kode.',
        };

        return back()->withErrors(['code' => $pesan]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('password.request');
        }

        $user = $this->userForEmail($email);

        if ($user !== null) {
            $wait = $this->resets->secondsUntilResend($user);

            if ($wait > 0) {
                return back()->withErrors(['code' => "Tunggu {$wait} detik sebelum kirim ulang kode."]);
            }

            Mail::to($user->email)->send(new PasswordResetMail($this->resets->issue($user)));
        }

        return back()->with('status', 'Kode baru sudah dikirim.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if ($this->verifiedUser($request) === null) {
            return redirect()->route('password.request');
        }

        return view('auth.new-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->verifiedUser($request);

        if ($user === null) {
            return redirect()->route('password.request');
        }

        $request->validate(
            ['password' => ['required', 'string', 'min:8', 'confirmed']],
            [
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak sama.',
            ]
        );

        $user->update(['password' => $request->input('password')]);
        $this->resets->markUsed($user);

        $request->session()->forget([self::SESSION_VERIFIED, self::SESSION_EMAIL]);

        return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan login.');
    }

    private function userForEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * User yang kodenya sudah benar dan masih dalam batas waktu mengisi password baru.
     */
    private function verifiedUser(Request $request): ?User
    {
        $data = $request->session()->get(self::SESSION_VERIFIED);

        if (! is_array($data)) {
            return null;
        }

        $age = now()->getTimestamp() - (int) ($data['at'] ?? 0);

        if ($age > self::VERIFIED_WINDOW_MINUTES * 60) {
            return null;
        }

        return User::find($data['user_id'] ?? null);
    }
}