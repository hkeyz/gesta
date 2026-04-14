@extends('layouts.app')
@section('title', __('mobilemoney::lang.operators'))

@section('content')
<section class="content-header">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.operators')</h1>
        </div>
        <div class="col-md-6 col-sm-12 text-right" style="margin-top: 6px;">
            <a href="{{ route('mobilemoney.operators.create') }}" class="btn btn-primary">@lang('mobilemoney::lang.add_operator')</a>
        </div>
    </div>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('mobilemoney::lang.available_operators')</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('mobilemoney::lang.name')</th>
                        <th>@lang('mobilemoney::lang.code')</th>
                        <th>@lang('mobilemoney::lang.color')</th>
                        <th>@lang('mobilemoney::lang.sort_order')</th>
                        <th>@lang('mobilemoney::lang.status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operators as $operator)
                        <tr>
                            <td>{{ $operator->name }}</td>
                            <td>{{ $operator->code ?: '-' }}</td>
                            <td>
                                <span class="label" style="background-color: {{ $operator->color ?: '#64748b' }};">
                                    {{ $operator->color ?: '-' }}
                                </span>
                            </td>
                            <td>{{ $operator->sort_order }}</td>
                            <td>
                                @if($operator->is_active)
                                    <span class="label label-success">@lang('mobilemoney::lang.active')</span>
                                @else
                                    <span class="label label-default">@lang('mobilemoney::lang.inactive')</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('mobilemoney.operators.edit', $operator) }}" class="btn btn-xs btn-primary">@lang('messages.edit')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">@lang('lang_v1.no_data')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
