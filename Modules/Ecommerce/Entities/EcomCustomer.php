<?php

namespace Modules\Ecommerce\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class EcomCustomer extends Authenticatable
{
    use Notifiable;

    protected $table = 'ecom_customers';

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function businessContacts()
    {
        return $this->hasMany(EcomCustomerBusinessContact::class, 'ecom_customer_id');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
