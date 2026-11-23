<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'ratings';
    protected $primaryKey = 'rating_id';

    // Tabel ratings hanya memiliki kolom created_at (tanpa updated_at)
    const UPDATED_AT = null;

    protected $fillable = [
        'transaction_id',
        'reviewer_id',
        'reviewed_user_id',
        'stars',
        'review',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'user_id');
    }

    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_user_id', 'user_id');
    }
}