<?php

namespace Modules\MobileMoney\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\MobileMoney\Entities\MmOperator;

class OperatorController extends BaseController
{
    public function index()
    {
        $this->authorizeMobileMoney(['mobile_money.operators', 'mobile_money.settings', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $operators = MmOperator::forBusiness($this->businessId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('mobilemoney::operators.index', compact('operators'));
    }

    public function create()
    {
        $this->authorizeMobileMoney(['mobile_money.operators', 'mobile_money.settings', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        return view('mobilemoney::operators.create');
    }

    public function store(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.operators', 'mobile_money.settings']);
        $this->ensureBusinessSetup();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mm_operators')->where(fn ($query) => $query->where('business_id', $this->businessId())),
            ],
            'code' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        MmOperator::create([
            'business_id' => $this->businessId(),
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'color' => $validated['color'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('mobilemoney.operators.index')->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.operator_saved'),
        ]);
    }

    public function edit(MmOperator $operator)
    {
        $this->authorizeMobileMoney(['mobile_money.operators', 'mobile_money.settings', 'mobile_money.access']);
        $this->ensureOwnedOperator($operator);

        return view('mobilemoney::operators.edit', compact('operator'));
    }

    public function update(Request $request, MmOperator $operator)
    {
        $this->authorizeMobileMoney(['mobile_money.operators', 'mobile_money.settings']);
        $this->ensureOwnedOperator($operator);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mm_operators')
                    ->ignore($operator->id)
                    ->where(fn ($query) => $query->where('business_id', $this->businessId())),
            ],
            'code' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $operator->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'color' => $validated['color'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('mobilemoney.operators.index')->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.operator_updated'),
        ]);
    }

    protected function ensureOwnedOperator(MmOperator $operator): void
    {
        if ($operator->business_id !== $this->businessId()) {
            abort(404);
        }
    }
}
