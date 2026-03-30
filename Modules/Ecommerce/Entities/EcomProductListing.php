<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class EcomProductListing extends Model
{
    protected $table = 'ecom_product_listings';

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(EcomStore::class, 'store_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }
}
