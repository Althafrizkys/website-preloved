<?php

namespace App\Services;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    public const LIFETIME_MINUTES = 5;

    public const RESEND_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    // Hasil verifikasi.
    public const OK = 'ok';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    public const LOCKED = 'locked';

    public const NONE = 'none';

    /**
     * Membuat kode baru dan membatalkan kode lama yang belum dipakai.
     * Mengembalikan kode asli (untuk dikirim lewat email); yang disimpan hanya hash-nya.
     */
    public function issue(User $user): string
    {
        PasswordReset::where('user_id', $user->user_id)
            ->whereNull('used_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordReset::create([
            'user_id' => $user->user_id,
            'token' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
        ]);

        return $code;
    }

    public function secondsUntilResend(User $user): int
    {
        $latest = $this->latest($user);

        if ($latest === null) {
            return 0;
        }

        $allowedAt = $latest->created_at->getTimestamp() + self::RESEND_SECONDS;

        return max(0, $allowedAt - now()->getTimestamp());
    }

    /**
     * Memeriksa kode yang diinput. Mengembalikan salah satu konstanta hasil di atas.
     * Kode baru ditandai terpakai lewat markUsed() setelah password berhasil diganti.
     */
    public function verify(User $user, string $code): string
    {
        $row = $this->latest($user);

        if ($row === null || $row->used_at !== null) {
            return self::NONE;
        }

        if ($row->expires_at->isPast()) {
            return self::EXPIRED;
        }

        if ($row->attempts >= self::MAX_ATTEMPTS) {
            return self::LOCKED;
        }

        if (! Hash::check($code, $row->token)) {
            $row->increment('attempts');

            return $row->attempts >= self::MAX_ATTEMPTS ? self::LOCKED : self::INVALID;
        }

        return self::OK;
    }

    /**
     * Menandai kode terakhir sebagai terpakai agar tidak bisa dipakai lagi.
     */
    public function markUsed(User $user): void
    {
        $row = $this->latest($user);

        if ($row !== null && $row->used_at === null) {
            $row->update(['used_at' => now()]);
        }
    }

    private function latest(User $user): ?PasswordReset
    {
        return PasswordReset::where('user_id', $user->user_id)
            ->orderByDesc('reset_id')
            ->first();
    }
}