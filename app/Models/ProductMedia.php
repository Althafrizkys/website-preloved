<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    protected $table = 'product_media';
    protected $primaryKey = 'media_id';
    public $timestamps = false;   // tabel tidak punya created_at/updated_at

    protected $fillable = [
        'product_id', 'media_type', 'media_url', 'file_size', 'display_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}