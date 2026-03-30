<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class EcomStore extends Model
{
    protected $table = 'ecom_stores';

    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function productListings()
    {
        return $this->hasMany(EcomProductListing::class, 'store_id');
    }

    public function apiSetting()
    {
        return $this->hasOne(EcomApiSetting::class, 'store_id');
    }

    public function checkoutSessions()
    {
        return $this->hasMany(EcomCheckoutSession::class, 'store_id');
    }

    public function getPublicUrlAttribute()
    {
        return url('/shop/'.$this->slug);
    }
}
