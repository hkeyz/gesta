@extends('layouts.app')
@section('title', $transactionTypes[$type] ?? __('mobilemoney::lang.new_operation'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ $transactionTypes[$type] ??
        __('mobilemoney::lang.new_operation') }}</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.operation_details')</h3>
                </div>
                <form method="POST" action="{{ route('mobilemoney.transactions.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.operator')</label>
                                    <select class="form-control" name="operator_id" id="mm_operator_id" required>
                                        <option value="">@lang('mobilemoney::lang.select_operator')</option>
                                        @foreach($operators as $operator)
                                        <option value="{{ $operator->id }}" {{ (string) old('operator_id')===(string)
                                            $operator->id ? 'selected' : '' }}>{{ $operator->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('sale.date')</label>
                                    <input type="datetime-local" class="form-control" name="operation_datetime"
                                        value="{{ old('operation_datetime', now()->format('Y-m-d\\TH:i')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.customer_name') <small
                                            class="text-muted">(@lang('lang_v1.optional'))</small></label>
                                    <input type="text" class="form-control" name="customer_name"
                                        value="{{ old('customer_name') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.customer_phone') <small
                                            class="text-muted">(@lang('lang_v1.optional'))</small></label>
                                    <input type="text" class="form-control" name="customer_phone"
                                        value="{{ old('customer_phone') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('sale.amount')</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="mm_amount"
                                        name="amount" value="{{ old('amount') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.commission')</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="mm_commission"
                                        name="commission" value="{{ old('commission') }}" {{
                                        $settings->allow_manual_commission ? '' : 'readonly' }}>
                                    {{-- <p class="help-block">@lang('mobilemoney::lang.commission_help_text')</p> --}}
                                    {{-- <p class="help-block text-muted" id="mm_commission_preview_text">
                                        @lang('mobilemoney::lang.commission_preview_waiting')</p> --}}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('mobilemoney::lang.manual_reference')</label>
                                    <input type="text" class="form-control" name="external_reference"
                                        value="{{ old('external_reference') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.note')</label>
                            <textarea class="form-control" name="note" rows="3">{{ old('note') }}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                        <a href="{{ route('mobilemoney.transactions.index') }}"
                            class="btn btn-default">@lang('messages.close')</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-solid box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.reminder')</h3>
                </div>
                <div class="box-body">
                    <p>@lang('mobilemoney::lang.transaction_manual_notice')</p>
                    <ul class="list-unstyled">
                        <li>@lang('mobilemoney::lang.no_balance_check')</li>
                        <li>@lang('mobilemoney::lang.record_after_real_operation')</li>
                        <li>@lang('mobilemoney::lang.only_commission_tracked')</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var $operator = $('#mm_operator_id');
        var $amount = $('#mm_amount');
        var $commission = $('#mm_commission');
        var $message = $('#mm_commission_preview_text');
        var previewUrl = "{{ route('mobilemoney.transactions.preview_commission') }}";
        var transactionType = "{{ $type }}";
        var allowManualCommission = {{ $settings->allow_manual_commission ? 'true' : 'false' }};
        var manualOverride = allowManualCommission && $.trim($commission.val()) !== '';
        var syncingCommission = false;
        var currentRequest = null;

        function setCommissionValue(value) {
            syncingCommission = true;
            $commission.val(value);
            syncingCommission = false;
        }

        function resetPreviewMessage() {
            $message.text("@lang('mobilemoney::lang.commission_preview_waiting')");
        }

        function fetchCommissionPreview() {
            var operatorId = $operator.val();
            var amount = $.trim($amount.val());

            if (!operatorId || amount === '' || parseFloat(amount) <= 0) {
                if (!manualOverride || !allowManualCommission) {
                    setCommissionValue('');
                }
                resetPreviewMessage();
                return;
            }

            if (currentRequest) {
                currentRequest.abort();
            }

            currentRequest = $.ajax({
                url: previewUrl,
                method: 'GET',
                dataType: 'json',
                data: {
                    type: transactionType,
                    operator_id: operatorId,
                    amount: amount
                },
                success: function(result) {
                    if (!manualOverride || !allowManualCommission) {
                        setCommissionValue(result.commission);
                    }

                    $message.text(result.message);
                },
                error: function(xhr, status) {
                    if (status !== 'abort') {
                        $message.text("@lang('mobilemoney::lang.commission_preview_error')");
                    }
                },
                complete: function() {
                    currentRequest = null;
                }
            });
        }

        $operator.on('change', fetchCommissionPreview);
        $amount.on('input change', fetchCommissionPreview);

        $commission.on('input', function() {
            if (!allowManualCommission || syncingCommission) {
                return;
            }

            manualOverride = $.trim($(this).val()) !== '';

            if (!manualOverride) {
                fetchCommissionPreview();
            }
        });

        fetchCommissionPreview();
    });
</script>
@endsection