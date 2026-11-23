<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'seller_id',
        'category_id',
        'product_name',
        'description',
        'price',
        'condition',
        'size',
        'usage_period',
        'defects',
        'stock',
        'is_negotiable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_negotiable' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'product_id';
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'product_id', 'product_id')
            ->orderBy('display_order');
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'product_id', 'product_id');
    }
}
