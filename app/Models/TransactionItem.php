<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $table = 'transaction_items';

    protected $primaryKey = 'transaction_item_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'wishlist_request_id',
        'item_name_snapshot',
        'negotiation_id',
        'quantity',
        'agreed_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'agreed_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function wishlistRequest(): BelongsTo
    {
        return $this->belongsTo(WishlistRequest::class, 'wishlist_request_id', 'request_id');
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(Negotiation::class, 'negotiation_id', 'negotiation_id');
    }
}
