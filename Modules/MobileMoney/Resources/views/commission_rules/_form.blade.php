<form method="POST" action="{{ $formAction }}">
    @csrf
    @if(!empty($formMethod) && strtoupper($formMethod) !== 'POST')
        @method($formMethod)
    @endif
    <div class="box-body">
        <div class="form-group">
            <label>@lang('mobilemoney::lang.operator')</label>
            <select class="form-control" name="operator_id" required>
                <option value="">@lang('mobilemoney::lang.select_operator')</option>
                @foreach($operators as $operatorOption)
                    <option value="{{ $operatorOption->id }}" {{ (string) old('operator_id', $rule->operator_id ?? '') === (string) $operatorOption->id ? 'selected' : '' }}>{{ $operatorOption->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>@lang('mobilemoney::lang.operation_type')</label>
            <select class="form-control" name="transaction_type" required>
                @foreach($transactionTypes as $value => $label)
                    <option value="{{ $value }}" {{ old('transaction_type', $rule->transaction_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>@lang('mobilemoney::lang.min_amount')</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="min_amount" value="{{ old('min_amount', $rule->min_amount ?? 0) }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>@lang('mobilemoney::lang.max_amount')</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="max_amount" value="{{ old('max_amount', $rule->max_amount ?? '') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>@lang('mobilemoney::lang.commission_type')</label>
                    <select class="form-control" name="commission_type" required>
                        @foreach($commissionTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('commission_type', $rule->commission_type ?? 'fixed') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>@lang('mobilemoney::lang.commission_value')</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="commission_value" value="{{ old('commission_value', $rule->commission_value ?? 0) }}" required>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>@lang('mobilemoney::lang.note')</label>
            <textarea class="form-control" name="note" rows="3">{{ old('note', $rule->note ?? '') }}</textarea>
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', isset($rule) ? $rule->is_active : true) ? 'checked' : '' }}>
                @lang('mobilemoney::lang.active')
            </label>
        </div>
    </div>
    <div class="box-footer">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('mobilemoney.commission_rules.index') }}" class="btn btn-default">@lang('messages.go_back')</a>
    </div>
</form>
