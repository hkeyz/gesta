@extends('layouts.app')
@section('title', __('mobilemoney::lang.operators'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.operators')</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.add_operator')</h3>
                </div>
                <form method="POST" action="{{ route('mobilemoney.operators.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.name')</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.code')</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code') }}">
                        </div>
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.color')</label>
                            <input type="text" class="form-control" name="color" value="{{ old('color', '#1f6feb') }}">
                        </div>
                        <div class="form-group">
                            <label>@lang('mobilemoney::lang.sort_order')</label>
                            <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', 0) }}">
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                @lang('mobilemoney::lang.active')
                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">@lang('mobilemoney::lang.add_operator')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
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
                            @foreach($operators as $operator)
                                <tr>
                                    <td>
                                        <form id="operator-form-{{ $operator->id }}" method="POST" action="{{ route('mobilemoney.operators.update', $operator) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <input type="text" class="form-control" name="name" value="{{ $operator->name }}" form="operator-form-{{ $operator->id }}" required>
                                    </td>
                                    <td><input type="text" class="form-control" name="code" value="{{ $operator->code }}" form="operator-form-{{ $operator->id }}"></td>
                                    <td><input type="text" class="form-control" name="color" value="{{ $operator->color }}" form="operator-form-{{ $operator->id }}"></td>
                                    <td><input type="number" min="0" class="form-control" name="sort_order" value="{{ $operator->sort_order }}" form="operator-form-{{ $operator->id }}"></td>
                                    <td>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="is_active" value="1" form="operator-form-{{ $operator->id }}" {{ $operator->is_active ? 'checked' : '' }}>
                                            @lang('mobilemoney::lang.active')
                                        </label>
                                    </td>
                                    <td>
                                        <button type="submit" class="btn btn-xs btn-primary" form="operator-form-{{ $operator->id }}">@lang('messages.update')</button>
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
