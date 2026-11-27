<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_name_gender_and_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::create([
            'name' => 'Nama Lama',
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'cohort' => '2024',
            'password' => 'rahasia123',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Edit Profil')
            ->assertSee($user->email)
            ->assertSee('UNSIKA')
            ->assertSee($user->npm);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
                'gender' => 'Perempuan',
                'email' => 'ubah@example.com',
                'campus' => 'UBP',
                'npm' => '123',
                'profile_photo' => UploadedFile::fake()->createWithContent(
                    'profile.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lE8AAAAASUVORK5CYII=')
                ),
            ])
            ->assertRedirect(route('profile.me'));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('Perempuan', $user->gender);
        $this->assertSame('2410631170172@student.unsika.ac.id', $user->email);
        $this->assertSame('UNSIKA', $user->campus);
        $this->assertSame('2410631170172', $user->npm);
        Storage::disk('public')->assertExists($user->profile_photo);

        $this->get(route('profile.me'))
            ->assertOk()
            ->assertSee('Nama Baru')
            ->assertSee(Storage::disk('public')->url($user->profile_photo));
    }

    public function test_profile_edit_requires_authentication_and_invalid_gender_is_rejected(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));

        $user = User::create([
            'name' => 'Nama Lama',
            'email' => '2410631170172@student.unsika.ac.id',
            'npm' => '2410631170172',
            'campus' => 'UNSIKA',
            'password' => 'rahasia123',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => 'Nama Baru', 'gender' => 'lainnya'])
            ->assertSessionHasErrors('gender');

        $this->assertSame('Nama Lama', $user->fresh()->name);
    }
}
