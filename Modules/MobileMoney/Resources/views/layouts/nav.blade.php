<div class="row">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                @if(auth()->user()->can('mobile_money.transactions') || auth()->user()->can('mobile_money.access') || auth()->user()->can('business_settings.access') || auth()->user()->can('manage_modules'))
                    <li class="{{ request()->routeIs('mobilemoney.transactions.*') ? 'active' : '' }}">
                        <a href="{{ route('mobilemoney.transactions.index') }}">@lang('mobilemoney::lang.operations')</a>
                    </li>
                @endif
                @if(auth()->user()->can('mobile_money.reports') || auth()->user()->can('mobile_money.access') || auth()->user()->can('business_settings.access') || auth()->user()->can('manage_modules'))
                    <li class="{{ request()->routeIs('mobilemoney.reports.*') ? 'active' : '' }}">
                        <a href="{{ route('mobilemoney.reports.index') }}">@lang('mobilemoney::lang.reports')</a>
                    </li>
                @endif
                @if(auth()->user()->can('mobile_money.operators') || auth()->user()->can('mobile_money.settings') || auth()->user()->can('business_settings.access') || auth()->user()->can('manage_modules'))
                    <li class="{{ request()->routeIs('mobilemoney.operators.*') ? 'active' : '' }}">
                        <a href="{{ route('mobilemoney.operators.index') }}">@lang('mobilemoney::lang.operators')</a>
                    </li>
                @endif
                @if(auth()->user()->can('mobile_money.commissions') || auth()->user()->can('mobile_money.settings') || auth()->user()->can('business_settings.access') || auth()->user()->can('manage_modules'))
                    <li class="{{ request()->routeIs('mobilemoney.commission_rules.*') ? 'active' : '' }}">
                        <a href="{{ route('mobilemoney.commission_rules.index') }}">@lang('mobilemoney::lang.commission_rules')</a>
                    </li>
                @endif
                @if(auth()->user()->can('mobile_money.settings') || auth()->user()->can('business_settings.access') || auth()->user()->can('manage_modules'))
                    <li class="{{ request()->routeIs('mobilemoney.settings.*') ? 'active' : '' }}">
                        <a href="{{ route('mobilemoney.settings.edit') }}">@lang('mobilemoney::lang.settings')</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
