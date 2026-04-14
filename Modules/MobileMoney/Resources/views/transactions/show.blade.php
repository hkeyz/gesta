@extends('layouts.app')
@section('title', $transaction->entry_no)

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ $transaction->entry_no }}</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.operation_details')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <tr>
                            <th>@lang('mobilemoney::lang.reference')</th>
                            <td>{{ $transaction->entry_no }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.operation_type')</th>
                            <td>{{ $transactionTypes[$transaction->type] ?? $transaction->type }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.operator')</th>
                            <td>{{ $transaction->operator?->name }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.customer_name')</th>
                            <td>{{ $transaction->customer_name ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.customer_phone')</th>
                            <td>{{ $transaction->customer_phone ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>@lang('sale.amount')</th>
                            <td>@format_currency($transaction->amount)</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.commission')</th>
                            <td>@format_currency($transaction->commission)</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.rule_applied')</th>
                            <td>{{ $transaction->rule ? $transaction->rule->operator?->name.' / '.$transaction->rule->transaction_type : __('mobilemoney::lang.manual_or_no_rule') }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.manual_reference')</th>
                            <td>{{ $transaction->external_reference }}</td>
                        </tr>
                        <tr>
                            <th>@lang('sale.date')</th>
                            <td>{{ optional($transaction->operation_datetime)->format('Y-m-d H:i') }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.status')</th>
                            <td>
                                @if($transaction->status === 'completed')
                                    <span class="label label-success">@lang('mobilemoney::lang.completed')</span>
                                @else
                                    <span class="label label-danger">@lang('mobilemoney::lang.cancelled')</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('report.user')</th>
                            <td>{{ $transaction->creator?->user_full_name ?? $transaction->creator?->username }}</td>
                        </tr>
                        <tr>
                            <th>@lang('mobilemoney::lang.note')</th>
                            <td>{{ $transaction->note }}</td>
                        </tr>
                        @if($transaction->status === 'cancelled')
                            <tr>
                                <th>@lang('mobilemoney::lang.cancelled_at')</th>
                                <td>{{ optional($transaction->cancelled_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <th>@lang('mobilemoney::lang.cancelled_by')</th>
                                <td>{{ $transaction->canceller?->user_full_name ?? $transaction->canceller?->username }}</td>
                            </tr>
                            <tr>
                                <th>@lang('mobilemoney::lang.cancellation_note')</th>
                                <td>{{ $transaction->cancellation_note }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
                <div class="box-footer">
                    <a href="{{ route('mobilemoney.transactions.index') }}" class="btn btn-default">@lang('messages.go_back')</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            @if($transaction->status === 'completed' && (auth()->user()->can('mobile_money.cancel') || auth()->user()->can('business_settings.access') || auth()->user()->can('manage_modules')))
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('mobilemoney::lang.cancel_operation')</h3>
                    </div>
                    <form method="POST" action="{{ route('mobilemoney.transactions.cancel', $transaction) }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label>@lang('mobilemoney::lang.cancellation_note')</label>
                                <textarea class="form-control" name="cancellation_note" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('@lang('mobilemoney::lang.confirm_cancel')')">@lang('mobilemoney::lang.cancel_operation')</button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="box box-solid box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.commission_summary')</h3>
                </div>
                <div class="box-body">
                    <p><strong>@lang('mobilemoney::lang.expected_gain')</strong></p>
                    <p style="font-size: 24px;">@format_currency($transaction->commission)</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
