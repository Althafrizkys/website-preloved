<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $override);
    }

    public function test_unsika_valid_membuat_user(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/');

        $user = User::where('email', '2410631170172@student.unsika.ac.id')->first();
        $this->assertSame('UNSIKA', $user->campus);
        $this->assertSame('2024', $user->cohort);
        $this->assertSame('2410631170172', $user->npm);
        $this->assertNull($user->name);
        $this->assertFalse((bool) $user->is_verified);
        $this->assertNotSame('rahasia123', $user->password);
    }

    public function test_ubp_valid_tanpa_nim(): void
    {
        $this->post('/register', $this->payload([
            'email' => 'ak24.reysadwiputra@mhs.ubpkarawang.ac.id',
            'npm' => '',
        ]))->assertRedirect('/');

        $user = User::where('email', 'ak24.reysadwiputra@mhs.ubpkarawang.ac.id')->first();
        $this->assertSame('UBP', $user->campus);
        $this->assertSame('2024', $user->cohort);
        $this->assertNull($user->npm);
    }

    public function test_dua_user_ubp_tanpa_nim_boleh(): void
    {
        foreach (['ak24.satu', 'ak24.dua'] as $nama) {
            $this->post('/register', $this->payload([
                'email' => $nama.'@mhs.ubpkarawang.ac.id',
                'npm' => null,
            ]))->assertRedirect('/');
        }

        $this->assertSame(2, User::count());
    }

    public function test_email_huruf_besar_dinormalisasi(): void
    {
        $this->post('/register', $this->payload([
            'email' => ' 2410631170172@Student.Unsika.ac.id ',
        ]))->assertRedirect('/');

        $this->assertDatabaseHas('users', ['email' => '2410631170172@student.unsika.ac.id']);
    }

    public function test_domain_selain_kampus_ditolak(): void
    {
        $this->post('/register', $this->payload(['email' => 'budi@gmail.com']))
            ->assertSessionHasErrors('email');
        $this->assertSame(0, User::count());
    }

    public function test_domain_menyerupai_ditolak(): void
    {
        foreach ([
            'ak24.x@mhs.ubpkarawang.ac.id.evil.com',
            'ak24.x@evilmhs.ubpkarawang.ac.id',
            '2410631170172@student.unsika.ac.id.evil.com',
            '2410631170172@unsika.ac.id',
        ] as $email) {
            $this->post('/register', $this->payload(['email' => $email]))
                ->assertSessionHasErrors('email');
        }
        $this->assertSame(0, User::count());
    }

    public function test_npm_unsika_harus_sama_dengan_email(): void
    {
        $this->post('/register', $this->payload(['npm' => '2410631170999']))
            ->assertSessionHasErrors('npm');
    }

    public function test_npm_unsika_wajib(): void
    {
        $this->post('/register', $this->payload(['npm' => '']))
            ->assertSessionHasErrors('npm');
    }

    public function test_password_pendek_atau_beda_konfirmasi_ditolak(): void
    {
        $this->post('/register', $this->payload(['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['password_confirmation' => 'beda12345']))
            ->assertSessionHasErrors('password');
    }

    public function test_email_duplikat_ditolak(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/');
        $this->post('/register', $this->payload())->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }
}