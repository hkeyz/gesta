@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    <section class="content no-print">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">
        @if (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
        @endphp
        {!! Form::open([
            'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
            'method' => 'post',
            'id' => 'add_pos_sell_form',
        ]) !!}
        <div class="row mb-12">
            <div class="col-md-12 tw-pt-0 tw-mb-14">
                <div class="row tw-flex lg:tw-flex-row md:tw-flex-col sm:tw-flex-col tw-flex-col tw-items-start md:tw-gap-4">
                    {{-- <div class="@if (empty($pos_settings['hide_product_suggestion'])) col-md-7 @else col-md-10 col-md-offset-1 @endif no-padding pr-12"> --}}
                    <div class="tw-px-3 tw-w-full  lg:tw-px-0 lg:tw-pr-0 @if(empty($pos_settings['hide_product_suggestion'])) lg:tw-w-[60%]  @else lg:tw-w-[100%] @endif">

                        <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-mb-2 md:tw-mb-8 tw-p-2">

                            {{-- <div class="box box-solid mb-12 @if (!isMobile()) mb-40 @endif"> --}}
                                <div class="box-body pb-0">
                                    {!! Form::hidden('location_id', $default_location->id ?? null, [
                                        'id' => 'location_id',
                                        'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
                                            ? $default_location->receipt_printer_type
                                            : 'browser',
                                        'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '',
                                    ]) !!}
                                    <!-- sub_type -->
                                    {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                                    <input type="hidden" id="item_addition_method"
                                        value="{{ $business_details->item_addition_method }}">
                                    @include('sale_pos.partials.pos_form')

                                    @include('sale_pos.partials.pos_form_totals')

                                    @include('sale_pos.partials.payment_modal')

                                    @if (empty($pos_settings['disable_suspend']))
                                        @include('sale_pos.partials.suspend_note_modal')
                                    @endif

                                    @if (empty($pos_settings['disable_recurring_invoice']))
                                        @include('sale_pos.partials.recurring_invoice_modal')
                                    @endif
                                </div>
                            {{-- </div> --}}
                        </div>
                    </div>
                    @if (empty($pos_settings['hide_product_suggestion']) && !isMobile())
                        <div class="md:tw-no-padding tw-w-full lg:tw-w-[40%] tw-px-5">
                            @include('sale_pos.partials.pos_sidebar')
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @include('sale_pos.partials.pos_form_actions')
        {!! Form::close() !!}
    </section>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    @if (empty($pos_settings['hide_product_suggestion']) && isMobile())
        @include('sale_pos.partials.mobile_product_suggestions')
    @endif
    <!-- /.content -->
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('sale_pos.partials.configure_search_modal')

    @include('sale_pos.partials.recent_transactions_modal')

    @include('sale_pos.partials.weighing_scale_modal')

@stop
@section('css')
    <!-- include module css -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif

    <style type="text/css">
        /* POS Page Premium Overrides */
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        body, .pos-layout, .lockscreen, h1, h2, h3, h4, h5, h6, select, input, button, span, p, a, div {
            font-family: 'Outfit', sans-serif !important;
        }

        /* 1. Header Bar Overhaul */
        .pos-header .tw-bg-white {
            background: rgba(255, 255, 255, 0.75) !important;
            backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            border-radius: 20px !important;
            box-shadow: 0 4px 20px rgba(148, 163, 184, 0.04) !important;
            padding: 12px 20px !important;
        }
        
        .pos-header .curr_datetime {
            color: #475569 !important;
            font-weight: 600 !important;
        }
        
        .pos-header .tw-bg-\[\#646EE4\] {
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 6px 12px !important;
        }
        
        .pos-header .tw-bg-\[\#646EE4\] i,
        .pos-header .tw-bg-\[\#646EE4\] span {
            color: #475569 !important;
        }

        /* Nav and shortcuts buttons styled as minimal rounded icons */
        .pos-header a, 
        .pos-header button {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02) !important;
            transition: all 0.2s ease !important;
        }

        .pos-header a:hover, 
        .pos-header button:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-1px);
        }

        .pos-header button i.fa-calculator { color: #6366f1 !important; }
        .pos-header button.btn-danger i { color: #ef4444 !important; }
        .pos-header button.btn-success i { color: #10b981 !important; }
        .pos-header button.btn-warning i { color: #f59e0b !important; }
        .pos-header a i.fa-backward { color: #3b82f6 !important; }
        .pos-header button#recent-transactions i { color: #8b5cf6 !important; }
        .pos-header button i.fa-lock { color: #64748b !important; }
        
        /* 2. Left Cart Panel Fields & Tables */
        .pos-layout select, 
        .pos-layout input, 
        .pos-layout .select2-container--default .select2-selection--single {
            border-radius: 10px !important;
            border: 1px solid #cbd5e1 !important;
            height: 38px !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02) !important;
            transition: all 0.2s ease !important;
            background-color: #ffffff !important;
        }

        .pos-layout select:focus, 
        .pos-layout input:focus,
        .pos-layout .select2-container--default .select2-selection--single:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .pos-layout .input-group-addon {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            color: #64748b !important;
        }

        /* Cart table overrides */
        .pos-layout table.table {
            border: none !important;
            background: transparent !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;
        }
        
        .pos-layout table.table thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            padding: 12px !important;
            border: none !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .pos-layout table.table tbody tr {
            background: #ffffff !important;
            box-shadow: 0 2px 8px rgba(148, 163, 184, 0.03) !important;
            border: 1px solid #e2e8f0 !important;
            transition: all 0.2s ease !important;
        }
        
        .pos-layout table.table tbody tr td {
            padding: 14px 12px !important;
            border: none !important;
            border-top: 1px solid #e2e8f0 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            vertical-align: middle !important;
        }
        
        .pos-layout table.table tbody tr td:first-child {
            border-left: 1px solid #e2e8f0 !important;
            border-top-left-radius: 12px !important;
            border-bottom-left-radius: 12px !important;
        }
        
        .pos-layout table.table tbody tr td:last-child {
            border-right: 1px solid #e2e8f0 !important;
            border-top-right-radius: 12px !important;
            border-bottom-right-radius: 12px !important;
        }

        .pos-layout table.table tbody tr:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(148, 163, 184, 0.06) !important;
        }

        /* 3. Right Product Suggestions Panel */
        /* Category & Brand Drawer Buttons */
        #product_category_div label[for="my-drawer-4"],
        #product_brand_div label[for="my-drawer-brand"] {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            height: 48px !important;
            font-size: 0.95rem !important;
        }

        #product_category_div label[for="my-drawer-4"]:hover,
        #product_brand_div label[for="my-drawer-brand"]:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(148, 163, 184, 0.06) !important;
        }

        #product_category_div label[for="my-drawer-4"] svg,
        #product_brand_div label[for="my-drawer-brand"] svg {
            stroke: #475569 !important;
        }
        
        #product_category_div label[for="my-drawer-4"]:hover svg,
        #product_brand_div label[for="my-drawer-brand"]:hover svg {
            stroke: #1e293b !important;
        }

        /* Category/Brand inner grid buttons */
        .pos-layout .main-category-div .tw-dw-card,
        .pos-layout .product_category .tw-dw-card,
        .pos-layout .product_brand .tw-dw-card {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 2px 8px rgba(148, 163, 184, 0.03) !important;
            transition: all 0.2s ease !important;
        }
        .pos-layout .main-category-div .tw-dw-card:hover,
        .pos-layout .product_category .tw-dw-card:hover,
        .pos-layout .product_brand .tw-dw-card:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1 !important;
            box-shadow: 0 4px 12px rgba(148, 163, 184, 0.06) !important;
        }

        /* Category/Brand tabs inner block button resets */
        .pos-layout #product_category_block .btn-flat.tw-bg-blue-600,
        .pos-layout #product_brand_block a.btn-flat,
        .pos-layout #product_category_block button,
        .pos-layout #product_brand_block button {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
            transition: all 0.2s ease !important;
            font-size: 0.85rem !important;
            padding: 10px 16px !important;
        }
        
        .pos-layout #product_category_block .btn-flat.tw-bg-blue-600:hover,
        .pos-layout #product_brand_block a.btn-flat:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
            transform: translateY(-1px);
        }

        /* Product suggestion cards grid */
        .pos-layout .product_box {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 16px !important;
            box-shadow: 0 2px 8px rgba(148, 163, 184, 0.04) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
            text-align: center !important;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100px !important;
        }
        
        .pos-layout .product_box:hover {
            transform: translateY(-4px) !important;
            border-color: #bfdbfe !important;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.06) !important;
        }

        /* Hide fallback default images placeholder completely */
        .pos-layout .product_box .image-container {
            display: none !important;
        }

        .pos-layout .product_box .product_name {
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            margin-bottom: 6px !important;
        }

        .pos-layout .product_box .product_stock {
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            color: #10b981 !important; /* Elegant green for available stock */
            background-color: #ecfdf5 !important;
            padding: 3px 8px !important;
            border-radius: 6px !important;
            display: inline-block !important;
            width: fit-content !important;
            margin: 4px auto 0 auto !important;
        }
        
        /* If stock count is 0 or less, color it gray/amber */
        .pos-layout .product_box .product_stock:contains("0"),
        .pos-layout .product_box .product_stock[data-stock="0"] {
            color: #64748b !important;
            background-color: #f1f5f9 !important;
        }

        /* 4. Bottom Actions / Checkout */
        .pos-layout .pos-form-actions {
            background: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            padding: 16px 24px !important;
            box-shadow: 0 -4px 20px rgba(148, 163, 184, 0.03) !important;
        }

        .pos-layout .pos-form-actions button,
        .pos-layout .pos-form-actions a {
            border-radius: 12px !important;
            font-family: 'Outfit', sans-serif !important;
            font-weight: 600 !important;
            padding: 10px 20px !important;
            transition: all 0.2s ease !important;
            font-size: 0.9rem !important;
        }

        /* Secondary actions as minimal outlined button links */
        .pos-layout .pos-form-actions .btn-default {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
        }
        .pos-layout .pos-form-actions .btn-default:hover {
            background: #f8fafc !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
            transform: translateY(-1px);
        }

        /* Primary action button highlights */
        .pos-layout .pos-form-actions .bg-navy {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important;
        }
        .pos-layout .pos-form-actions .bg-navy:hover {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            transform: translateY(-1px);
        }

        .pos-layout .pos-form-actions .btn-success {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
        }
        .pos-layout .pos-form-actions .btn-success:hover {
            background: #059669 !important;
            border-color: #059669 !important;
            transform: translateY(-1px);
        }

        .pos-layout .pos-form-actions .btn-danger {
            background: #ef4444 !important;
            border-color: #ef4444 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2) !important;
        }
        .pos-layout .pos-form-actions .btn-danger:hover {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            transform: translateY(-1px);
        }

        .pos-layout .pos-form-actions .pos-total-display {
            font-family: 'Outfit', sans-serif !important;
            font-size: 1.875rem !important;
            font-weight: 800 !important;
            color: #2563eb !important;
            letter-spacing: -0.02em !important;
        }
    </style>
@stop
@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    @include('sale_pos.partials.keyboard_shortcuts')

    <!-- Call restaurant module if defined -->
    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <!-- include module js -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
@endsection
