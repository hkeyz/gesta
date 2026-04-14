<?php

namespace Modules\MobileMoney\Entities;

use Illuminate\Database\Eloquent\Model;

class MmTransaction extends Model
{
    protected $table = 'mm_transactions';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'commission' => 'float',
        'operation_datetime' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where($query->getModel()->getTable().'.business_id', $businessId);
    }

    public function operator()
    {
        return $this->belongsTo(MmOperator::class, 'operator_id');
    }

    public function rule()
    {
        return $this->belongsTo(MmCommissionRule::class, 'commission_rule_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function canceller()
    {
        return $this->belongsTo(\App\User::class, 'cancelled_by');
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }
}
