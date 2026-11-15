<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    // Tabel hanya punya created_at (tanpa updated_at).
    public const UPDATED_AT = null;

    protected $primaryKey = 'verification_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}