$(document).ready(function() {
    $(document).on('click', '.add_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'json',
            success: function(result) {
                if (result.status == 'due') {
                    container.html(result.view).modal('show');
                    __currency_convert_recursively(container);
                    $('#paid_on').datetimepicker({
                        format: moment_date_format + ' ' + moment_time_format,
                        ignoreReadonly: true,
                    });
                    container.find('form#transaction_payment_add_form').validate();
                    set_default_payment_account();

                    $('.payment_modal')
                        .find('input[type="checkbox"].input-icheck')
                        .each(function() {
                            $(this).iCheck({
                                checkboxClass: 'icheckbox_square-blue',
                                radioClass: 'iradio_square-blue',
                            });
                        });
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', '.edit_payment', function(e) {
        e.preventDefault();
        var container = $('.edit_payment_modal');

        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                container.html(result).modal('show');
                __currency_convert_recursively(container);
                $('#paid_on').datetimepicker({
                    format: moment_date_format + ' ' + moment_time_format,
                    ignoreReadonly: true,
                });
                container.find('form#transaction_payment_add_form').validate();
            },
        });
    });

    $(document).on('click', '.view_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
    $(document).on('click', '.delete_payment', function(e) {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_payment,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    url: $(this).data('href'),
                    method: 'delete',
                    dataType: 'json',
                    success: function(result) {
                        if (result.success === true) {
                            $('div.payment_modal').modal('hide');
                            $('div.edit_payment_modal').modal('hide');
                            toastr.success(result.msg);
                            if (typeof purchase_table != 'undefined') {
                                purchase_table.ajax.reload();
                            }
                            if (typeof sell_table != 'undefined') {
                                sell_table.ajax.reload();
                            }
                            if (typeof expense_table != 'undefined') {
                                expense_table.ajax.reload();
                            }
                            if (typeof ob_payment_table != 'undefined') {
                                ob_payment_table.ajax.reload();
                            }
                            // project Module
                            if (typeof project_invoice_datatable != 'undefined') {
                                project_invoice_datatable.ajax.reload();
                            }
                            
                            if ($('#contact_payments_table').length) {
                                get_contact_payments();
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //view single payment
    $(document).on('click', '.view_payment', function() {
        var url = $(this).data('href');
        var container = $('.view_modal');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
});

$(document).on('change', '#transaction_payment_add_form .payment_types_dropdown', function(e) {
    set_default_payment_account();
});

function set_default_payment_account() {
    var default_accounts = {};

    var default_accounts_val = $('#transaction_payment_add_form #default_payment_accounts').val();
    if (!_.isUndefined(default_accounts_val) && default_accounts_val !== '' && default_accounts_val !== null) {
        try {
            default_accounts = JSON.parse(default_accounts_val);
        } catch (e) {
            default_accounts = {};
        }
    }

    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    if (payment_type && payment_type != 'advance') {
        var default_account = '';
        if (!_.isEmpty(default_accounts) && default_accounts[payment_type] && default_accounts[payment_type]['account']) {
            default_account = default_accounts[payment_type]['account'];
        }
        $('#transaction_payment_add_form #account_id').val(default_account);
        $('#transaction_payment_add_form #account_id').change();
    }
}

$(document).on('change', '.payment_types_dropdown', function(e) {
    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    account_dropdown = $('#transaction_payment_add_form #account_id');
    if (payment_type == 'advance') {
        if (account_dropdown) {
            account_dropdown.prop('disabled', true);
            account_dropdown.closest('.form-group').addClass('hide');
        }
    } else {
        if (account_dropdown) {
            account_dropdown.prop('disabled', false); 
            account_dropdown.closest('.form-group').removeClass('hide');
        }    
    }
});

$(document).on('submit', 'form#transaction_payment_add_form', function(e){
    e.preventDefault();
    
    var is_valid = true;
    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    var denomination_for_payment_types = [];
    var denomination_input = $('#transaction_payment_add_form .enable_cash_denomination_for_payment_methods');
    if (denomination_input.length && denomination_input.val() && denomination_input.val() !== '') {
        try {
            denomination_for_payment_types = JSON.parse(denomination_input.val());
        } catch (e) {
            denomination_for_payment_types = [];
        }
    }
    if (denomination_for_payment_types.includes(payment_type) && $('#transaction_payment_add_form .is_strict').length && $('#transaction_payment_add_form .is_strict').val() === '1' ) {
        var payment_amount = __read_number($('#transaction_payment_add_form .payment_amount'));
        var total_denomination = $('#transaction_payment_add_form').find('input.denomination_total_amount').val();
        if (payment_amount != total_denomination ) {
            is_valid = false;
        }
    }

    if (!is_valid) {
        $('#transaction_payment_add_form').find('.cash_denomination_error').removeClass('hide');
        return false;
    } else {
        $('#transaction_payment_add_form').find('.cash_denomination_error').addClass('hide');
    }
    
    var form = $(this);
    var submitBtn = form.find('button[type="submit"]');
    var originalBtnText = submitBtn.html();
    var formData = new FormData(this);
    var transaction_id = form.find('input[name="transaction_id"]').val();
    
    // Disable submit button to prevent double submission
    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + originalBtnText.replace(/<[^>]*>/g, ''));
    
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(result) {
            if (result.success === true) {
                toastr.success(result.msg);
                
                // Get transaction ID before closing modal
                var transaction_id = form.find('input[name="transaction_id"]').val();
                
                // Close the add payment modal
                $('.payment_modal').modal('hide');
                
                // Reload payment view modal - check if view_modal has payment content
                var viewPaymentModal = $('.view_modal');
                var paymentModal = $('.payment_modal');
                
                // If view_modal is visible and contains payment view, reload it
                if (viewPaymentModal.length && viewPaymentModal.find('.modal-title').text().indexOf('View Payments') !== -1) {
                    var viewPaymentUrl = '/payments/' + transaction_id;
                    $.ajax({
                        url: viewPaymentUrl,
                        dataType: 'html',
                        success: function(html) {
                            viewPaymentModal.html(html);
                            __currency_convert_recursively(viewPaymentModal);
                        }
                    });
                } else {
                    // Otherwise, reload the payment modal if it contains the payment view
                    setTimeout(function() {
                        var viewPaymentLink = $('a.view_payment_modal[href*="' + transaction_id + '"]');
                        if (viewPaymentLink.length) {
                            viewPaymentLink.click();
                        }
                    }, 500);
                }
                
                // Reload tables if they exist
                if (typeof purchase_table != 'undefined') {
                    purchase_table.ajax.reload();
                }
                if (typeof sell_table != 'undefined') {
                    sell_table.ajax.reload();
                }
                if (typeof expense_table != 'undefined') {
                    expense_table.ajax.reload();
                }
            } else {
                toastr.error(result.msg);
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        },
        error: function(xhr) {
            var msg = __('messages.something_went_wrong');
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            toastr.error(msg);
            submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
    
    return false;
})