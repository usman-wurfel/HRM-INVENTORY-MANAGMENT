$(document).ready(function() {
    //get suppliers
    $('#supplier_id').select2({
        ajax: {
            url: '/purchases/get_suppliers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        minimumInputLength: 1,
        escapeMarkup: function(m) {
            return m;
        },
        templateResult: function(data) {
            if (!data.id) {
                return data.text;
            }
            var html = data.text + ' - ' + data.business_name + ' (' + data.contact_id + ')';
            return html;
        }
    });
    //Add products
    if ($('#search_product_for_purchase_return').length > 0) {
        //Add Product
        $('#search_product_for_purchase_return')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        purchase_return_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_purchase_return').removeAttr('disabled');
        } else {
            $('#search_product_for_purchase_return').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
    });

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'select.product_tax_id', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    $('form#purchase_return_form').validate();

    $(document).on('click', 'button#submit_purchase_return_form', function(e) {
        e.preventDefault();

        //Check if product is present or not.
        if ($('table#purchase_return_product_table tbody tr').length <= 0) {
            toastr.warning(LANG.no_products_added);
            $('input#search_product_for_purchase_return').select();
            return false;
        }

        if ($('form#purchase_return_form').valid()) {
            $('form#purchase_return_form').submit();
        }
    });


    $('#purchase_return_product_table tbody')
    .find('.expiry_datepicker')
    .each(function() {
        $(this).datepicker({
            autoclose: true,
            format: datepicker_date_format,
        });
    });


});

function purchase_return_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/purchase-return/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id },
        dataType: 'html',
        success: function(result) {
            $('table#purchase_return_product_table tbody').append(result);
            
            $('table#purchase_return_product_table tbody tr:last').find('.expiry_datepicker').datepicker({
                autoclose: true,
                format: datepicker_date_format,
            });
            
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_table_total() {
    var table_total = 0;
    var total_tax = 0;
    $('table#purchase_return_product_table tbody tr').each(function() {
        var quantity = parseFloat(__read_number($(this).find('input.product_quantity')));
        var unit_price = parseFloat(__read_number($(this).find('input.product_unit_price')));
        var subtotal_before_tax = 0;
        if (quantity && unit_price) {
            subtotal_before_tax = quantity * unit_price;
        }
        
        // Calculate tax for this line
        var tax_rate = parseFloat($(this).find('select.product_tax_id').find(':selected').data('tax_amount')) || 0;
        var tax_type = $(this).find('select.product_tax_id').find(':selected').data('tax_type') || 'percentage';
        var line_tax = __calculate_amount(tax_type, tax_rate, subtotal_before_tax);
        
        $(this).find('input.product_item_tax').val(line_tax);
        
        var line_total = subtotal_before_tax + line_tax;
        $(this).find('input.product_line_total').val(__number_f(line_total));
        
        table_total += subtotal_before_tax;
        total_tax += line_tax;
    });
    
    __write_number($('input#tax_amount'), total_tax);
    $('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
    var final_total = table_total + total_tax;
    $('input#total_amount').val(final_total);
    $('span#total_return').text(__number_f(final_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var subtotal_before_tax = 0;
    if (quantity && unit_price) {
        subtotal_before_tax = quantity * unit_price;
    }
    
    // Calculate tax for this line
    var tax_rate = parseFloat(tr.find('select.product_tax_id').find(':selected').data('tax_amount')) || 0;
    var tax_type = tr.find('select.product_tax_id').find(':selected').data('tax_type') || 'percentage';
    var line_tax = __calculate_amount(tax_type, tax_rate, subtotal_before_tax);
    
    tr.find('input.product_item_tax').val(line_tax);
    
    var row_total = subtotal_before_tax + line_tax;
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_adjustment_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/stock-adjustments/' + rowData.DT_RowId,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}
