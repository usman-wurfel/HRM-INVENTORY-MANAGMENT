@extends('layouts.app')

@php
	if (!empty($status) && $status == 'quotation') {
		$title = __('lang_v1.add_quotation');
	} else if (!empty($status) && $status == 'draft') {
		$title = __('lang_v1.add_draft');
	} else {
		$title = __('sale.add_sale');
	}

	if($sale_type == 'sales_order') {
		$title = __('lang_v1.sales_order');
	}
@endphp

@section('title', $title)

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{$title}}</h1>
</section>
<!-- Main content -->
<section class="content no-print">
<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
@if(!empty($pos_settings['allow_overselling']))
	<input type="hidden" id="is_overselling_allowed">
@endif
@if(session('business.enable_rp') == 1)
    <input type="hidden" id="reward_point_enabled">
@endif
@if(count($business_locations) > 0)
<div class="row">
	<div class="col-sm-3">
		<div class="form-group">
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fa fa-map-marker"></i>
				</span>
			{!! Form::select('select_location_id', $business_locations, $default_location->id ?? null, ['class' => 'form-control input-sm',
			'id' => 'select_location_id', 
			'required', 'autofocus'], $bl_attributes); !!}
			<span class="input-group-addon">
					@show_tooltip(__('tooltip.sale_location'))
				</span> 
			</div>
		</div>
	</div>
</div>
@endif

@php
	$custom_labels = json_decode(session('business.custom_labels'), true);
	$common_settings = session()->get('business.common_settings');
