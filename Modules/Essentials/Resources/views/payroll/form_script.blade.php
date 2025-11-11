<script type="text/javascript">
$(document).ready( function () {

	//add allowance row
	$('.add_allowance').click( function() {
        let id = $(this).parent().parent().parent().parent().data('id');
        $this = $(this);
        $.ajax({
            method: "GET",
            dataType: "html",
            data:{
                'employee_id': id,
                'type': 'allowance'
            },
            url: '/hrm/get-allowance-deduction-row',
            success: function(result){
                $this.closest('.allowance_table tbody').append(result);
            }
        });
    });

	//add deduction row
    $('.add_deduction').click( function() {
        let id = $(this).parent().parent().parent().parent().data('id');
        $this = $(this);
        $.ajax({
            method: "GET",
            dataType: "html",
            data:{
                'employee_id': id,
                'type': 'deduction'
            },
            url: '/hrm/get-allowance-deduction-row',
            success: function(result){
                $this.closest('.deductions_table tbody').append(result);
            }
        });
    });

    //remove allowance/deduction row
    $(document).on('click', 'button.remove_tr', function(){
        let tr = $(this).closest('tr');
        // Don't allow removing readonly loan deduction rows
        if (tr.hasClass('loan-deduction-row')) {
            toastr.error('@lang("essentials::lang.loan_deduction_cannot_be_removed")');
            return false;
        }
        let id = $(this).parent().parent().parent().parent().data('id');
        tr.remove();
        calculateTotal(id);
        calculateTotalGrossAmount();
    });

    //toggle allowance/deduction amount type
    $(document).on('change', '.amount_type', function(){
	    let tr = $(this).closest('tr');
	    if ($(this).val() == 'percent') {
	        tr.find('.percent_field').removeClass('hide');
	        tr.find('.value_field').attr('readonly', true);
	    } else {
	        tr.find('.percent_field').addClass('hide');
	        tr.find('.value_field').removeAttr('readonly');
	    }
	});

    //calculate amount per unit duration
	$(document).on('change', '.total', function() {
        let total_duration = __read_number($(this).closest('td').find('input.essentials_duration'));
        let total = __read_number($(this));
        let amount_per_unit_duration = total / total_duration;
        __write_number($(this).closest('td').find('input.essentials_amount_per_unit_duration'), amount_per_unit_duration, false, 2);
        calculateTotal($(this).data('id'));
        calculateTotalGrossAmount();
    });

    $(document).on('change', '.essentials_duration, .essentials_amount_per_unit_duration, input.allowance, input.deduction, input.percent', function() {
        let id = $(this).data('id');
        if ($(this).hasClass('allowance') || $(this).hasClass('deduction')) {
            id = $(this).parent().parent().parent().parent().data('id');
        } else if ($(this).hasClass('percent')) {
            console.log();
            id = $(this).parent().parent().parent().parent().parent().data('id');
        }
        calculateTotal(id);
        calculateTotalGrossAmount();
    });

    function calculateTotal (id) {
        //calculate basic salary
    	let total_duration = __read_number($("input#essentials_duration_"+id));
	    let amount_per_unit_duration = __read_number($("input#essentials_amount_per_unit_duration_"+id));
	    let total = total_duration * amount_per_unit_duration;
	    __write_number($("input#total_"+id), total, false, 2);

        //calculate total allownace
        let total_allowance = 0;
        $("table#allowance_table_"+id).find('tbody tr').each(function () {
            let type = $(this).find('.amount_type').val();
            if (type == 'percent') {
                let percent = __read_number($(this).find('.percent'));
                let row_total = __calculate_amount('percentage', percent, total);
                __write_number($(this).find('input.allowance'), row_total);
            }
            total_allowance += __read_number($(this).find('input.allowance'));
        });
        $('#total_allowances_'+id).text(__currency_trans_from_en(total_allowance, true));

        //calculate total deduction
        let total_deduction = 0;
        $('table#deductions_table_'+id).find('tbody tr').each( function(){
            let type = $(this).find('.amount_type').val();
            if (type == 'percent') {
                let percent = __read_number($(this).find('.percent'));
                let row_total = __calculate_amount('percentage', percent, total);
                __write_number($(this).find('input.deduction'), row_total);
            }
            total_deduction += __read_number($(this).find('input.deduction'));
        });
        $('#total_deductions_'+id).text(__currency_trans_from_en(total_deduction, true));

        //calculate gross amount
        var gross_amount = total + total_allowance - total_deduction;
        $('#gross_amount_'+id).val(gross_amount);
        $('#gross_amount_text_'+id).text(__currency_trans_from_en(gross_amount, true));
    }

    function calculateTotalGrossAmount () {
        let total_gross_amount = 0;
        $("input.gross_amount").each(function () {
            let gross_amount = __read_number($(this));
            total_gross_amount += gross_amount;
        });
        $('#total_gross_amount').val(total_gross_amount);
    }

    $("table#payroll_table tbody tr").each(function () {
       calculateTotal($(this).data('id'));
       calculateTotalGrossAmount();
    });

    // Initialize dropzone for each employee's document upload
    var payrollDropzones = {};
    $("table#payroll_table tbody tr").each(function () {
        var employeeId = $(this).data('id');
        var dropzoneId = 'payroll_documents_dropzone_' + employeeId;
        var hiddenInputId = 'uploaded_documents_' + employeeId;
        
        if ($('#' + dropzoneId).length) {
            // Store file names for this employee
            payrollDropzones[employeeId] = {
                fileNames: [],
                hiddenInput: $('#' + hiddenInputId)
            };

            $('#' + dropzoneId).dropzone({
                url: base_path + '/post-document-upload',
                paramName: 'file',
                uploadMultiple: true,
                autoProcessQueue: true,
                addRemoveLinks: true,
                acceptedFiles: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(file, response) {
                    if (response.success) {
                        var empId = this.element.id.replace('payroll_documents_dropzone_', '');
                        // Store file name in file object for later removal
                        file.uploadedFileName = response.file_name;
                        payrollDropzones[empId].fileNames.push(response.file_name);
                        payrollDropzones[empId].hiddenInput.val(payrollDropzones[empId].fileNames.join(','));
                    } else {
                        toastr.error(response.msg);
                        this.removeFile(file);
                    }
                },
                removedfile: function(file) {
                    var empId = this.element.id.replace('payroll_documents_dropzone_', '');
                    // Get file name from file object
                    if (file.uploadedFileName) {
                        payrollDropzones[empId].fileNames = payrollDropzones[empId].fileNames.filter(function(name) {
                            return name !== file.uploadedFileName;
                        });
                        payrollDropzones[empId].hiddenInput.val(payrollDropzones[empId].fileNames.join(','));
                    }
                    var _ref;
                    if ((_ref = file.previewElement) != null) {
                        _ref.parentNode.removeChild(file.previewElement);
                    }
                    return this._updateMaxFilesReachedClass();
                },
                error: function(file, response) {
                    toastr.error('@lang("messages.something_went_wrong")');
                    this.removeFile(file);
                }
            });
        }
    });
});
</script>