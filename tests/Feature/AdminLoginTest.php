<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function buatAdmin(): User
    {
        $admin = User::firstOrNew(['email' => 'admin@seklov.test']);
        $admin->forceFill([
            'name' => 'Admin',
            'role' => 'admin',
            'password' => 'rahasia-admin',
            'is_verified' => true,
        ])->save();

        return $admin;
    }

    private function buatUser(): User
    {
        $user = User::create([
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => 2024,
            'password' => 'rahasia123',
        ]);

        $user->forceFill(['is_verified' => true])->save();

        return $user;
    }

    public function test_halaman_login_admin_tampil(): void
    {
        $this->withoutVite();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Login Admin')
            ->assertDontSee('Belum punya akun?')
            ->assertDontSee('Lupa Password?');
    }

    public function test_admin_bisa_login_dan_masuk_ke_dashboard_admin(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/admin/login', ['email' => 'admin@seklov.test', 'password' => 'rahasia-admin'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Dashboard admin');
    }

    public function test_email_admin_huruf_besar_dan_spasi_dinormalkan(): void
    {
        $this->buatAdmin();

        $this->post('/admin/login', ['email' => '  Admin@SEKLOV.test ', 'password' => 'rahasia-admin'])
            ->assertRedirect('/admin');
    }

    public function test_password_salah_atau_email_tidak_dikenal_ditolak_dengan_pesan_sama(): void
    {
        $this->buatAdmin();

        $this->from('/admin/login')->post('/admin/login', ['email' => 'admin@seklov.test', 'password' => 'salah-banget'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->post('/admin/login', ['email' => 'tidak-ada@seklov.test', 'password' => 'apa-saja'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_input_kosong_ditolak(): void
    {
        $this->post('/admin/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_akun_user_biasa_tidak_bisa_login_lewat_login_admin(): void
    {
        $this->buatUser();

        $this->post('/admin/login', ['email' => '2410631170172@student.unsika.ac.id', 'password' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_akun_admin_tidak_bisa_login_lewat_login_biasa(): void
    {
        $this->buatAdmin();

        $this->post('/login', ['email' => 'admin@seklov.test', 'password' => 'rahasia-admin'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_tamu_yang_membuka_halaman_admin_diarahkan_ke_login_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_setelah_login_admin_kembali_ke_halaman_admin_yang_dituju(): void
    {
        $this->buatAdmin();

        $this->get('/admin');

        $this->post('/admin/login', ['email' => 'admin@seklov.test', 'password' => 'rahasia-admin'])
            ->assertRedirect('/admin');
    }

    public function test_user_biasa_yang_login_ditolak_di_halaman_admin(): void
    {
        $this->actingAs($this->buatUser())->get('/admin')->assertForbidden();
    }

    public function test_user_yang_sudah_login_dialihkan_dari_halaman_tamu(): void
    {
        $this->actingAs($this->buatUser());

        foreach (['/login', '/register', '/lupa-password', '/admin/login'] as $url) {
            $this->get($url)->assertRedirect('/');
        }
    }

    public function test_admin_yang_sudah_login_dialihkan_ke_dashboard_admin_dari_halaman_tamu(): void
    {
        $this->actingAs($this->buatAdmin());

        $this->get('/login')->assertRedirect('/admin');
        $this->get('/admin/login')->assertRedirect('/admin');
    }
}