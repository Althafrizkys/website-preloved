<?php

namespace Tests\Feature;

use App\Models\PasswordReset;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(): User
    {
        return User::create([
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => 2024,
            'password' => 'rahasia123',
        ]);
    }

    public function test_issue_membuat_kode_6_digit_dan_menyimpan_hash(): void
    {
        $user = $this->buatUser();

        $code = app(PasswordResetService::class)->issue($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $row = PasswordReset::where('user_id', $user->user_id)->first();
        $this->assertNotSame($code, $row->token);
        $this->assertTrue(Hash::check($code, $row->token));
        $this->assertNull($row->used_at);
    }

    public function test_kode_benar_menghasilkan_ok_tanpa_menandai_terpakai(): void
    {
        $user = $this->buatUser();
        $service = app(PasswordResetService::class);
        $code = $service->issue($user);

        $this->assertSame(PasswordResetService::OK, $service->verify($user, $code));
        $this->assertNull(PasswordReset::first()->used_at);
    }

    public function test_kode_salah_menambah_percobaan_dan_terkunci_setelah_5_kali(): void
    {
        $user = $this->buatUser();
        $service = app(PasswordResetService::class);
        $code = $service->issue($user);
        $salah = $code === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->assertSame(PasswordResetService::INVALID, $service->verify($user, $salah));
        }

        $this->assertSame(PasswordResetService::LOCKED, $service->verify($user, $salah));
        // Setelah terkunci, kode yang benar pun ditolak.
        $this->assertSame(PasswordResetService::LOCKED, $service->verify($user, $code));
        $this->assertSame(5, PasswordReset::first()->attempts);
    }

    public function test_kode_kedaluwarsa_setelah_5_menit(): void
    {
        $user = $this->buatUser();
        $service = app(PasswordResetService::class);
        $code = $service->issue($user);

        $this->travel(5)->minutes();
        $this->travel(1)->seconds();

        $this->assertSame(PasswordResetService::EXPIRED, $service->verify($user, $code));
    }

    public function test_kirim_ulang_baru_boleh_setelah_60_detik(): void
    {
        $user = $this->buatUser();
        $service = app(PasswordResetService::class);

        $this->assertSame(0, $service->secondsUntilResend($user));

        $service->issue($user);
        $this->assertGreaterThan(0, $service->secondsUntilResend($user));

        $this->travel(61)->seconds();
        $this->assertSame(0, $service->secondsUntilResend($user));
    }

    public function test_kode_baru_membatalkan_kode_lama(): void
    {
        $user = $this->buatUser();
        $service = app(PasswordResetService::class);

        $lama = $service->issue($user);
        $baru = $service->issue($user);

        $this->assertSame(1, PasswordReset::where('user_id', $user->user_id)->count());
        $this->assertSame(PasswordResetService::OK, $service->verify($user, $baru));

        if ($lama !== $baru) {
            $this->assertSame(PasswordResetService::INVALID, $service->verify($user, $lama));
        }
    }

    public function test_kode_yang_sudah_dipakai_tidak_berlaku_lagi(): void
    {
        $user = $this->buatUser();
        $service = app(PasswordResetService::class);
        $code = $service->issue($user);

        $service->markUsed($user);

        $this->assertNotNull(PasswordReset::first()->used_at);
        $this->assertSame(PasswordResetService::NONE, $service->verify($user, $code));
    }

    public function test_user_tanpa_kode_menghasilkan_none(): void
    {
        $user = $this->buatUser();

        $this->assertSame(PasswordResetService::NONE, app(PasswordResetService::class)->verify($user, '123456'));
    }
}