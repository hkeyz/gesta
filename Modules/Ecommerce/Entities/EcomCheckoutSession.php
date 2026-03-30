<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class EcomCheckoutSession extends Model
{
    protected $table = 'ecom_checkout_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'cart_snapshot' => 'array',
        'checkout_context' => 'array',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(EcomStore::class, 'store_id');
    }

    public function customer()
    {
        return $this->belongsTo(EcomCustomer::class, 'ecom_customer_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }
}
