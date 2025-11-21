@extends('layouts.app')
@section('title', __('essentials::lang.holiday'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.holiday')
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
        @component('components.filters', ['title' => __('report.filters'), 'class' => 'box-solid'])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}

                    {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('holiday_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('holiday_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
        @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid', 'title' => __( 'essentials::lang.all_holidays' )])
                @if($is_admin || auth()->user()->can('essentials.crud_holiday'))
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right btn-modal"
                            data-href="{{action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'create'])}}" data-container="#add_holiday_modal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang( 'messages.add' )
                        </button>
                    </div>
                @endslot
                @endif
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="holidays_table">
                        <thead>
                            <tr>
                                <th>@lang( 'lang_v1.name' )</th>
                                <th>@lang( 'lang_v1.date' )</th>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang( 'business.business_location' )</th>
                                <th>@lang( 'brand.note' )</th>
                                @if($is_admin || auth()->user()->can('essentials.crud_holiday'))
                                    <th>@lang( 'messages.action' )</th>
                                @endif
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade" id="add_holiday_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            holidays_table = $('#holidays_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: {
                    "url": "{{action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'index'])}}",
                    "data" : function(d) {
                        d.location_id = $('#location_id').val();
                        if($('#holiday_filter_date_range').val()) {
                            var start = $('#holiday_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#holiday_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                    }
                },
                @if($is_admin || auth()->user()->can('essentials.crud_holiday'))
                columnDefs: [
                    {
                        targets: 5,
                        orderable: false,
                        searchable: false,
                    },
                ],
                @endif
                columns: [
                    { data: 'name', name: 'essentials_holidays.name' },
                    { data: 'start_date', name: 'start_date'},
                    { data: 'employee', name: 'employee'},
                    { data: 'location', name: 'bl.name' },
                    { data: 'note', name: 'note'},
                    @if($is_admin || auth()->user()->can('essentials.crud_holiday'))
                    { data: 'action', name: 'action' },
                    @endif
                ],
            });

            $('#holiday_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#holiday_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                }
            );
            $('#holiday_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#holiday_filter_date_range').val('');
                holidays_table.ajax.reload();
            });

            $(document).on( 'change', '#holiday_filter_date_range, #location_id', function() {
                holidays_table.ajax.reload();
            });

            $('#add_holiday_modal').on('shown.bs.modal', function(e) {
                $('#add_holiday_modal .select2').select2();

                $('form#add_holiday_form #holiday_start_date, form#add_holiday_form #holiday_end_date').datepicker({
                    autoclose: true,
                }).on('show', function() {
                    // Fix z-index for datepicker to show above modal
                    $('.datepicker').css('z-index', '9999');
                });

                // Handle holiday type change
                $('#holiday_type').on('change', function() {
                    if ($(this).val() == 'consecutive') {
                        $('#normal_start_date_field').hide();
                        $('#normal_end_date_field').hide();
                        $('#consecutive_holiday_fields').show();
                        $('#holiday_start_date').removeAttr('required');
                        $('#holiday_end_date').removeAttr('required');
                    } else {
                        $('#normal_start_date_field').show();
                        $('#normal_end_date_field').show();
                        $('#consecutive_holiday_fields').hide();
                        $('#holiday_start_date').attr('required', 'required');
                        $('#holiday_end_date').attr('required', 'required');
                    }
                });

                // Handle repeat type change
                $('#repeat_type').on('change', function() {
                    var repeatType = $(this).val();
                    if (repeatType == 'week') {
                        $('#weekdays_field').show();
                        $('#repeat_pattern_field').show();
                        $('#repeat_days_field').hide();
                        $('#custom_dates_field').hide();
                        $('#weekdays_select').attr('required', 'required');
                        $('#repeat_days_input').removeAttr('required');
                        $('#custom_dates_input').removeAttr('required');
                    } else if (repeatType == 'month') {
                        $('#weekdays_field').hide();
                        $('#repeat_pattern_field').hide();
                        $('#gap_weeks_field').hide();
                        $('#repeat_days_field').show();
                        $('#custom_dates_field').hide();
                        $('#weekdays_select').removeAttr('required');
                        $('#repeat_days_input').attr('required', 'required');
                        $('#custom_dates_input').removeAttr('required');
                    } else if (repeatType == 'custom') {
                        $('#weekdays_field').hide();
                        $('#repeat_pattern_field').hide();
                        $('#gap_weeks_field').hide();
                        $('#repeat_days_field').hide();
                        $('#custom_dates_field').show();
                        $('#weekdays_select').removeAttr('required');
                        $('#repeat_days_input').removeAttr('required');
                        $('#custom_dates_input').attr('required', 'required');
                    }
                });

                // Handle repeat pattern change
                $('#repeat_pattern').on('change', function() {
                    if ($(this).val() == 'gap') {
                        $('#gap_weeks_field').show();
                    } else {
                        $('#gap_weeks_field').hide();
                    }
                });

                // Initialize custom dates picker - click to add dates
                $(document).on('focus', '#custom_dates_input', function() {
                    var $input = $(this);
                    if (!$input.data('datepicker-initialized')) {
                        // Use the input itself for datepicker
                        $input.datepicker({
                            format: 'yyyy-mm-dd',
                            autoclose: false,
                            todayHighlight: true,
                            orientation: 'bottom auto',
                            clearBtn: false,
                            multidate: false
                        });
                        
                        // Fix z-index and positioning for datepicker to show above modal and below input
                        $input.on('show', function() {
                            setTimeout(function() {
                                var $datepicker = $('.datepicker');
                                $datepicker.css({
                                    'z-index': '9999',
                                    'position': 'absolute'
                                });
                                
                                // Position datepicker below the input field
                                var inputOffset = $input.offset();
                                var inputHeight = $input.outerHeight();
                                
                                $datepicker.css({
                                    'top': (inputOffset.top + inputHeight + 5) + 'px',
                                    'left': inputOffset.left + 'px'
                                });
                            }, 10);
                        });
                        
                        // Handle date selection - add to list
                        $input.on('changeDate', function(e) {
                            if (!e.date) {
                                return;
                            }
                            
                            // Get selected date
                            var dateObj = e.date;
                            var year = dateObj.getFullYear();
                            var month = dateObj.getMonth() + 1;
                            var day = dateObj.getDate();
                            
                            // Format with leading zeros
                            month = (month < 10) ? '0' + month : month;
                            day = (day < 10) ? '0' + day : day;
                            
                            var selectedDate = year + '-' + month + '-' + day;
                            
                            // Get current dates from data attribute (not from input value)
                            var currentValue = $input.attr('data-dates') || '';
                            var currentDates = currentValue ? currentValue.split(',').map(function(d) { return d.trim(); }).filter(function(d) { return d && d.length > 0; }) : [];
                            
                            // Check if date already exists
                            if (currentDates.indexOf(selectedDate) === -1) {
                                currentDates.push(selectedDate);
                                // Sort dates
                                currentDates.sort(function(a, b) {
                                    return new Date(a) - new Date(b);
                                });
                                
                                var newValue = currentDates.join(', ');
                                
                                // Store in data attribute
                                $input.attr('data-dates', newValue);
                            }
                            
                            // Update input value with all dates
                            var allDates = $input.attr('data-dates') || '';
                            $input.val(allDates);
                            
                            // Prevent datepicker from updating the value
                            return false;
                        });
                        
                        $input.data('datepicker-initialized', true);
                    }
                    
                    // Show calendar
                    $input.datepicker('show');
                });

                // Trigger change on load if repeat_type is week (for default value)
                setTimeout(function() {
                    if ($('#repeat_type').length && $('#repeat_type').val() == 'week') {
                        $('#repeat_type').trigger('change');
                    }
                }, 100);
            });

            // Clear modal on close
            $('#add_holiday_modal').on('hidden.bs.modal', function(e) {
                $(this).html('');
            });

            $(document).on('submit', 'form#add_holiday_form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                $submitBtn.attr('disabled', true);
                
                // Get form data using serializeArray to properly handle value 0 (Sunday)
                var formArray = $form.serializeArray();
                var data = {};
                
                // Build data object from form array
                $.each(formArray, function(i, field) {
                    if (field.name.indexOf('[]') !== -1) {
                        // Handle array fields like weekdays[]
                        var name = field.name.replace('[]', '');
                        if (!data[name]) {
                            data[name] = [];
                        }
                        data[name].push(field.value);
                    } else {
                        data[field.name] = field.value;
                    }
                });
                
                // Explicitly get weekdays from select2 to ensure value 0 (Sunday) is included
                var weekdaysSelect = $form.find('#weekdays_select');
                if (weekdaysSelect.length) {
                    var selectedWeekdays = weekdaysSelect.val();
                    if (selectedWeekdays && selectedWeekdays.length > 0) {
                        data.weekdays = selectedWeekdays;
                    }
                }

                $.ajax({
                    method: $form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div#add_holiday_modal').modal('hide');
                            $('div#add_holiday_modal').html('');
                            toastr.success(result.msg);
                            setTimeout(function() {
                                if (typeof holidays_table !== 'undefined' && holidays_table) {
                                    holidays_table.ajax.reload();
                                }
                            }, 500);
                        } else {
                            toastr.error(result.msg);
                            $submitBtn.attr('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : __('messages.something_went_wrong');
                        toastr.error(errorMsg);
                        $submitBtn.attr('disabled', false);
                    }
                });
            });
        });

        $(document).on('click', 'button.delete-holiday', function() {
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();

                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                holidays_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    </script>
@endsection
