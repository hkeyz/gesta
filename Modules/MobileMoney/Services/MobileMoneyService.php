<?php

namespace Modules\MobileMoney\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\MobileMoney\Entities\MmCommissionRule;
use Modules\MobileMoney\Entities\MmOperator;
use Modules\MobileMoney\Entities\MmSetting;
use Modules\MobileMoney\Entities\MmTransaction;

class MobileMoneyService
{
    public function ensureBusinessSetup(?int $businessId): void
    {
        if (empty($businessId)) {
            return;
        }

        MmSetting::firstOrCreate(
            ['business_id' => $businessId],
            [
                'terminal_label' => __('mobilemoney::lang.default_terminal_label'),
                'terminal_number' => null,
                'receipt_footer' => __('mobilemoney::lang.default_receipt_footer'),
                'auto_assign_reference' => true,
                'allow_manual_commission' => true,
            ]
        );

        if (! MmOperator::forBusiness($businessId)->exists()) {
            foreach ($this->defaultOperators() as $index => $operator) {
                MmOperator::create([
                    'business_id' => $businessId,
                    'name' => $operator['name'],
                    'code' => $operator['code'],
                    'color' => $operator['color'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    public function defaultOperators(): array
    {
        return [
            ['name' => 'Orange Money', 'code' => 'orange_money', 'color' => '#ff7a00'],
            ['name' => 'Moov Money', 'code' => 'moov_money', 'color' => '#0057b8'],
            ['name' => 'Wave', 'code' => 'wave', 'color' => '#19c2ff'],
            ['name' => 'MTN MoMo', 'code' => 'mtn_momo', 'color' => '#ffcc00'],
        ];
    }

    public function transactionTypes(): array
    {
        return [
            'deposit' => __('mobilemoney::lang.deposit'),
            'withdrawal' => __('mobilemoney::lang.withdrawal'),
        ];
    }

    public function statuses(): array
    {
        return [
            'completed' => __('mobilemoney::lang.completed'),
            'cancelled' => __('mobilemoney::lang.cancelled'),
        ];
    }

    public function commissionTypes(): array
    {
        return [
            'fixed' => __('mobilemoney::lang.fixed_amount'),
            'percentage' => __('mobilemoney::lang.percentage'),
        ];
    }

    public function calculateCommission(int $businessId, ?int $operatorId, string $type, float $amount): array
    {
        if (empty($operatorId) || $amount <= 0) {
            return ['commission' => 0.0, 'rule' => null];
        }

        $rule = MmCommissionRule::forBusiness($businessId)
            ->where('operator_id', $operatorId)
            ->where('transaction_type', $type)
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->orderByDesc('id')
            ->first();

        if (empty($rule)) {
            return ['commission' => 0.0, 'rule' => null];
        }

        $commission = $rule->commission_type === 'percentage'
            ? round($amount * ($rule->commission_value / 100), 2)
            : round($rule->commission_value, 2);

        return ['commission' => $commission, 'rule' => $rule];
    }

    public function parseOperationDateTime(?string $value): Carbon
    {
        if (empty($value)) {
            return now();
        }

        return Carbon::createFromFormat('Y-m-d\TH:i', $value);
    }

    public function generateEntryNo(int $businessId): string
    {
        $sequence = MmTransaction::forBusiness($businessId)->count() + 1;

        return 'MM-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function applyTransactionFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['operator_id'])) {
            $query->where('operator_id', $filters['operator_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_phone'])) {
            $query->where('customer_phone', 'like', '%'.$filters['customer_phone'].'%');
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('operation_datetime', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('operation_datetime', '<=', $filters['end_date']);
        }

        return $query;
    }
}
