<?php

namespace Modules\MobileMoney\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\MobileMoney\Entities\MmOperator;
use Modules\MobileMoney\Entities\MmSetting;
use Modules\MobileMoney\Entities\MmTransaction;

class TransactionController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.transactions', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $perPageOptions = [25, 50, 100, 200, 500, 1000, -1];
        $defaultPerPage = (int) data_get(session('business.common_settings', []), 'default_datatable_page_entries', 25);
        if (! in_array($defaultPerPage, $perPageOptions, true)) {
            $defaultPerPage = 25;
        }

        $perPage = (int) $request->input('per_page', $defaultPerPage);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = $defaultPerPage;
        }

        $filters = $request->only(['operator_id', 'type', 'status', 'customer_phone', 'start_date', 'end_date']);

        $query = MmTransaction::forBusiness($this->businessId())
            ->with(['operator', 'creator']);

        $this->mobileMoneyService->applyTransactionFilters($query, $filters);

        $paginationSize = $perPage === -1 ? max((clone $query)->count(), 1) : $perPage;

        $transactions = $query->orderByDesc('operation_datetime')
            ->orderByDesc('id')
            ->paginate($paginationSize)
            ->withQueryString();

        $operators = MmOperator::forBusiness($this->businessId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $transactionTypes = $this->mobileMoneyService->transactionTypes();
        $statuses = $this->mobileMoneyService->statuses();

        return view('mobilemoney::transactions.index', compact('transactions', 'operators', 'transactionTypes', 'statuses', 'filters', 'perPage', 'perPageOptions'));
    }

    public function create(?string $type = null)
    {
        $this->authorizeMobileMoney(['mobile_money.transactions', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $transactionTypes = $this->mobileMoneyService->transactionTypes();
        $type = $type ?: 'deposit';

        if (! array_key_exists($type, $transactionTypes)) {
            abort(404);
        }

        $operators = MmOperator::forBusiness($this->businessId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $settings = MmSetting::where('business_id', $this->businessId())->firstOrFail();

        return view('mobilemoney::transactions.create', compact('type', 'transactionTypes', 'operators', 'settings'));
    }

    public function store(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.transactions', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $settings = MmSetting::where('business_id', $this->businessId())->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys($this->mobileMoneyService->transactionTypes()))],
            'operator_id' => [
                'required',
                Rule::exists('mm_operators', 'id')->where(fn ($query) => $query->where('business_id', $this->businessId())->where('is_active', 1)),
            ],
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'commission' => 'nullable|numeric|min:0',
            'external_reference' => 'nullable|string|max:255',
            'operation_datetime' => 'nullable|date_format:Y-m-d\\TH:i',
            'note' => 'nullable|string|max:1000',
        ]);

        $ruleData = $this->mobileMoneyService->calculateCommission(
            $this->businessId(),
            (int) $validated['operator_id'],
            $validated['type'],
            (float) $validated['amount']
        );

        $manualCommission = $request->filled('commission') && $settings->allow_manual_commission;

        $transaction = MmTransaction::create([
            'business_id' => $this->businessId(),
            'operator_id' => $validated['operator_id'],
            'commission_rule_id' => $ruleData['rule']?->id,
            'entry_no' => $this->mobileMoneyService->generateEntryNo($this->businessId()),
            'type' => $validated['type'],
            'status' => 'completed',
            'customer_name' => ! empty($validated['customer_name']) ? $validated['customer_name'] : null,
            'customer_phone' => ! empty($validated['customer_phone']) ? $validated['customer_phone'] : '',
            'amount' => $validated['amount'],
            'commission' => $manualCommission ? (float) $validated['commission'] : (float) $ruleData['commission'],
            'external_reference' => $validated['external_reference'] ?? null,
            'operation_datetime' => $this->mobileMoneyService->parseOperationDateTime($validated['operation_datetime'] ?? null),
            'note' => $validated['note'] ?? null,
            'created_by' => session()->get('user.id'),
        ]);

        return redirect()->route('mobilemoney.transactions.show', $transaction)->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.transaction_saved'),
        ]);
    }

    public function previewCommission(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.transactions', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys($this->mobileMoneyService->transactionTypes()))],
            'operator_id' => [
                'nullable',
                Rule::exists('mm_operators', 'id')->where(fn ($query) => $query->where('business_id', $this->businessId())->where('is_active', 1)),
            ],
            'amount' => 'nullable|numeric|min:0',
        ]);

        $ruleData = $this->mobileMoneyService->calculateCommission(
            $this->businessId(),
            ! empty($validated['operator_id']) ? (int) $validated['operator_id'] : null,
            $validated['type'],
            ! empty($validated['amount']) ? (float) $validated['amount'] : 0
        );

        return response()->json([
            'success' => true,
            'commission' => number_format((float) $ruleData['commission'], 2, '.', ''),
            'has_rule' => ! empty($ruleData['rule']),
            'message' => ! empty($ruleData['rule'])
                ? __('mobilemoney::lang.commission_preview_rule_applied')
                : __('mobilemoney::lang.commission_preview_no_rule'),
        ]);
    }

    public function show(MmTransaction $transaction)
    {
        $this->authorizeMobileMoney(['mobile_money.transactions', 'mobile_money.access']);
        $this->ensureOwnedTransaction($transaction);

        $transaction->load(['operator', 'creator', 'canceller', 'rule']);
        $transactionTypes = $this->mobileMoneyService->transactionTypes();

        return view('mobilemoney::transactions.show', compact('transaction', 'transactionTypes'));
    }

    public function cancel(Request $request, MmTransaction $transaction)
    {
        $this->authorizeMobileMoney(['mobile_money.cancel', 'mobile_money.transactions']);
        $this->ensureOwnedTransaction($transaction);

        if ($transaction->status === 'cancelled') {
            return redirect()->route('mobilemoney.transactions.show', $transaction)->with('status', [
                'success' => 0,
                'msg' => __('mobilemoney::lang.transaction_already_cancelled'),
            ]);
        }

        $validated = $request->validate([
            'cancellation_note' => 'required|string|max:1000',
        ]);

        $transaction->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => session()->get('user.id'),
            'cancellation_note' => $validated['cancellation_note'],
        ]);

        return redirect()->route('mobilemoney.transactions.show', $transaction)->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.transaction_cancelled'),
        ]);
    }

    protected function ensureOwnedTransaction(MmTransaction $transaction): void
    {
        if ($transaction->business_id !== $this->businessId()) {
            abort(404);
        }
    }
}
