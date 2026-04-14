@extends('layouts.app')
@section('title', __('mobilemoney::lang.operations'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.operations')</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <form method="GET" action="{{ route('mobilemoney.transactions.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>@lang('mobilemoney::lang.operator')</label>
                                <select class="form-control" name="operator_id">
                                    <option value="">@lang('lang_v1.all')</option>
                                    @foreach($operators as $id => $name)
                                        <option value="{{ $id }}" {{ (string) ($filters['operator_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>@lang('mobilemoney::lang.operation_type')</label>
                                <select class="form-control" name="type">
                                    <option value="">@lang('lang_v1.all')</option>
                                    @foreach($transactionTypes as $value => $label)
                                        <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>@lang('mobilemoney::lang.status')</label>
                                <select class="form-control" name="status">
                                    <option value="">@lang('lang_v1.all')</option>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>@lang('mobilemoney::lang.customer_phone')</label>
                                <input type="text" class="form-control" name="customer_phone" value="{{ $filters['customer_phone'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>@lang('report.start_date')</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>@lang('report.end_date')</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">@lang('report.apply_filters')</button>
                            <a href="{{ route('mobilemoney.transactions.index') }}" class="btn btn-default">@lang('mobilemoney::lang.reset_filters')</a>
                        </div>
                    </div>
                </form>
            @endcomponent
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('mobilemoney::lang.operations')</h3>
            <div class="box-tools pull-right">
                <form method="GET" action="{{ route('mobilemoney.transactions.index') }}" class="form-inline">
                    <input type="hidden" name="operator_id" value="{{ $filters['operator_id'] ?? '' }}">
                    <input type="hidden" name="type" value="{{ $filters['type'] ?? '' }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                    <input type="hidden" name="customer_phone" value="{{ $filters['customer_phone'] ?? '' }}">
                    <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                    <label for="mm_per_page" style="margin-right: 8px;">@lang('mobilemoney::lang.entries_per_page')</label>
                    <select class="form-control input-sm" id="mm_per_page" name="per_page" onchange="this.form.submit()">
                        @foreach($perPageOptions as $value)
                            <option value="{{ $value }}" {{ (int) $perPage === (int) $value ? 'selected' : '' }}>
                                {{ $value === -1 ? __('lang_v1.all') : $value }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('sale.date')</th>
                        <th>@lang('mobilemoney::lang.reference')</th>
                        <th>@lang('mobilemoney::lang.operator')</th>
                        <th>@lang('mobilemoney::lang.operation_type')</th>
                        <th>@lang('mobilemoney::lang.customer_phone')</th>
                        <th>@lang('sale.amount')</th>
                        <th>@lang('mobilemoney::lang.commission')</th>
                        <th>@lang('mobilemoney::lang.status')</th>
                        <th>@lang('report.user')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ optional($transaction->operation_datetime)->format('Y-m-d H:i') }}</td>
                            <td>{{ $transaction->entry_no }}</td>
                            <td>{{ $transaction->operator?->name }}</td>
                            <td>{{ $transactionTypes[$transaction->type] ?? $transaction->type }}</td>
                            <td>{{ $transaction->customer_phone ?: '-' }}</td>
                            <td>@format_currency($transaction->amount)</td>
                            <td>@format_currency($transaction->commission)</td>
                            <td>
                                @if($transaction->status === 'completed')
                                    <span class="label label-success">@lang('mobilemoney::lang.completed')</span>
                                @else
                                    <span class="label label-danger">@lang('mobilemoney::lang.cancelled')</span>
                                @endif
                            </td>
                            <td>{{ $transaction->creator?->user_full_name ?? $transaction->creator?->username }}</td>
                            <td>
                                <a href="{{ route('mobilemoney.transactions.show', $transaction) }}" class="btn btn-xs btn-primary">@lang('messages.view')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">@lang('lang_v1.no_data')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="box-footer clearfix">
            <div class="pull-left" style="padding-top: 8px;">
                @if($transactions->total() > 0)
                    @lang('mobilemoney::lang.pagination_summary', ['from' => $transactions->firstItem(), 'to' => $transactions->lastItem(), 'total' => $transactions->total()])
                @else
                    @lang('lang_v1.no_data')
                @endif
            </div>
            <div class="pull-right">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
