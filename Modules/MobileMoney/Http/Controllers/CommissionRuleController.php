<?php

namespace Modules\MobileMoney\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\MobileMoney\Entities\MmCommissionRule;
use Modules\MobileMoney\Entities\MmOperator;
use Modules\MobileMoney\Entities\MmTransaction;

class CommissionRuleController extends BaseController
{
    public function index()
    {
        $this->authorizeMobileMoney(['mobile_money.commissions', 'mobile_money.settings', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $operators = MmOperator::forBusiness($this->businessId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rules = MmCommissionRule::forBusiness($this->businessId())
            ->with('operator')
            ->orderBy('operator_id')
            ->orderBy('transaction_type')
            ->orderBy('min_amount')
            ->get();

        $transactionTypes = $this->mobileMoneyService->transactionTypes();
        $commissionTypes = $this->mobileMoneyService->commissionTypes();

        return view('mobilemoney::commission_rules.index', compact('operators', 'rules', 'transactionTypes', 'commissionTypes'));
    }

    public function store(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.commissions', 'mobile_money.settings']);
        $this->ensureBusinessSetup();

        $validated = $this->validateRule($request);

        MmCommissionRule::create([
            'business_id' => $this->businessId(),
            'operator_id' => $validated['operator_id'],
            'transaction_type' => $validated['transaction_type'],
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'] ?? null,
            'commission_type' => $validated['commission_type'],
            'commission_value' => $validated['commission_value'],
            'is_active' => $request->boolean('is_active', true),
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('mobilemoney.commission_rules.index')->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.rule_saved'),
        ]);
    }

    public function update(Request $request, MmCommissionRule $rule)
    {
        $this->authorizeMobileMoney(['mobile_money.commissions', 'mobile_money.settings']);
        $this->ensureOwnedRule($rule);

        $validated = $this->validateRule($request);

        $rule->update([
            'operator_id' => $validated['operator_id'],
            'transaction_type' => $validated['transaction_type'],
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'] ?? null,
            'commission_type' => $validated['commission_type'],
            'commission_value' => $validated['commission_value'],
            'is_active' => $request->boolean('is_active', false),
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('mobilemoney.commission_rules.index')->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.rule_updated'),
        ]);
    }

    public function destroy(MmCommissionRule $rule)
    {
        $this->authorizeMobileMoney(['mobile_money.commissions', 'mobile_money.settings']);
        $this->ensureOwnedRule($rule);

        if (MmTransaction::forBusiness($this->businessId())->where('commission_rule_id', $rule->id)->exists()) {
            return redirect()->route('mobilemoney.commission_rules.index')->with('status', [
                'success' => 0,
                'msg' => __('mobilemoney::lang.rule_in_use'),
            ]);
        }

        $rule->delete();

        return redirect()->route('mobilemoney.commission_rules.index')->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.rule_deleted'),
        ]);
    }

    protected function validateRule(Request $request): array
    {
        return $request->validate([
            'operator_id' => [
                'required',
                Rule::exists('mm_operators', 'id')->where(fn ($query) => $query->where('business_id', $this->businessId())),
            ],
            'transaction_type' => ['required', Rule::in(array_keys($this->mobileMoneyService->transactionTypes()))],
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gte:min_amount',
            'commission_type' => ['required', Rule::in(array_keys($this->mobileMoneyService->commissionTypes()))],
            'commission_value' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);
    }

    protected function ensureOwnedRule(MmCommissionRule $rule): void
    {
        if ($rule->business_id !== $this->businessId()) {
            abort(404);
        }
    }
}
