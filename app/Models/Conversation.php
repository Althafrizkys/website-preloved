<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $primaryKey = 'conversation_id';

    protected $fillable = [
        'product_id',
        'buyer_id',
        'seller_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(Negotiation::class, 'conversation_id', 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id', 'conversation_id');
    }
    
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id', 'conversation_id')->ofMany('message_id', 'max');
    }

    public function agreedOffer(): ?Negotiation
    {
        return $this->negotiations()
            ->where('status', 'diterima')
            ->orderByDesc('created_at')
            ->orderByDesc('negotiation_id')
            ->first();
    }
}
