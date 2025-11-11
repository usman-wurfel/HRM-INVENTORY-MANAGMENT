@extends('layouts.app')
@section('title', 'Cash In / Cash Out Report')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Cash In / Cash Out Report
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="print_section">
        <h2>{{ session()->get('business.name') }} - Cash In / Cash Out Report</h2>
    </div>

    <div class="row no-print">
        <div class="col-md-4 col-xs-12">
            <div class="input-group">
                <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                <select class="form-control select2" id="cash_flow_location_filter">
                    @foreach ($business_locations as $key => $value)
                        <option value="{{ $key }}" {{ $location_id == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    
        <div class="col-md-4 col-xs-12">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon bg-light-blue"><i class="fa fa-calendar"></i></span>
                    <input type="text" class="form-control" id="cash_flow_date_filter" value="{{ $start_date }} to {{ $end_date }}" readonly>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 col-xs-12">
            <div class="form-group">
                <button type="button" class="btn btn-primary tw-dw-btn tw-dw-btn-primary form-control" id="filter_cash_flow" style="height: 34px;">
                    <i class="fa fa-filter"></i> @lang('report.filters')
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fa fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">Total Cash In</span>
                    <span class="info-box-number" style="color: white;">{{ number_format($total_cash_in, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">Total Cash Out</span>
                    <span class="info-box-number" style="color: white;">{{ number_format($total_cash_out, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box {{ $final_balance >= 0 ? 'bg-green' : 'bg-yellow' }}">
                <span class="info-box-icon"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">Final Balance</span>
                    <span class="info-box-number" style="color: white;">{{ number_format($final_balance, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">Total No. of entries: {{ count($cash_flow_data) }}</h3>
                    <div class="box-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="window.print();">
                            <i class="fa fa-print"></i> @lang('messages.print')
                        </button>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="cash_flow_table">
                        <thead>
                            <tr class="bg-gray">
                                <th>Date</th>
                                <th>Remark</th>
                                <th>Entry by</th>
                                <th>Contact</th>
                                <th>Category</th>
                                <th>Mode</th>
                                <th class="text-right">Cash In</th>
                                <th class="text-right">Cash Out</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cash_flow_data as $item)
                            <tr>
                                <td>{{ @format_date($item['date']) }}</td>
                                <td>{{ $item['remark'] }}</td>
                                <td>{{ $item['entry_by'] }}</td>
                                <td>{{ $item['contact'] }}</td>
                                <td>{{ $item['category'] }}</td>
                                <td>{{ $item['mode'] }}</td>
                                <td class="text-right {{ $item['cash_in'] > 0 ? 'text-success' : '' }}">
                                    @if($item['cash_in'] > 0)
                                        {{ number_format($item['cash_in'], 2) }}
                                    @endif
                                </td>
                                <td class="text-right {{ $item['cash_out'] > 0 ? 'text-danger' : '' }}">
                                    @if($item['cash_out'] > 0)
                                        {{ number_format($item['cash_out'], 2) }}
                                    @endif
                                </td>
                                <td class="text-right">
                                    <strong>{{ number_format($item['balance'], 2) }}</strong>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No data available for selected date range</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($cash_flow_data) > 0)
                        <tfoot>
                            <tr class="bg-gray">
                                <th colspan="6" class="text-right">Total:</th>
                                <th class="text-right text-success">{{ number_format($total_cash_in, 2) }}</th>
                                <th class="text-right text-danger">{{ number_format($total_cash_out, 2) }}</th>
                                <th class="text-right">
                                    <strong>{{ number_format($final_balance, 2) }}</strong>
                                </th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize date range picker
        $('#cash_flow_date_filter').daterangepicker({
            startDate: moment('{{ $start_date }}'),
            endDate: moment('{{ $end_date }}'),
            locale: {
                format: moment_date_format
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        // Filter button click
        $('#filter_cash_flow').click(function() {
            var start_date = $('#cash_flow_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            var end_date = $('#cash_flow_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            var location_id = $('#cash_flow_location_filter').val();

            var url = '/reports/cash-flow?start_date=' + start_date + '&end_date=' + end_date;
            if (location_id) {
                url += '&location_id=' + location_id;
            }

            window.location.href = url;
        });

        // Location filter change
        $('#cash_flow_location_filter').change(function() {
            $('#filter_cash_flow').click();
        });
    });
</script>
@endsection

