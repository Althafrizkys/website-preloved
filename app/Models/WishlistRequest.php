<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistRequest extends Model
{
    protected $table = 'wishlist_requests';

    protected $primaryKey = 'request_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'category_id',
        'item_name',
        'condition',
        'budget',
        'description',
        'image',
        'expired_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'expired_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'request_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
}