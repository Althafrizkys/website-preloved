<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = '2410631170172@student.unsika.ac.id';

    private function buatUser(): User
    {
        $user = User::create([
            'email' => self::EMAIL,
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => 2024,
            'password' => 'password-lama',
        ]);

        $user->forceFill(['is_verified' => true])->save();

        return $user;
    }

    /**
     * Meminta kode lewat halaman Lupa Password dan mengembalikan kode dari email.
     */
    private function mintaKode(): string
    {
        Mail::fake();

        $this->post('/lupa-password', ['email' => self::EMAIL])
            ->assertRedirect('/lupa-password/otp');

            $code = '';
            Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo(self::EMAIL);
        });

        return $code;
    }

    public function test_halaman_lupa_password_tampil(): void
    {
        $this->withoutVite();

        $this->get('/lupa-password')
            ->assertOk()
            ->assertSee('Lupa Password')
            ->assertSee('Kirim Kode')
            ->assertSee('Kembali ke Login');
    }

    public function test_link_lupa_password_ada_di_halaman_login(): void
    {
        $this->withoutVite();

        $this->get('/login')->assertSee(route('password.request'), false);
    }

    public function test_email_terdaftar_menerima_kode(): void
    {
        $user = $this->buatUser();

        $code = $this->mintaKode();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertSame(self::EMAIL, session('password_reset_email'));
        $this->assertTrue(Hash::check($code, PasswordReset::where('user_id', $user->user_id)->first()->token));
    }

    public function test_email_huruf_besar_dan_spasi_dinormalkan(): void
    {
        $this->buatUser();
        Mail::fake();

        $this->post('/lupa-password', ['email' => '  2410631170172@STUDENT.unsika.ac.id '])
            ->assertRedirect('/lupa-password/otp');

        Mail::assertSent(PasswordResetMail::class, 1);
    }

    public function test_email_tidak_terdaftar_diperlakukan_sama_tanpa_kirim_email(): void
    {
        Mail::fake();

        $this->post('/lupa-password', ['email' => '9999999999999@student.unsika.ac.id'])
            ->assertRedirect('/lupa-password/otp');

        Mail::assertNothingSent();
        $this->assertSame(0, PasswordReset::count());
    }

    public function test_format_email_salah_ditolak(): void
    {
        $this->from('/lupa-password')->post('/lupa-password', ['email' => 'bukan-email'])
            ->assertSessionHasErrors('email');

        $this->from('/lupa-password')->post('/lupa-password', [])
            ->assertSessionHasErrors('email');
    }

    public function test_permintaan_berulang_dalam_60_detik_tidak_mengirim_email_baru(): void
    {
        $this->buatUser();
        Mail::fake();

        $this->post('/lupa-password', ['email' => self::EMAIL])->assertRedirect('/lupa-password/otp');
        $this->post('/lupa-password', ['email' => self::EMAIL])->assertRedirect('/lupa-password/otp');

        Mail::assertSent(PasswordResetMail::class, 1);
    }

    public function test_halaman_otp_tanpa_session_diarahkan_ke_lupa_password(): void
    {
        $this->get('/lupa-password/otp')->assertRedirect('/lupa-password');
        $this->post('/lupa-password/otp', ['code' => '123456'])->assertRedirect('/lupa-password');
        $this->post('/lupa-password/otp/resend')->assertRedirect('/lupa-password');
    }

    public function test_halaman_otp_menampilkan_email_dan_memakai_route_reset(): void
    {
        $this->buatUser();
        $this->withoutVite();
        $this->mintaKode();

        $this->get('/lupa-password/otp')
            ->assertOk()
            ->assertSee(self::EMAIL)
            ->assertSee(route('password.otp.verify'), false)
            ->assertSee(route('password.otp.resend'), false);
    }

    public function test_kode_benar_membuka_halaman_password_baru(): void
    {
        $this->buatUser();
        $code = $this->mintaKode();

        $this->post('/lupa-password/otp', ['code' => $code])->assertRedirect('/password-baru');

        $this->assertNull(session('password_reset_email'));
        $this->assertNotNull(session('password_reset_verified'));
    }

    public function test_kode_salah_ditolak_dan_tidak_membuka_password_baru(): void
    {
        $this->buatUser();
        $code = $this->mintaKode();
        $salah = $code === '000000' ? '111111' : '000000';

        $this->from('/lupa-password/otp')->post('/lupa-password/otp', ['code' => $salah])
            ->assertRedirect('/lupa-password/otp')
            ->assertSessionHasErrors(['code' => 'Kode OTP salah.']);

        $this->assertNull(session('password_reset_verified'));
        $this->get('/password-baru')->assertRedirect('/lupa-password');
    }

    public function test_terkunci_setelah_5_kali_salah(): void
    {
        $this->buatUser();
        $code = $this->mintaKode();
        $salah = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/lupa-password/otp', ['code' => $salah]);
        }

        $this->post('/lupa-password/otp', ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Terlalu banyak percobaan salah. Kirim ulang kode.']);
    }

    public function test_kode_harus_6_digit_angka(): void
    {
        $this->buatUser();
        $this->mintaKode();

        $this->post('/lupa-password/otp', ['code' => '12ab'])->assertSessionHasErrors('code');
        $this->post('/lupa-password/otp', [])->assertSessionHasErrors('code');
    }

    public function test_kode_kedaluwarsa_ditolak(): void
    {
        $this->buatUser();
        $code = $this->mintaKode();

        $this->travel(6)->minutes();

        $this->post('/lupa-password/otp', ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode.']);
    }

    public function test_email_tidak_terdaftar_selalu_dijawab_kode_salah(): void
    {
        Mail::fake();
        $this->post('/lupa-password', ['email' => '9999999999999@student.unsika.ac.id']);

        $this->post('/lupa-password/otp', ['code' => '123456'])
            ->assertSessionHasErrors(['code' => 'Kode OTP salah.']);
    }

    public function test_kirim_ulang_ditolak_sebelum_60_detik_lalu_boleh_sesudahnya(): void
    {
        $this->buatUser();
        $this->mintaKode();

        $this->post('/lupa-password/otp/resend')->assertSessionHasErrors('code');
        Mail::assertSent(PasswordResetMail::class, 1);

        $this->travel(61)->seconds();

        $this->post('/lupa-password/otp/resend')->assertSessionHas('status', 'Kode baru sudah dikirim.');
        Mail::assertSent(PasswordResetMail::class, 2);
    }

    public function test_kirim_ulang_untuk_email_tidak_terdaftar_tidak_mengirim_email(): void
    {
        Mail::fake();
        $this->post('/lupa-password', ['email' => '9999999999999@student.unsika.ac.id']);

        $this->post('/lupa-password/otp/resend')->assertSessionHas('status', 'Kode baru sudah dikirim.');

        Mail::assertNothingSent();
    }

    public function test_password_baru_tanpa_verifikasi_diarahkan_ke_lupa_password(): void
    {
        $this->get('/password-baru')->assertRedirect('/lupa-password');
        $this->post('/password-baru', [
            'password' => 'password-baru-1',
            'password_confirmation' => 'password-baru-1',
        ])->assertRedirect('/lupa-password');
    }

    public function test_halaman_password_baru_tampil_setelah_kode_benar(): void
    {
        $this->buatUser();
        $this->withoutVite();
        $code = $this->mintaKode();
        $this->post('/lupa-password/otp', ['code' => $code]);

        $this->get('/password-baru')
            ->assertOk()
            ->assertSee('Password Baru')
            ->assertSee('Simpan Password');
    }

    public function test_password_berhasil_diganti_dan_kode_ditandai_terpakai(): void
    {
        $user = $this->buatUser();
        $code = $this->mintaKode();
        $this->post('/lupa-password/otp', ['code' => $code]);

        $this->post('/password-baru', [
            'password' => 'password-baru-1',
            'password_confirmation' => 'password-baru-1',
        ])->assertRedirect('/login')
            ->assertSessionHas('status', 'Password berhasil diubah. Silakan login.');

        $user->refresh();
        $this->assertTrue(Hash::check('password-baru-1', $user->password));
        $this->assertFalse(Hash::check('password-lama', $user->password));
        $this->assertNotNull(PasswordReset::first()->used_at);
        $this->assertNull(session('password_reset_verified'));
    }

    public function test_setelah_ganti_password_user_bisa_login_dengan_password_baru(): void
    {
        $user = $this->buatUser();
        $code = $this->mintaKode();
        $this->post('/lupa-password/otp', ['code' => $code]);
        $this->post('/password-baru', [
            'password' => 'password-baru-1',
            'password_confirmation' => 'password-baru-1',
        ]);

        $this->post('/login', ['email' => self::EMAIL, 'password' => 'password-lama'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => self::EMAIL, 'password' => 'password-baru-1'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_pendek_atau_beda_konfirmasi_ditolak(): void
    {
        $user = $this->buatUser();
        $code = $this->mintaKode();
        $this->post('/lupa-password/otp', ['code' => $code]);

        $this->post('/password-baru', ['password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors(['password' => 'Password minimal 8 karakter.']);

        $this->post('/password-baru', ['password' => 'password-baru-1', 'password_confirmation' => 'beda-banget'])
            ->assertSessionHasErrors(['password' => 'Konfirmasi password tidak sama.']);

        $this->assertTrue(Hash::check('password-lama', $user->refresh()->password));
    }

    public function test_batas_waktu_10_menit_mengisi_password_baru(): void
    {
        $this->buatUser();
        $code = $this->mintaKode();
        $this->post('/lupa-password/otp', ['code' => $code]);

        $this->travel(11)->minutes();

        $this->get('/password-baru')->assertRedirect('/lupa-password');
    }
}