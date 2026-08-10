@extends('layouts.app')
@section('title', __('mobilemoney::lang.reports'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('mobilemoney::lang.reports')</h1>
</section>

<section class="content">
    @include('mobilemoney::layouts.nav')

    <style>
        .mm-report-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            min-height: 150px;
            padding: 22px 24px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .mm-report-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--mm-accent, #2563eb);
        }

        .mm-report-card__label {
            color: #475569;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .mm-report-card__value {
            color: #0f172a;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .mm-report-card__hint {
            color: #64748b;
            font-size: 13px;
            margin: 0;
        }

        .mm-report-chart {
            height: 380px;
        }
    </style>

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <form method="GET" action="{{ route('mobilemoney.reports.index') }}">
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('report.start_date')</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('report.end_date')</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">@lang('report.apply_filters')</button>
                            <a href="{{ route('mobilemoney.reports.index') }}" class="btn btn-default">@lang('mobilemoney::lang.reset_filters')</a>
                        </div>
                    </div>
                </form>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="mm-report-card" style="--mm-accent: #0ea5e9;">
                <div class="mm-report-card__label">@lang('mobilemoney::lang.total_operations')</div>
                <div class="mm-report-card__value">{{ number_format($summary['total_operations']) }}</div>
                <p class="mm-report-card__hint">@lang('mobilemoney::lang.overview_operations_hint')</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="mm-report-card" style="--mm-accent: #16a34a;">
                <div class="mm-report-card__label">@lang('mobilemoney::lang.completed_operations')</div>
                <div class="mm-report-card__value">{{ number_format($summary['completed_operations']) }}</div>
                <p class="mm-report-card__hint">@lang('mobilemoney::lang.completed_operations_hint')</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="mm-report-card" style="--mm-accent: #dc2626;">
                <div class="mm-report-card__label">@lang('mobilemoney::lang.cancelled_operations')</div>
                <div class="mm-report-card__value">{{ number_format($summary['cancelled_operations']) }}</div>
                <p class="mm-report-card__hint">@lang('mobilemoney::lang.cancelled_operations_hint')</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="mm-report-card" style="--mm-accent: #2563eb;">
                <div class="mm-report-card__label">@lang('mobilemoney::lang.deposit_total')</div>
                <div class="mm-report-card__value">@format_currency($summary['deposit_amount'])</div>
                <p class="mm-report-card__hint">@lang('mobilemoney::lang.deposit_total_hint')</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="mm-report-card" style="--mm-accent: #f59e0b;">
                <div class="mm-report-card__label">@lang('mobilemoney::lang.withdrawal_total')</div>
                <div class="mm-report-card__value">@format_currency($summary['withdrawal_amount'])</div>
                <p class="mm-report-card__hint">@lang('mobilemoney::lang.withdrawal_total_hint')</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="mm-report-card" style="--mm-accent: #7c3aed;">
                <div class="mm-report-card__label">@lang('mobilemoney::lang.commission_total')</div>
                <div class="mm-report-card__value">@format_currency($summary['commission_total'])</div>
                <p class="mm-report-card__hint">@lang('mobilemoney::lang.commission_total_hint')</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.evolution_chart')</h3>
                </div>
                <div class="box-body">
                    <div id="mm-evolution-chart" class="mm-report-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.daily_report')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('sale.date')</th>
                                <th>@lang('mobilemoney::lang.completed_operations')</th>
                                <th>@lang('mobilemoney::lang.cancelled_operations')</th>
                                <th>@lang('mobilemoney::lang.deposit_total')</th>
                                <th>@lang('mobilemoney::lang.withdrawal_total')</th>
                                <th>@lang('mobilemoney::lang.commission_total')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyRows as $row)
                                <tr>
                                    <td>{{ $row->operation_day }}</td>
                                    <td>{{ $row->completed_count }}</td>
                                    <td>{{ $row->cancelled_count }}</td>
                                    <td>@format_currency($row->deposit_amount)</td>
                                    <td>@format_currency($row->withdrawal_amount)</td>
                                    <td>@format_currency($row->commission_total)</td>
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
        </div>

        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('mobilemoney::lang.operator_breakdown')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('mobilemoney::lang.operator')</th>
                                <th>@lang('mobilemoney::lang.completed_operations')</th>
                                <th>@lang('mobilemoney::lang.deposit_total')</th>
                                <th>@lang('mobilemoney::lang.withdrawal_total')</th>
                                <th>@lang('mobilemoney::lang.commission_total')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operatorRows as $row)
                                <tr>
                                    <td>{{ $row->operator_name }}</td>
                                    <td>{{ $row->completed_count }}</td>
                                    <td>@format_currency($row->deposit_amount)</td>
                                    <td>@format_currency($row->withdrawal_amount)</td>
                                    <td>@format_currency($row->commission_total)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">@lang('lang_v1.no_data')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var chartData = @json($chartData);

        if (typeof Highcharts === 'undefined' || !$('#mm-evolution-chart').length) {
            return;
        }

        var currencySymbol = $('#__symbol').val() || '';
        var symbolPlacement = $('#__symbol_placement').val() || 'before';

        function formatCurrencyValue(value) {
            var formatted = Highcharts.numberFormat(value, 2);
            return symbolPlacement === 'after' ? formatted + ' ' + currencySymbol : currencySymbol + ' ' + formatted;
        }

        Highcharts.chart('mm-evolution-chart', {
            chart: {
                type: 'spline',
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            },
            credits: {
                enabled: false
            },
            xAxis: {
                categories: chartData.labels,
                crosshair: true
            },
            yAxis: [{
                title: {
                    text: "@lang('mobilemoney::lang.amount_axis')"
                },
                labels: {
                    formatter: function() {
                        return formatCurrencyValue(this.value);
                    }
                }
            }, {
                title: {
                    text: "@lang('mobilemoney::lang.operations_axis')"
                },
                opposite: true,
                allowDecimals: false
            }],
            tooltip: {
                shared: true,
                formatter: function() {
                    var lines = ['<strong>' + this.x + '</strong>'];

                    this.points.forEach(function(point) {
                        var isAmountSeries = point.series.userOptions.yAxis === 0;
                        var value = isAmountSeries ? formatCurrencyValue(point.y) : Highcharts.numberFormat(point.y, 0);
                        lines.push('<span style="color:' + point.color + '">\u25CF</span> ' + point.series.name + ': <strong>' + value + '</strong>');
                    });

                    return lines.join('<br>');
                }
            },
            legend: {
                align: 'center',
                verticalAlign: 'bottom'
            },
            plotOptions: {
                spline: {
                    marker: {
                        radius: 3
                    }
                },
                series: {
                    states: {
                        inactive: {
                            opacity: 1
                        }
                    }
                }
            },
            series: [{
                name: "@lang('mobilemoney::lang.deposit_total')",
                data: chartData.deposit_amounts,
                color: '#2563eb',
                yAxis: 0
            }, {
                name: "@lang('mobilemoney::lang.withdrawal_total')",
                data: chartData.withdrawal_amounts,
                color: '#f59e0b',
                yAxis: 0
            }, {
                name: "@lang('mobilemoney::lang.commission_total')",
                data: chartData.commission_totals,
                color: '#7c3aed',
                yAxis: 0
            }, {
                name: "@lang('mobilemoney::lang.completed_operations')",
                data: chartData.completed_counts,
                color: '#16a34a',
                dashStyle: 'ShortDash',
                yAxis: 1
            }, {
                name: "@lang('mobilemoney::lang.cancelled_operations')",
                data: chartData.cancelled_counts,
                color: '#dc2626',
                dashStyle: 'ShortDot',
                yAxis: 1
            }]
        });
    });
</script>
@endsection
