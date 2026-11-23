<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRecord extends Model
{
    protected $table = 'reward_records';
    protected $primaryKey = 'reward_id';

    // Karena di tabel hanya ada kolom created_at (tidak ada updated_at)
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'points',
        'reward_type',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}