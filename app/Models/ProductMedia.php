<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $primaryKey = 'media_id';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'media_type',
        'media_url',
        'file_size',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
