<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(bool $terverifikasi = true): User
    {
        $user = User::create([
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => 2024,
            'password' => 'rahasia123',
        ]);

        $user->forceFill(['is_verified' => $terverifikasi])->save();

        return $user;
    }

    public function test_halaman_login_tampil(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Lupa Password?')
            ->assertSee('Belum punya akun?');
    }

    public function test_login_berhasil_untuk_user_terverifikasi(): void
    {
        $user = $this->buatUser();

        $this->post('/login', [
            'email' => '2410631170172@student.unsika.ac.id',
            'password' => 'rahasia123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_dinormalkan_huruf_kecil_dan_spasi_dibuang(): void
    {
        $user = $this->buatUser();

        $this->post('/login', [
            'email' => '  2410631170172@STUDENT.unsika.ac.id ',
            'password' => 'rahasia123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_salah_ditolak(): void
    {
        $this->buatUser();

        $this->from('/login')->post('/login', [
            'email' => '2410631170172@student.unsika.ac.id',
            'password' => 'salah-banget',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_email_tidak_terdaftar_memakai_pesan_yang_sama(): void
    {
        $this->from('/login')->post('/login', [
            'email' => '9999999999999@student.unsika.ac.id',
            'password' => 'rahasia123',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_field_kosong_ditolak(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_user_belum_verifikasi_diarahkan_ke_otp_dan_dikirimi_kode(): void
    {
        Mail::fake();
        $user = $this->buatUser(false);

        $this->post('/login', [
            'email' => '2410631170172@student.unsika.ac.id',
            'password' => 'rahasia123',
        ])->assertRedirect('/otp');

        $this->assertGuest();
        $this->assertSame($user->user_id, session('otp_user_id'));
        Mail::assertSent(OtpMail::class, 1);
    }

    public function test_user_belum_verifikasi_tidak_dikirimi_kode_baru_sebelum_60_detik(): void
    {
        Mail::fake();
        $this->buatUser(false);
        $data = [
            'email' => '2410631170172@student.unsika.ac.id',
            'password' => 'rahasia123',
        ];

        $this->post('/login', $data)->assertRedirect('/otp');
        $this->post('/login', $data)->assertRedirect('/otp');

        Mail::assertSent(OtpMail::class, 1);
    }
}