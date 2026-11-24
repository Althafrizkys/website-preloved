<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'seller_id', 'category_id', 'product_name', 'description',
        'price', 'condition', 'size', 'usage_period', 'defects', 'status',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class, 'product_id', 'product_id')
                    ->orderBy('display_order');
    }

    // $product->formatted_price => "Rp 150.000"
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn () => 'Rp ' . number_format((float) $this->price, 0, ',', '.'));
    }

    // $product->thumbnail_url
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function () {
            $foto = $this->media->firstWhere('media_type', 'foto');
            return $foto
                ? asset('storage/' . $foto->media_url)
                : asset('images/no-image.png');
        });
    }
}