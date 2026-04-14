@extends('layouts.app')
@section('title', __('mobilemoney::lang.commission_rules'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.commission_rules')</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.add_rule')</h3>
                </div>
                <form method="POST" action="{{ route('mobilemoney.commission_rules.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.operator')</label>
                            <select class="form-control" name="operator_id" required>
                                <option value="">@lang('mobilemoney::lang.select_operator')</option>
                                @foreach($operators as $operator)
                                    <option value="{{ $operator->id }}" {{ (string) old('operator_id') === (string) $operator->id ? 'selected' : '' }}>{{ $operator->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.operation_type')</label>
                            <select class="form-control" name="transaction_type" required>
                                @foreach($transactionTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('transaction_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.min_amount')</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="min_amount" value="{{ old('min_amount', 0) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.max_amount')</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="max_amount" value="{{ old('max_amount') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.commission_type')</label>
                                    <select class="form-control" name="commission_type" required>
                                        @foreach($commissionTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('commission_type', 'fixed') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.commission_value')</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="commission_value" value="{{ old('commission_value', 0) }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.note')</label>
                            <textarea class="form-control" name="note" rows="3">{{ old('note') }}</textarea>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                @lang('mobilemoney::lang.active')
                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">@lang('mobilemoney::lang.add_rule')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.commission_rules')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('mobilemoney::lang.operator')</th>
                                <th>@lang('mobilemoney::lang.operation_type')</th>
                                <th>@lang('mobilemoney::lang.range')</th>
                                <th>@lang('mobilemoney::lang.commission')</th>
                                <th>@lang('mobilemoney::lang.status')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rules as $rule)
                                <tr>
                                    <td colspan="6">
                                        <form id="rule-form-{{ $rule->id }}" method="POST" action="{{ route('mobilemoney.commission_rules.update', $rule) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <select class="form-control" name="operator_id" form="rule-form-{{ $rule->id }}" required>
                                                    @foreach($operators as $operator)
                                                        <option value="{{ $operator->id }}" {{ (int) $rule->operator_id === (int) $operator->id ? 'selected' : '' }}>{{ $operator->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select class="form-control" name="transaction_type" form="rule-form-{{ $rule->id }}" required>
                                                    @foreach($transactionTypes as $value => $label)
                                                        <option value="{{ $value }}" {{ $rule->transaction_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" min="0" class="form-control" name="min_amount" value="{{ $rule->min_amount }}" form="rule-form-{{ $rule->id }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" min="0" class="form-control" name="max_amount" value="{{ $rule->max_amount }}" form="rule-form-{{ $rule->id }}">
                                            </div>
                                            <div class="col-md-2">
                                                <select class="form-control" name="commission_type" form="rule-form-{{ $rule->id }}" required>
                                                    @foreach($commissionTypes as $value => $label)
                                                        <option value="{{ $value }}" {{ $rule->commission_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="number" step="0.01" min="0" class="form-control" name="commission_value" value="{{ $rule->commission_value }}" form="rule-form-{{ $rule->id }}" style="margin-top: 6px;" required>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="checkbox" style="margin-top: 0;">
                                                    <label>
                                                        <input type="checkbox" name="is_active" value="1" form="rule-form-{{ $rule->id }}" {{ $rule->is_active ? 'checked' : '' }}>
                                                        @lang('mobilemoney::lang.active')
                                                    </label>
                                                </div>
                                                <button type="submit" class="btn btn-xs btn-primary" form="rule-form-{{ $rule->id }}">@lang('messages.update')</button>
                                            </div>
                                            <div class="col-md-10" style="margin-top: 8px;">
                                                <input type="text" class="form-control" name="note" value="{{ $rule->note }}" form="rule-form-{{ $rule->id }}" placeholder="@lang('mobilemoney::lang.note')">
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('mobilemoney.commission_rules.destroy', $rule) }}" style="margin-top: 6px;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('@lang('mobilemoney::lang.confirm_delete')')">@lang('messages.delete')</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
