<?php

namespace Modules\MobileMoney\Entities;

use Illuminate\Database\Eloquent\Model;

class MmCommissionRule extends Model
{
    protected $table = 'mm_commission_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'min_amount' => 'float',
        'max_amount' => 'float',
        'commission_value' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where($query->getModel()->getTable().'.business_id', $businessId);
    }

    public function operator()
    {
        return $this->belongsTo(MmOperator::class, 'operator_id');
    }
}
