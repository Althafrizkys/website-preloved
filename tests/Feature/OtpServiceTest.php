<?php

namespace Tests\Feature;

use App\Models\EmailVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'password' => 'rahasia123',
        ]);
    }

    public function test_issue_membuat_kode_6_digit_dan_menyimpan_hash(): void
    {
        $user = $this->user();
        $code = (new OtpService)->issue($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $row = EmailVerification::first();
        $this->assertNotSame($code, $row->token);
        $this->assertSame(0, $row->attempts);
        $this->assertEqualsWithDelta(300, now()->diffInSeconds($row->expires_at), 2);
    }

    public function test_kode_benar_memverifikasi_user(): void
    {
        $user = $this->user();
        $service = new OtpService;
        $code = $service->issue($user);

        $this->assertSame(OtpService::OK, $service->verify($user, $code));

        $user->refresh();
        $this->assertTrue((bool) $user->is_verified);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull(EmailVerification::first()->verified_at);
    }

    public function test_kode_salah_menambah_percobaan_dan_terkunci_setelah_5_kali(): void
    {
        $user = $this->user();
        $service = new OtpService;
        $code = $service->issue($user);
        $salah = $code === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->assertSame(OtpService::INVALID, $service->verify($user, $salah));
        }
        $this->assertSame(OtpService::LOCKED, $service->verify($user, $salah));

        // Setelah terkunci, kode yang benar pun ditolak.
        $this->assertSame(OtpService::LOCKED, $service->verify($user, $code));
        $this->assertFalse((bool) $user->refresh()->is_verified);
    }

    public function test_kode_kedaluwarsa_setelah_5_menit(): void
    {
        $user = $this->user();
        $service = new OtpService;
        $code = $service->issue($user);

        $this->travel(6)->minutes();

        $this->assertSame(OtpService::EXPIRED, $service->verify($user, $code));
    }

    public function test_kirim_ulang_baru_boleh_setelah_60_detik(): void
    {
        $user = $this->user();
        $service = new OtpService;

        $this->assertSame(0, $service->secondsUntilResend($user));

        $service->issue($user);
        $this->assertGreaterThan(0, $service->secondsUntilResend($user));

        $this->travel(61)->seconds();
        $this->assertSame(0, $service->secondsUntilResend($user));
    }

    public function test_kode_baru_membatalkan_kode_lama(): void
    {
        $user = $this->user();
        $service = new OtpService;

        $lama = $service->issue($user);
        $baru = $service->issue($user);

        $this->assertSame(1, EmailVerification::count());

        if ($lama !== $baru) {
            $this->assertSame(OtpService::INVALID, $service->verify($user, $lama));
        }
        $this->assertSame(OtpService::OK, $service->verify($user, $baru));
    }

    public function test_user_tanpa_kode_menghasilkan_none(): void
    {
        $this->assertSame(OtpService::NONE, (new OtpService)->verify($this->user(), '123456'));
    }
}