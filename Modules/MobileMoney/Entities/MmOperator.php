<?php

namespace Modules\MobileMoney\Entities;

use Illuminate\Database\Eloquent\Model;

class MmOperator extends Model
{
    protected $table = 'mm_operators';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where($query->getModel()->getTable().'.business_id', $businessId);
    }

    public function rules()
    {
        return $this->hasMany(MmCommissionRule::class, 'operator_id');
    }
}
