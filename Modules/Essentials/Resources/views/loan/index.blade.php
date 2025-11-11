@extends('layouts.app')
@section('title', __('essentials::lang.loan'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.loan')
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
        @component('components.filters', ['title' => __('report.filters'), 'class' => 'box-solid'])
            @if(!empty($users))
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('user_id_filter', __('essentials::lang.employee') . ':') !!}
                    {!! Form::select('user_id_filter', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            @endif
            <div class="col-md-3">
                <div class="form-group">
                    <label for="status_filter">@lang( 'sale.status' ):</label>
                    <select class="form-control select2" name="status_filter" required id="status_filter" style="width: 100%;">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($loan_statuses as $key => $value)
                            <option value="{{$key}}">{{$value['name']}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#all_loan_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list" aria-hidden="true"></i> @lang('essentials::lang.all_loan')</a>
                    </li>
                    @if($can_manage_loan)
                    <li>
                        <a href="#loan_request_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-clock" aria-hidden="true"></i> @lang('essentials::lang.loan_request')</a>
                    </li>
                    @endif
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="all_loan_tab">
                        @component('components.widget', ['class' => 'box-solid', 'title' => __( 'essentials::lang.all_loan' )])
                            @slot('tool')
                                @if($can_request_loan)
                                <div class="box-tools">
                                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right btn-modal"
                                        data-href="{{action([\Modules\Essentials\Http\Controllers\LoanController::class, 'create'])}}" data-container="#add_loan_modal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 5l0 14" />
                                            <path d="M5 12l14 0" />
                                        </svg> @lang('messages.add')
                                    </button>
                                </div>
                                @endif
                            @endslot
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="loan_table">
                                    <thead>
                                        <tr>
                                            <th>@lang( 'purchase.ref_no' )</th>
                                            <th>@lang('essentials::lang.employee')</th>
                                            <th>@lang( 'essentials::lang.loan_amount' )</th>
                                            <th>@lang( 'essentials::lang.remaining_loan' )</th>
                                            <th>@lang( 'essentials::lang.reason' )</th>
                                            <th>@lang( 'sale.status' )</th>
                                            <th>@lang( 'lang_v1.date' )</th>
                                            <th>@lang( 'messages.action' )</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        @endcomponent
                    </div>
                    @if($can_manage_loan)
                    <div class="tab-pane" id="loan_request_tab">
                        @component('components.widget', ['class' => 'box-solid', 'title' => __( 'essentials::lang.loan_request' )])
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="loan_request_table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>@lang( 'purchase.ref_no' )</th>
                                            <th>@lang('essentials::lang.employee')</th>
                                            <th>@lang( 'essentials::lang.loan_amount' )</th>
                                            <th>@lang( 'essentials::lang.remaining_loan' )</th>
                                            <th>@lang( 'essentials::lang.reason' )</th>
                                            <th>@lang( 'sale.status' )</th>
                                            <th>@lang( 'lang_v1.date' )</th>
                                            <th>@lang( 'messages.action' )</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        @endcomponent
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade" id="add_loan_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
 <div class="modal fade change_status_modal" id="change_status_modal"  tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            loan_table = $('#loan_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: {
                    "url": "{{action([\Modules\Essentials\Http\Controllers\LoanController::class, 'index'])}}",
                    "data" : function(d) {
                        if ($('#user_id_filter').length) {
                            d.user_id = $('#user_id_filter').val();
                        }
                        d.status = $('#status_filter').val();
                    },
                    "error": function(xhr, error, thrown) {
                        console.log('Loan table error:', error, thrown);
                        toastr.error(__('messages.something_went_wrong'));
                    }
                },
                columnDefs: [
                    {
                        targets: 7,
                        orderable: false,
                        searchable: false,
                    },
                ],
                columns: [
                    { data: 'ref_no', name: 'ref_no' },
                    { data: 'user', name: 'user' },
                    { data: 'loan_amount', name: 'loan_amount'},
                    { data: 'remaining_loan', name: 'remaining_loan'},
                    { data: 'reason', name: 'essentials_loans.reason'},
                    { data: 'status', name: 'essentials_loans.status'},
                    { data: 'created_at', name: 'essentials_loans.created_at'},
                    { data: 'action', name: 'action' },
                ],
            });

            @if($can_manage_loan)
            loan_request_table = $('#loan_request_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                autoWidth: false,
                ajax: {
                    "url": "{{action([\Modules\Essentials\Http\Controllers\LoanController::class, 'index'])}}",
                    "data" : function(d) {
                        d.tab_type = 'loan_request';
                    },
                    "error": function(xhr, error, thrown) {
                        console.log('Loan request table error:', error, thrown);
                        toastr.error(__('messages.something_went_wrong'));
                    }
                },
                columnDefs: [
                    {
                        targets: 7,
                        orderable: false,
                        searchable: false,
                    },
                ],
                columns: [
                    { data: 'ref_no', name: 'ref_no' },
                    { data: 'user', name: 'user' },
                    { data: 'loan_amount', name: 'loan_amount'},
                    { data: 'remaining_loan', name: 'remaining_loan'},
                    { data: 'reason', name: 'essentials_loans.reason'},
                    { data: 'status', name: 'essentials_loans.status'},
                    { data: 'created_at', name: 'essentials_loans.created_at'},
                    { data: 'action', name: 'action' },
                ],
            });

            // Fix table width when tab is shown
            $('a[href="#loan_request_tab"]').on('shown.bs.tab', function (e) {
                setTimeout(function() {
                    if (typeof loan_request_table !== 'undefined' && loan_request_table) {
                        loan_request_table.columns.adjust();
                        loan_request_table.draw(false);
                    }
                }, 200);
            });
            @endif

            $(document).on( 'change', '#user_id_filter, #status_filter', function() {
                loan_table.ajax.reload();
            });

            $('#add_loan_modal').on('shown.bs.modal', function(e) {
                $('#add_loan_modal .select2').select2();
            });

            $('#add_loan_modal').on('hidden.bs.modal', function(e) {
                $(this).html('');
            });

            $(document).on('submit', 'form#add_loan_form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                $submitBtn.attr('disabled', true);
                var data = $form.serialize();
                var ladda = Ladda.create(document.querySelector('.add-loan-btn'));
                ladda.start();
                $.ajax({
                    method: $form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        ladda.stop();
                        $submitBtn.attr('disabled', false);
                        if (result.success == true) {
                            $form[0].reset();
                            $('div#add_loan_modal').modal('hide');
                            toastr.success(result.msg);
                            // Reload table after a small delay to ensure modal is closed
                            setTimeout(function() {
                                if (typeof loan_table !== 'undefined' && loan_table) {
                                    loan_table.ajax.reload(null, false);
                                }
                                @if($can_manage_loan)
                                if (typeof loan_request_table !== 'undefined' && loan_request_table) {
                                    loan_request_table.ajax.reload(null, false);
                                }
                                @endif
                            }, 300);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        ladda.stop();
                        $submitBtn.attr('disabled', false);
                        toastr.error(__('messages.something_went_wrong'));
                    }
                });
            });

            $(document).on('click', 'a.change_status', function(e) {
                e.preventDefault();
                var loan_id = $(this).data('loan-id');
                var status = $(this).data('orig-value');
                var status_note = $(this).data('status-note') || '';
                var loan_amount = $(this).data('loan-amount') || '';
                
                $('#change_status_modal').html('');
                $('#change_status_modal').html(
                    '<div class="modal-dialog" role="document">' +
                    '<div class="modal-content">' +
                    '<form id="change_status_form" method="post" action="{{action([\Modules\Essentials\Http\Controllers\LoanController::class, "changeStatus"])}}">' +
                    '<div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    '<h4 class="modal-title">@lang("essentials::lang.change_status")</h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<input type="hidden" name="loan_id" value="' + loan_id + '">' +
                    '<div class="form-group">' +
                    '<label for="status">@lang("sale.status"):*</label>' +
                    '<select class="form-control select2" name="status" required id="status_dropdown" style="width: 100%;">' +
                    '<option value="pending" ' + (status == 'pending' ? 'selected' : '') + '>@lang("lang_v1.pending")</option>' +
                    '<option value="approved" ' + (status == 'approved' ? 'selected' : '') + '>@lang("essentials::lang.approved")</option>' +
                    '<option value="rejected" ' + (status == 'rejected' ? 'selected' : '') + '>@lang("essentials::lang.rejected")</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="form-group" id="monthly_deduction_group" style="display: none;">' +
                    '<label for="monthly_deduction">@lang("essentials::lang.monthly_deduction"):*</label>' +
                    '<input type="text" class="form-control input_number" name="monthly_deduction" id="monthly_deduction" placeholder="@lang("essentials::lang.monthly_deduction")">' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label for="status_note">@lang("brand.note"):</label>' +
                    '<textarea class="form-control" name="status_note" rows="3" id="status_note">' + status_note + '</textarea>' +
                    '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ladda-button update-loan-status" data-style="expand-right">' +
                    '<span class="ladda-label">@lang("messages.update")</span>' +
                    '</button>' +
                    '<button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang("messages.close")</button>' +
                    '</div>' +
                    '</form>' +
                    '</div>' +
                    '</div>'
                );
                $('#change_status_modal').modal('show');
                $('#change_status_modal .select2').select2();
                
                // Show/hide monthly deduction field based on status
                $('#status_dropdown').on('change', function() {
                    if ($(this).val() == 'approved') {
                        $('#monthly_deduction_group').show();
                        $('#monthly_deduction').attr('required', true);
                    } else {
                        $('#monthly_deduction_group').hide();
                        $('#monthly_deduction').removeAttr('required');
                    }
                });
                
                // Trigger change on load if status is approved
                if (status == 'approved') {
                    $('#status_dropdown').trigger('change');
                }
            });

            $(document).on('submit', 'form#change_status_form', function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                var ladda = Ladda.create(document.querySelector('.update-loan-status'));
                ladda.start();
                $.ajax({
                    method: $(this).attr('method'),
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        ladda.stop();
                        if (result.success == true) {
                            $('div#change_status_modal').modal('hide');
                            toastr.success(result.msg);
                            loan_table.ajax.reload();
                            @if($can_manage_loan)
                            loan_request_table.ajax.reload();
                            @endif
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            $(document).on('click', 'button.delete-loan', function() {
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
                                    loan_table.ajax.reload();
                                    @if($can_manage_loan)
                                    loan_request_table.ajax.reload();
                                    @endif
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
        });
    </script>
@endsection

