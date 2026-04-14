@extends('layouts.app')
@section('title', __('mobilemoney::lang.add_rule'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.add_rule')</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.add_rule')</h3>
                </div>
                @include('mobilemoney::commission_rules._form', [
                    'formAction' => route('mobilemoney.commission_rules.store'),
                    'formMethod' => 'POST',
                    'submitLabel' => __('mobilemoney::lang.add_rule'),
                ])
            </div>
        </div>
    </div>
</section>
@endsection
