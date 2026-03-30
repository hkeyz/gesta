<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class EcomCustomerBusinessContact extends Model
{
    protected $table = 'ecom_customer_business_contacts';

    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(EcomCustomer::class, 'ecom_customer_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }
}
