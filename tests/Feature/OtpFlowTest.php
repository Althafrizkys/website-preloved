<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpFlowTest extends TestCase
{
    use RefreshDatabase;

    private function daftar(): array
    {
        Mail::fake();

        $this->post('/register', [
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/otp');

        $code = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('2410631170172@student.unsika.ac.id');
        });

        return [User::first(), $code];
    }

    public function test_daftar_mengirim_kode_dan_menyimpan_user_di_session(): void
    {
        [$user, $code] = $this->daftar();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertSame($user->user_id, session('otp_user_id'));
        $this->assertFalse((bool) $user->is_verified);
    }

    public function test_kode_benar_memverifikasi_dan_membersihkan_session(): void
    {
        [$user, $code] = $this->daftar();

        $this->post('/otp', ['code' => $code])->assertRedirect('/');

        $this->assertTrue((bool) $user->refresh()->is_verified);
        $this->assertNull(session('otp_user_id'));
    }

    public function test_kode_salah_ditolak(): void
    {
        [$user, $code] = $this->daftar();
        $salah = $code === '000000' ? '111111' : '000000';

        $this->post('/otp', ['code' => $salah])->assertSessionHasErrors('code');

        $this->assertFalse((bool) $user->refresh()->is_verified);
    }

    public function test_kode_harus_6_digit_angka(): void
    {
        $this->daftar();

        $this->post('/otp', ['code' => '12ab'])->assertSessionHasErrors('code');
        $this->post('/otp', ['code' => ''])->assertSessionHasErrors('code');
    }

    public function test_tanpa_session_diarahkan_ke_register(): void
    {
        $this->get('/otp')->assertRedirect('/register');
        $this->post('/otp', ['code' => '123456'])->assertRedirect('/register');
        $this->post('/otp/resend')->assertRedirect('/register');
    }

    public function test_kirim_ulang_ditolak_sebelum_60_detik_lalu_boleh_sesudahnya(): void
    {
        $this->daftar();

        $this->post('/otp/resend')->assertSessionHasErrors('code');
        Mail::assertSent(OtpMail::class, 1);

        $this->travel(61)->seconds();

        $this->post('/otp/resend')->assertSessionHasNoErrors();
        Mail::assertSent(OtpMail::class, 2);
    }

    public function test_halaman_otp_menampilkan_email_dan_enam_kotak(): void
    {
        $this->withoutVite();
        $this->daftar();

        $this->get('/otp')
            ->assertOk()
            ->assertSee('Verifikasi OTP')
            ->assertSee('2410631170172@student.unsika.ac.id');
    }
}