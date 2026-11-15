<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->isiEnvAdmin('', '');

        parent::tearDown();
    }

    private function isiEnvAdmin(string $email, string $password): void
    {
        foreach (['ADMIN_EMAIL' => $email, 'ADMIN_PASSWORD' => $password] as $kunci => $nilai) {
            putenv("{$kunci}={$nilai}");
            $_ENV[$kunci] = $nilai;
            $_SERVER[$kunci] = $nilai;
        }
    }

    private function buatUser(array $tambahan = []): User
    {
        return User::create(array_merge([
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => 2024,
            'password' => 'rahasia123',
        ], $tambahan));
    }

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

    public function test_akun_baru_berperan_user_secara_default(): void
    {
        $user = $this->buatUser()->refresh();

        $this->assertSame('user', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_role_tidak_bisa_diisi_lewat_mass_assignment(): void
    {
        $user = $this->buatUser(['role' => 'admin'])->refresh();

        $this->assertSame('user', $user->role);
    }

    public function test_admin_boleh_tanpa_kampus_dan_angkatan(): void
    {
        $admin = $this->buatAdmin()->refresh();

        $this->assertTrue($admin->isAdmin());
        $this->assertNull($admin->campus);
        $this->assertNull($admin->cohort);
    }

    public function test_middleware_admin_menolak_tamu_dan_user_biasa_dan_menerima_admin(): void
    {
        Route::middleware(['web', 'auth', 'admin'])->get('/_uji-admin', fn () => 'ok');

        $this->get('/_uji-admin')->assertRedirect(route('login'));

        $this->actingAs($this->buatUser())->get('/_uji-admin')->assertForbidden();

        $this->actingAs($this->buatAdmin())->get('/_uji-admin')->assertOk()->assertSee('ok');
    }

    public function test_seeder_membuat_admin_dari_env(): void
    {
        $this->isiEnvAdmin('  Admin@Seklov.TEST ', 'rahasia-admin');

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@seklov.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue((bool) $admin->is_verified);
        $this->assertTrue(Hash::check('rahasia-admin', $admin->password));
    }

    public function test_seeder_bisa_diulang_tanpa_membuat_admin_ganda(): void
    {
        $this->isiEnvAdmin('admin@seklov.test', 'rahasia-admin');
        $this->seed(AdminSeeder::class);

        $this->isiEnvAdmin('admin@seklov.test', 'password-baru-1');
        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@seklov.test')->count());
        $this->assertTrue(Hash::check('password-baru-1', User::first()->password));
    }

    public function test_seeder_dilewati_jika_env_admin_kosong(): void
    {
        $this->isiEnvAdmin('', '');

        $this->seed(AdminSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_database_seeder_menjalankan_seeder_admin(): void
    {
        $this->isiEnvAdmin('admin@seklov.test', 'rahasia-admin');

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('email', 'admin@seklov.test')->first()->isAdmin());
    }
}