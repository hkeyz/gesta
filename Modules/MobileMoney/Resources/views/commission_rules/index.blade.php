@extends('layouts.app')
@section('title', __('mobilemoney::lang.commission_rules'))

@section('content')
<section class="content-header">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.commission_rules')</h1>
        </div>
        <div class="col-md-6 col-sm-12 text-right" style="margin-top: 6px;">
            <a href="{{ route('mobilemoney.commission_rules.create') }}" class="btn btn-primary">@lang('mobilemoney::lang.add_rule')</a>
        </div>
    </div>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

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
                        <th>@lang('mobilemoney::lang.note')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td>{{ $rule->operator?->name }}</td>
                            <td>{{ $transactionTypes[$rule->transaction_type] ?? $rule->transaction_type }}</td>
                            <td>
                                @format_currency($rule->min_amount)
                                -
                                @if($rule->max_amount !== null)
                                    @format_currency($rule->max_amount)
                                @else
                                    @lang('lang_v1.all')
                                @endif
                            </td>
                            <td>
                                @if($rule->commission_type === 'percentage')
                                    {{ rtrim(rtrim(number_format($rule->commission_value, 2, '.', ''), '0'), '.') }}%
                                @else
                                    @format_currency($rule->commission_value)
                                @endif
                            </td>
                            <td>
                                @if($rule->is_active)
                                    <span class="label label-success">@lang('mobilemoney::lang.active')</span>
                                @else
                                    <span class="label label-default">@lang('mobilemoney::lang.inactive')</span>
                                @endif
                            </td>
                            <td>{{ $rule->note ?: '-' }}</td>
                            <td>
                                <a href="{{ route('mobilemoney.commission_rules.edit', $rule) }}" class="btn btn-xs btn-primary">@lang('messages.edit')</a>
                                <form method="POST" action="{{ route('mobilemoney.commission_rules.destroy', $rule) }}" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('@lang('mobilemoney::lang.confirm_delete')')">@lang('messages.delete')</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">@lang('lang_v1.no_data')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