@endphp
<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
	{!! Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'store']), 'method' => 'post', 'id' => 'add_sell_form', 'files' => true ]) !!}
	 @if(!empty($sale_type))
	 	<input type="hidden" id="sale_type" name="type" value="{{$sale_type}}">
	 @endif
	<div class="row">
		<div class="col-md-12 col-sm-12">
			@component('components.widget', ['class' => 'box-solid'])
				{!! Form::hidden('location_id', !empty($default_location) ? $default_location->id : null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => !empty($default_location) ? $default_location->default_payment_accounts : '']); !!}

				@if(!empty($price_groups))
					@if(count($price_groups) > 1)
						<div class="col-sm-4">
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									@php
										reset($price_groups);
										$selected_price_group = !empty($default_price_group_id) && array_key_exists($default_price_group_id, $price_groups) ? $default_price_group_id : null;
									@endphp
									{!! Form::hidden('hidden_price_group', key($price_groups), ['id' => 'hidden_price_group']) !!}
									{!! Form::select('price_group', $price_groups, $selected_price_group, ['class' => 'form-control select2', 'id' => 'price_group']); !!}
									<span class="input-group-addon">
										@show_tooltip(__('lang_v1.price_group_help_text'))
									</span> 
								</div>
							</div>
						</div>
						
					@else
						@php
							reset($price_groups);
						@endphp
						{!! Form::hidden('price_group', key($price_groups), ['id' => 'price_group']) !!}
					@endif
				@endif

				{!! Form::hidden('default_price_group', null, ['id' => 'default_price_group']) !!}

				@if(in_array('types_of_service', $enabled_modules) && !empty($types_of_service))
					<div class="col-md-4 col-sm-6">
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-external-link-square-alt text-primary service_modal_btn"></i>
								</span>
								{!! Form::select('types_of_service_id', $types_of_service, null, ['class' => 'form-control', 'id' => 'types_of_service_id', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.select_types_of_service')]); !!}

								{!! Form::hidden('types_of_service_price_group', null, ['id' => 'types_of_service_price_group']) !!}

								<span class="input-group-addon">
									@show_tooltip(__('lang_v1.types_of_service_help'))
								</span> 
							</div>
							<small><p class="help-block hide" id="price_group_text">@lang('lang_v1.price_group'): <span></span></p></small>
						</div>
					</div>
				@endif
				
				@if(in_array('subscription', $enabled_modules))
					<div class="col-md-4 pull-right col-sm-6">
						<div class="checkbox">
							<label>
				              {!! Form::checkbox('is_recurring', 1, false, ['class' => 'input-icheck', 'id' => 'is_recurring']); !!} @lang('lang_v1.subscribe')?
				            </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-link"><i class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
						</div>
					</div>
				@endif
				<div class="clearfix"></div>
				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('contact_id', __('contact.customer') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-user"></i>
							</span>
							<input type="hidden" id="default_customer_id" 
							value="{{ $walk_in_customer['id']}}" >
							<input type="hidden" id="default_customer_name" 
							value="{{ $walk_in_customer['name']}}" >
							<input type="hidden" id="default_customer_balance" value="{{ $walk_in_customer['balance'] ?? ''}}" >
							<input type="hidden" id="default_customer_address" value="{{ $walk_in_customer['shipping_address'] ?? ''}}" >
							@if(!empty($walk_in_customer['price_calculation_type']) && $walk_in_customer['price_calculation_type'] == 'selling_price_group')
								<input type="hidden" id="default_selling_price_group" 
							value="{{ $walk_in_customer['selling_price_group_id'] ?? ''}}" >
							@endif
							{!! Form::select('contact_id', 
								[], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required']); !!}
							<span class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
							</span>
						</div>
						<small class="text-danger hide contact_due_text"><strong>@lang('account.customer_due'):</strong> <span></span></small>
					</div>
					<small>
					<strong>
						@lang('lang_v1.billing_address'):
					</strong>
					<div id="billing_address_div">
						{!! $walk_in_customer['contact_address'] ?? '' !!}
					</div>
					<br>
					<strong>
						@lang('lang_v1.shipping_address'):
					</strong>
					<div id="shipping_address_div">
						{{$walk_in_customer['supplier_business_name'] ?? ''}},<br>
						{{$walk_in_customer['name'] ?? ''}},<br>
						{{$walk_in_customer['shipping_address'] ?? ''}}
					</div>					
					</small>
				</div>

				<div class="col-md-3">
		          <div class="form-group">
		            <div class="multi-input">
		            @php
						$is_pay_term_required = !empty($pos_settings['is_pay_term_required']);
					@endphp
		              {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
		              <br/>
		              {!! Form::number('pay_term_number', $walk_in_customer['pay_term_number'], ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term'), 'required' => $is_pay_term_required]); !!}

		              {!! Form::select('pay_term_type', 
		              	['months' => __('lang_v1.months'), 
		              		'days' => __('lang_v1.days')], 
		              		$walk_in_customer['pay_term_type'], 
		              	['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select'), 'required' => $is_pay_term_required]); !!}
		            </div>
		          </div>
		        </div>

				@if(!empty($commission_agent))
				@php
					$is_commission_agent_required = !empty($pos_settings['is_commission_agent_required']);
				@endphp
				<div class="col-sm-3">
					<div class="form-group">
					{!! Form::label('commission_agent', __('lang_v1.commission_agent') . ':') !!}
					{!! Form::select('commission_agent', 
								$commission_agent, null, ['class' => 'form-control select2', 'id' => 'commission_agent', 'required' => $is_commission_agent_required]); !!}
					</div>
				</div>
				@endif
				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', $default_datetime, ['class' => 'form-control', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
				@if(!empty($status))
					<input type="hidden" name="status" id="status" value="{{$status}}">

					@if(in_array($status, ['draft', 'quotation']))
						<input type="hidden" id="disable_qty_alert">
					@endif
				@else
					<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
						<div class="form-group">
							{!! Form::label('status', __('sale.status') . ':*') !!}
							{!! Form::select('status', $statuses, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
						</div>
					</div>
				@endif
				@if($sale_type != 'sales_order')
					<div class="col-sm-3 hide">
						<div class="form-group">
							{!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':') !!}
							{!! Form::select('invoice_scheme_id', $invoice_schemes, $default_invoice_schemes->id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
						</div>
					</div>
				@endif
					@can('edit_invoice_number')
					<div class="col-sm-3 hide">
						<div class="form-group">
							{!! Form::label('invoice_no', $sale_type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no') . ':') !!}
							{!! Form::text('invoice_no', null, ['class' => 'form-control', 'placeholder' => $sale_type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no')]); !!}
							<p class="help-block">@lang('lang_v1.keep_blank_to_autogenerate')</p>
						</div>
					</div>
					@endcan
				
				@php
			        $custom_field_1_label = !empty($custom_labels['sell']['custom_field_1']) ? $custom_labels['sell']['custom_field_1'] : '';

			        $is_custom_field_1_required = !empty($custom_labels['sell']['is_custom_field_1_required']) && $custom_labels['sell']['is_custom_field_1_required'] == 1 ? true : false;

			        $custom_field_2_label = !empty($custom_labels['sell']['custom_field_2']) ? $custom_labels['sell']['custom_field_2'] : '';

			        $is_custom_field_2_required = !empty($custom_labels['sell']['is_custom_field_2_required']) && $custom_labels['sell']['is_custom_field_2_required'] == 1 ? true : false;

			        $custom_field_3_label = !empty($custom_labels['sell']['custom_field_3']) ? $custom_labels['sell']['custom_field_3'] : '';

			        $is_custom_field_3_required = !empty($custom_labels['sell']['is_custom_field_3_required']) && $custom_labels['sell']['is_custom_field_3_required'] == 1 ? true : false;

			        $custom_field_4_label = !empty($custom_labels['sell']['custom_field_4']) ? $custom_labels['sell']['custom_field_4'] : '';

			        $is_custom_field_4_required = !empty($custom_labels['sell']['is_custom_field_4_required']) && $custom_labels['sell']['is_custom_field_4_required'] == 1 ? true : false;
		        @endphp
		        @if(!empty($custom_field_1_label))
		        	@php
		        		$label_1 = $custom_field_1_label . ':';
		        		if($is_custom_field_1_required) {
		        			$label_1 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_1', $label_1 ) !!}
				            {!! Form::text('custom_field_1', null, ['class' => 'form-control','placeholder' => $custom_field_1_label, 'required' => $is_custom_field_1_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_2_label))
		        	@php
		        		$label_2 = $custom_field_2_label . ':';
		        		if($is_custom_field_2_required) {
		        			$label_2 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_2', $label_2 ) !!}
				            {!! Form::text('custom_field_2', null, ['class' => 'form-control','placeholder' => $custom_field_2_label, 'required' => $is_custom_field_2_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_3_label))
		        	@php
		        		$label_3 = $custom_field_3_label . ':';
		        		if($is_custom_field_3_required) {
		        			$label_3 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_3', $label_3 ) !!}
				            {!! Form::text('custom_field_3', null, ['class' => 'form-control','placeholder' => $custom_field_3_label, 'required' => $is_custom_field_3_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_4_label))
		        	@php
		        		$label_4 = $custom_field_4_label . ':';
		        		if($is_custom_field_4_required) {
		        			$label_4 .= '*';
		        		}
		        	@endphp

		        	<div class="col-md-4">
				        <div class="form-group">
				            {!! Form::label('custom_field_4', $label_4 ) !!}
				            {!! Form::text('custom_field_4', null, ['class' => 'form-control','placeholder' => $custom_field_4_label, 'required' => $is_custom_field_4_required]); !!}
				        </div>
				    </div>
		        @endif
		        <div class="col-sm-3">
	                <div class="form-group">
	                    {!! Form::label('upload_document', __('purchase.attach_document') . ':') !!}
	                    {!! Form::file('sell_document[]', ['id' => 'upload_document', 'multiple', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
	                    <p class="help-block">
	                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
	                    	@includeIf('components.document_help_text')
	                    	<br><small>@lang('lang_v1.you_can_upload_multiple_files')</small>
	                    </p>
	                </div>
	            </div>
		        <div class="clearfix"></div>

		        @if((!empty($pos_settings['enable_sales_order']) && $sale_type != 'sales_order') || $is_order_request_enabled)
					<div class="col-sm-3">
						<div class="form-group">
							{!! Form::label('sales_order_ids', __('lang_v1.sales_order').':') !!}
							{!! Form::select('sales_order_ids[]', [], null, ['class' => 'form-control select2', 'multiple', 'id' => 'sales_order_ids']); !!}
						</div>
					</div>
					<div class="clearfix"></div>
				@endif
				<!-- Call restaurant module if defined -->
		        @if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
		        	<span id="restaurant_module_span">
		        	</span>
		        @endif
			@endcomponent

			@php
				$default_sales_tax = $business_details->default_sales_tax;

				if($sale_type == 'sales_order') {
					$default_sales_tax = null;
				}
			@endphp
			<input type="hidden" name="is_direct_sale" value="1">
			<input type="hidden" name="is_serial_no" value="1">
		</div>
	</div>
	@if(!empty($common_settings['is_enabled_export']) && $sale_type != 'sales_order')
		@component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.export')])
			<div class="col-md-12 mb-12">
                <div class="form-check">
                    <input type="checkbox" name="is_export" class="form-check-input" id="is_export" @if(!empty($walk_in_customer['is_export'])) checked @endif>
                    <label class="form-check-label" for="is_export">@lang('lang_v1.is_export')</label>
                </div>
            </div>
	        @php
	            $i = 1;
	        @endphp
	        @for($i; $i <= 6 ; $i++)
	            <div class="col-md-4 export_div" @if(empty($walk_in_customer['is_export'])) style="display: none;" @endif>
	                <div class="form-group">
	                    {!! Form::label('export_custom_field_'.$i, __('lang_v1.export_custom_field'.$i).':') !!}
	                    {!! Form::text('export_custom_fields_info['.'export_custom_field_'.$i.']', !empty($walk_in_customer['export_custom_field_'.$i]) ? $walk_in_customer['export_custom_field_'.$i] : null, ['class' => 'form-control','placeholder' => __('lang_v1.export_custom_field'.$i), 'id' => 'export_custom_field_'.$i]); !!}
	                </div>
	            </div>
	        @endfor
		@endcomponent
	@endif
	@php
		$is_enabled_download_pdf = config('constants.enable_download_pdf');
		$payment_body_id = 'payment_rows_div';
		if ($is_enabled_download_pdf) {
			$payment_body_id = '';
		}
	@endphp
	@if((empty($status) || (!in_array($status, ['quotation', 'draft'])) || $is_enabled_download_pdf) && $sale_type != 'sales_order')
		@can('sell.payments')
			@component('components.widget', ['class' => 'box-solid', 'id' => $payment_body_id, 'title' => __('purchase.add_payment')])
			@if($is_enabled_download_pdf)
				<div class="well row">
					<div class="col-md-6">
						<div class="form-group">
							{!! Form::label("prefer_payment_method" , __('lang_v1.prefer_payment_method') . ':') !!}
							@show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fas fa-money-bill-alt"></i>
								</span>
								{!! Form::select("prefer_payment_method", $payment_types, 'cash', ['class' => 'form-control','style' => 'width:100%;']); !!}
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							{!! Form::label("prefer_payment_account" , __('lang_v1.prefer_payment_account') . ':') !!}
							@show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fas fa-money-bill-alt"></i>
								</span>
								{!! Form::select("prefer_payment_account", $accounts, null, ['class' => 'form-control','style' => 'width:100%;']); !!}
							</div>
						</div>
					</div>
				</div>
			@endif
			@if(empty($status) || !in_array($status, ['quotation', 'draft']))
				<div class="payment_row" @if($is_enabled_download_pdf) id="payment_rows_div" @endif>
					<div class="row hide">
						<div class="col-md-12 mb-12">
							<strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text"></span>
							{!! Form::hidden('advance_balance', null, ['id' => 'advance_balance', 'data-error-msg' => __('lang_v1.required_advance_balance_not_available')]); !!}
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								{!! Form::label('payment_methods', __('lang_v1.payment_method') . ':*') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									{!! Form::select('payment_methods[]', $payment_types, null, ['class' => 'form-control select2', 'id' => 'payment_methods', 'multiple', 'style' => 'width:100%;', 'required']); !!}
								</div>
								<p class="help-block">@lang('lang_v1.select_multiple_payment_methods')</p>
							</div>
						</div>
					</div>
					<div id="payment_amount_fields" class="row">
						<!-- Amount fields will be dynamically added here -->
					</div>
                </div>
                <div class="payment_row">
					<div class="row">
						<div class="col-md-12">
			        		<hr>
			        		<strong>
			        			@lang('sale.amount'):
			        		</strong>
			        		<br/>
			        		<span class="lead text-bold change_return_span">0</span>
			        		{!! Form::hidden("change_return", $change_return['amount'], ['class' => 'form-control change_return input_number', 'required', 'id' => "change_return"]); !!}
			        		<!-- <span class="lead text-bold total_quantity">0</span> -->
			        		@if(!empty($change_return['id']))
			            		<input type="hidden" name="change_return_id" 
			            		value="{{$change_return['id']}}">
			            	@endif
						</div>
					</div>
					<div class="row hide payment_row" id="change_return_payment_data">
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label("change_return_method" , __('lang_v1.change_return_payment_method') . ':*') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									@php
										$_payment_method = empty($change_return['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $change_return['method'];

										$_payment_types = $payment_types;
										if(isset($_payment_types['advance'])) {
											unset($_payment_types['advance']);
										}
									@endphp
									{!! Form::select("payment[change_return][method]", $_payment_types, $_payment_method, ['class' => 'form-control col-md-12 payment_types_dropdown', 'id' => 'change_return_method', 'style' => 'width:100%;']); !!}
								</div>
							</div>
						</div>
						@if(!empty($accounts))
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label("change_return_account" , __('lang_v1.change_return_payment_account') . ':') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									{!! Form::select("payment[change_return][account_id]", $accounts, !empty($change_return['account_id']) ? $change_return['account_id'] : '' , ['class' => 'form-control select2', 'id' => 'change_return_account', 'style' => 'width:100%;']); !!}
								</div>
							</div>
						</div>
						@endif
						@include('sale_pos.partials.payment_type_details', ['payment_line' => $change_return, 'row_index' => 'change_return'])
					</div>
					<hr>
					<div class="row hide">
						<div class="col-sm-12">
							<div class="pull-right"><strong>@lang('lang_v1.balance'):</strong> <span class="balance_due">0.00</span></div>
						</div>
					</div>
				</div>
			@endif
			@endcomponent
		@endcan
	@endif
	
	@component('components.widget', ['class' => 'box-solid'])
		<div class="col-md-12">
			<div class="form-group">
				{!! Form::label('sell_note',__('sale.sell_note')) !!}
				{!! Form::textarea('sale_note', null, ['class' => 'form-control', 'rows' => 3]); !!}
			</div>
		</div>
	@endcomponent
	
	<div class="row">
		{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
		<div class="col-sm-12 text-center tw-mt-4">
			<button type="button" id="submit-sell" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
		</div>
	</div>
	
	@if(empty($pos_settings['disable_recurring_invoice']))
		@include('sale_pos.partials.recurring_invoice_modal')
	@endif
	
	{!! Form::close() !!}
</section>

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>

<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>


@include('sale_pos.partials.configure_search_modal')

@stop

@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <script type="text/javascript">
    	$(document).ready( function() {
    		$('#status').change(function(){
    			if ($(this).val() == 'final') {
    				$('#payment_rows_div').removeClass('hide');
    			} else {
    				$('#payment_rows_div').addClass('hide');
    			}
    		});
    		$('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });


		    $(document).on('change', '#prefer_payment_method', function(e) {
			    var default_accounts = $('select#select_location_id').length ? 
			                $('select#select_location_id')
			                .find(':selected')
			                .data('default_payment_accounts') : $('#location_id').data('default_payment_accounts');
			    var payment_type = $(this).val();
			    if (payment_type) {
			        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
			            default_accounts[payment_type]['account'] : '';
			        var account_dropdown = $('select#prefer_payment_account');
			        if (account_dropdown.length && default_accounts) {
			            account_dropdown.val(default_account);
			            account_dropdown.change();
			        }
			    }
			});

		    function setPreferredPaymentMethodDropdown() {
			    var payment_settings = $('#location_id').data('default_payment_accounts');
			    payment_settings = payment_settings ? payment_settings : [];
			    enabled_payment_types = [];
			    for (var key in payment_settings) {
			        if (payment_settings[key] && payment_settings[key]['is_enabled']) {
			            enabled_payment_types.push(key);
			        }
			    }
			    if (enabled_payment_types.length) {
			        $("#prefer_payment_method > option").each(function() {
		                if (enabled_payment_types.indexOf($(this).val()) != -1) {
		                    $(this).removeClass('hide');
		                } else {
		                    $(this).addClass('hide');
		                }
			        });
			    }
			}
			
			setPreferredPaymentMethodDropdown();

			$('#is_export').on('change', function () {
	            if ($(this).is(':checked')) {
	                $('div.export_div').show();
	            } else {
	                $('div.export_div').hide();
	            }
	        });

			if($('.payment_types_dropdown').length){
				$('.payment_types_dropdown').change();
			}

			// Handle multiple payment methods selection
			$('#payment_methods').on('change', function() {
				var selectedMethods = $(this).val() || [];
				var paymentTypes = @json($payment_types);
				var container = $('#payment_amount_fields');
				container.empty();

				var currentDate = moment().format(moment_date_format + ' ' + moment_time_format);
				
				selectedMethods.forEach(function(method, index) {
					var methodLabel = paymentTypes[method] || method;
					var fieldHtml = '<div class="col-md-6 payment-method-field" data-method="' + method + '">' +
						'<div class="form-group">' +
						'<label for="payment_amount_' + method + '">' + methodLabel + ' @lang("sale.amount"):*</label>' +
						'<div class="input-group">' +
						'<span class="input-group-addon"><i class="fas fa-money-bill-alt"></i></span>' +
						'<input type="text" name="payment[' + index + '][method]" value="' + method + '" class="form-control" style="display:none;">' +
						'<input type="text" name="payment[' + index + '][amount]" id="payment_amount_' + method + '" class="form-control input_number payment-amount" placeholder="@lang("sale.amount")" required>' +
						'<input type="hidden" name="payment[' + index + '][paid_on]" value="' + currentDate + '">' +
						'</div>' +
						'</div>' +
						'</div>';
					container.append(fieldHtml);
				});

				// Initialize input_number class for new fields
				if (typeof initialize_input_number !== 'undefined') {
					initialize_input_number();
				}
			});

			// Keep change return payment method section permanently hidden
			$('#change_return_payment_data').addClass('hide').hide();
			
			// Use MutationObserver to keep it hidden
			if (typeof MutationObserver !== 'undefined') {
				var observer = new MutationObserver(function(mutations) {
					$('#change_return_payment_data').addClass('hide').hide();
				});
				
				var targetNode = document.getElementById('payment_rows_div') || document.body;
				if (targetNode) {
					observer.observe(targetNode, {
						attributes: true,
						childList: true,
						subtree: true,
						attributeFilter: ['class', 'style']
					});
				}
			}

			// Override any functions that might show this section
			$(document).on('change blur keyup input', '.payment-amount, .change_return, input[type="text"]', function() {
				$('#change_return_payment_data').addClass('hide').hide();
			});

			// Override the calculate_balance_due function if it exists
			if (typeof calculate_balance_due === 'function') {
				var originalCalculateBalanceDue = calculate_balance_due;
				calculate_balance_due = function() {
					var result = originalCalculateBalanceDue.apply(this, arguments);
					$('#change_return_payment_data').addClass('hide').hide();
					return result;
				};
			}

    	});
    </script>
@endsection
