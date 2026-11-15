<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    private const DOMAIN_UNSIKA = 'student.unsika.ac.id';

    private const DOMAIN_UBP = 'mhs.ubpkarawang.ac.id';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $npm = trim((string) $this->input('npm'));

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'npm' => $npm === '' ? null : $npm,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'npm' => ['nullable', 'regex:/^[0-9]+$/', 'max:30', 'unique:users,npm'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email kampus wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'npm.regex' => 'NPM/NIM hanya boleh berisi angka.',
            'npm.unique' => 'NPM/NIM sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('email')) {
                    return;
                }

                $info = $this->emailInfo();

                if ($info === null) {
                    $validator->errors()->add(
                        'email',
                        'Pendaftaran hanya diperbolehkan menggunakan email kampus UNSIKA/UBP.'
                    );

                    return;
                }

                // NPM wajib dan harus sama dengan angka di email hanya untuk UNSIKA.
                // Untuk UBP, NIM opsional (tidak bisa dicocokkan dengan email).
                if ($info['campus'] === 'UNSIKA') {
                    $npm = $this->input('npm');

                    if ($npm === null) {
                        $validator->errors()->add('npm', 'NPM wajib diisi untuk mahasiswa UNSIKA.');
                    } elseif ($npm !== $info['local']) {
                        $validator->errors()->add('npm', 'NPM harus sama dengan angka pada email kampus.');
                    }
                }
            },
        ];
    }

    public function campus(): string
    {
        return $this->emailInfo()['campus'];
    }

    public function cohort(): string
    {
        return $this->emailInfo()['cohort'];
    }

    /**
     * Membaca email kampus. Domain harus sama persis (bukan sekadar mengandung).
     *
     * @return array{campus: string, cohort: string, local: string}|null
     */
    private function emailInfo(): ?array
    {
        $email = (string) $this->input('email');

        if (substr_count($email, '@') !== 1) {
            return null;
        }

        [$local, $domain] = explode('@', $email);

        // UNSIKA: NPM@student.unsika.ac.id, angkatan dari 2 digit awal NPM.
        if ($domain === self::DOMAIN_UNSIKA && preg_match('/^(\d{2})\d+$/', $local, $m)) {
            return ['campus' => 'UNSIKA', 'cohort' => '20'.$m[1], 'local' => $local];
        }

        // UBP: kodeprodi+angkatan.nama@mhs.ubpkarawang.ac.id, contoh ak24.nama.
        if ($domain === self::DOMAIN_UBP && preg_match('/^[a-z]+(\d{2})\.[a-z0-9._-]+$/', $local, $m)) {
            return ['campus' => 'UBP', 'cohort' => '20'.$m[1], 'local' => $local];
        }

        return null;
    }
}