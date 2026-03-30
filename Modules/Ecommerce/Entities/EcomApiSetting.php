<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class EcomApiSetting extends Model
{
    protected $table = 'ecom_api_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function store()
    {
        return $this->belongsTo(EcomStore::class, 'store_id');
    }
}
