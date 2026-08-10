<?php

namespace Modules\MobileMoney\Entities;

use Illuminate\Database\Eloquent\Model;

class MmSetting extends Model
{
    protected $table = 'mm_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'auto_assign_reference' => 'boolean',
        'allow_manual_commission' => 'boolean',
    ];
}
