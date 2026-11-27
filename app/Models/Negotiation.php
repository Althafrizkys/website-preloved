<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Negotiation extends Model
{
    protected $table = 'negotiations';

    protected $primaryKey = 'negotiation_id';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'product_id',
        'offered_item_name',
        'sender_id',
        'parent_offer_id',
        'offered_price',
        'quantity',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'offered_price' => 'decimal:2',
            'quantity' => 'integer',
            'created_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function parentOffer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_offer_id', 'negotiation_id');
    }

    public function childOffers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_offer_id', 'negotiation_id');
    }

    public function transactionItem(): HasOne
    {
        return $this->hasOne(TransactionItem::class, 'negotiation_id', 'negotiation_id');
    }
}
