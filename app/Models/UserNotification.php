<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserNotification extends Model
{
    protected $table = 'notifications';

    protected $primaryKey = 'notification_id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'product_id',
        'negotiation_id',
        'role',
        'notification_type',
        'title',
        'message',
        'offered_price',
        'original_price',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'offered_price' => 'integer',
            'original_price' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(Negotiation::class, 'negotiation_id', 'negotiation_id');
    }

    public function scopeForRole(Builder $query, ?string $role): Builder
    {
        if (! $role) {
            return $query;
        }

        return $query->where('role', $role);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function getEffectiveRoleAttribute(): string
    {
        if (! empty($this->attributes['role'])) {
            return $this->attributes['role'];
        }

        if ($this->product && (int) $this->user_id === (int) $this->product->seller_id) {
            return 'penjual';
        }

        return 'pembeli';
    }

    public function getProductNameAttribute(): string
    {
        return $this->product?->product_name ?? $this->title;
    }

    public function getDisplayOriginalPriceAttribute(): ?int
    {
        if ($this->original_price !== null) {
            return (int) $this->original_price;
        }

        return $this->product ? (int) $this->product->price : null;
    }

    public function getDisplayOfferedPriceAttribute(): ?int
    {
        if ($this->offered_price !== null) {
            return (int) $this->offered_price;
        }

        return $this->negotiation ? (int) $this->negotiation->offered_price : null;
    }

    public function getTargetUrlAttribute(): string
    {
        if (! $this->product_id) {
            return route('home');
        }

        if ($this->effective_role === 'penjual' || ((int) auth()->id() === (int) $this->product?->seller_id)) {
            return route('products.edit', $this->product_id);
        }

        return route('products.show', [
            'product' => $this->product_id,
            'nego' => 'open',
        ]);
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at?->locale('id')->diffForHumans() ?? '';
    }

    public function getProductCoverUrlAttribute(): ?string
    {
        $cover = $this->product?->media?->firstWhere('media_type', 'foto');
        if ($cover) {
            return Storage::disk('public')->url($cover->media_url);
        }

        return null;
    }
}
