<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\LoanController::class, 'store']), 'method' => 'post', 'id' => 'add_loan_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'essentials::lang.add_loan' )</h4>
    </div>

    <div class="modal-body">
    	<div class="row">
    		<div class="form-group col-md-12">
	        	{!! Form::label('loan_amount', __( 'essentials::lang.loan_amount' ) . ':*') !!}
	          	{!! Form::text('loan_amount', null, ['class' => 'form-control input_number', 'placeholder' => __( 'essentials::lang.loan_amount' ), 'required' ]); !!}
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('reason', __( 'essentials::lang.reason' ) . ':') !!}
	          	{!! Form::textarea('reason', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.reason' ), 'rows' => 4, 'required' ]); !!}
	      	</div>
    	</div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ladda-button add-loan-btn" data-style="expand-right">
      	<span class="ladda-label">@lang( 'messages.save' )</span>
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

