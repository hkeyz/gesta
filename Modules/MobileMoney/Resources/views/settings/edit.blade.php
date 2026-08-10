@extends('layouts.app')
@section('title', __('mobilemoney::lang.settings'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.settings')</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.general_settings')</h3>
                </div>
                <form method="POST" action="{{ route('mobilemoney.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terminal_label">@lang('mobilemoney::lang.terminal_label')</label>
                                    <input type="text" class="form-control" id="terminal_label" name="terminal_label" value="{{ old('terminal_label', $settings->terminal_label) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terminal_number">@lang('mobilemoney::lang.terminal_number')</label>
                                    <input type="text" class="form-control" id="terminal_number" name="terminal_number" value="{{ old('terminal_number', $settings->terminal_number) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="auto_assign_reference" value="1" {{ old('auto_assign_reference', $settings->auto_assign_reference) ? 'checked' : '' }}>
                                        @lang('mobilemoney::lang.auto_assign_reference')
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="allow_manual_commission" value="1" {{ old('allow_manual_commission', $settings->allow_manual_commission) ? 'checked' : '' }}>
                                        @lang('mobilemoney::lang.allow_manual_commission')
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="receipt_footer">@lang('mobilemoney::lang.receipt_footer')</label>
                            <textarea class="form-control" id="receipt_footer" name="receipt_footer" rows="4">{{ old('receipt_footer', $settings->receipt_footer) }}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-solid box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.module_name')</h3>
                </div>
                <div class="box-body">
                    <p>@lang('mobilemoney::lang.settings_help_text')</p>
                    <ul class="list-unstyled">
                        <li>@lang('mobilemoney::lang.single_terminal_only')</li>
                        <li>@lang('mobilemoney::lang.manual_only')</li>
                        <li>@lang('mobilemoney::lang.no_balance_check')</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
