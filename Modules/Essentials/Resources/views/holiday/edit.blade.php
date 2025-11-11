<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'update'], [$holiday->id]), 'method' => 'put', 'id' => 'add_holiday_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'essentials::lang.edit_holiday' )</h4>
    </div>

    <div class="modal-body">
    	<div class="row">
    		<div class="form-group col-md-12">
	        	{!! Form::label('type', __( 'essentials::lang.holiday_type' ) . ':*') !!}
	          	{!! Form::select('type', ['normal' => __('essentials::lang.normal'), 'consecutive' => __('essentials::lang.consecutive_holidays')], $holiday->type ?? 'normal', ['class' => 'form-control select2', 'id' => 'holiday_type', 'required']); !!}
	      	</div>

    		<div class="form-group col-md-12">
	        	{!! Form::label('name', __( 'lang_v1.name' ) . ':*') !!}
	          	{!! Form::text('name', $holiday->name, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.name' ), 'id' => 'holiday_name', 'required']); !!}
	      	</div>

	      	<div class="form-group col-md-6" id="normal_start_date_field" @if($holiday->type == 'consecutive') style="display: none;" @endif>
	        	{!! Form::label('start_date', __( 'essentials::lang.start_date' ) . ':*') !!}
	        	<div class="input-group data">
	        		{!! Form::text('start_date', @format_date($holiday->start_date), ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.start_date' ), 'readonly', 'id' => 'holiday_start_date']); !!}
	        		<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
	        	</div>
	      	</div>

	      	<div class="form-group col-md-6" id="normal_end_date_field" @if($holiday->type == 'consecutive') style="display: none;" @endif>
	        	{!! Form::label('end_date', __( 'essentials::lang.end_date' ) . ':*') !!}
		        	<div class="input-group data">
		          	{!! Form::text('end_date', @format_date($holiday->end_date), ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.end_date' ), 'readonly', 'id' => 'holiday_end_date']); !!}
		          	<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
	        	</div>
	      	</div>

	      	{{-- Consecutive holidays fields --}}
	      	<div id="consecutive_holiday_fields" @if($holiday->type != 'consecutive') style="display: none;" @endif>
	      		<div class="form-group col-md-12">
		        	{!! Form::label('user_id', __( 'essentials::lang.employee' ) . ':*') !!}
		          	{!! Form::select('user_id', $users, $holiday->user_id, ['class' => 'form-control select2', 'placeholder' => __( 'messages.please_select' ), 'id' => 'consecutive_user_id', 'style' => 'width: 100%;']); !!}
		      	</div>

		      	<div class="form-group col-md-12">
		        	{!! Form::label('repeat_type', __( 'essentials::lang.repeat_type' ) . ':*') !!}
		          	{!! Form::select('repeat_type', ['week' => __('essentials::lang.week'), 'month' => __('essentials::lang.month'), 'custom' => __('essentials::lang.custom_dates')], $holiday->repeat_type, ['class' => 'form-control select2', 'id' => 'repeat_type', 'required', 'style' => 'width: 100%;']); !!}
		      	</div>

		      	<div class="form-group col-md-12" id="weekdays_field" @if($holiday->repeat_type != 'week') style="display: none;" @endif>
		        	{!! Form::label('weekdays', __( 'essentials::lang.weekdays' ) . ':*') !!}
		          	{!! Form::select('weekdays[]', [
		          		0 => __('lang_v1.sunday'),
		          		1 => __('lang_v1.monday'),
		          		2 => __('lang_v1.tuesday'),
		          		3 => __('lang_v1.wednesday'),
		          		4 => __('lang_v1.thursday'),
		          		5 => __('lang_v1.friday'),
		          		6 => __('lang_v1.saturday')
		          	], $holiday->weekdays_array ?? [], ['class' => 'form-control select2', 'multiple', 'id' => 'weekdays_select', 'style' => 'width: 100%;']); !!}
		      	</div>

		      	<div class="form-group col-md-12" id="repeat_pattern_field" @if($holiday->repeat_type != 'week') style="display: none;" @endif>
		        	{!! Form::label('repeat_pattern', __( 'essentials::lang.repeat_pattern' ) . ':') !!}
		          	{!! Form::select('repeat_pattern', ['every' => __('essentials::lang.every_week'), 'alternate' => __('essentials::lang.alternate_week'), 'gap' => __('essentials::lang.gap_weeks')], $holiday->repeat_pattern ?? 'every', ['class' => 'form-control select2', 'id' => 'repeat_pattern', 'style' => 'width: 100%;']); !!}
		      	</div>

		      	<div class="form-group col-md-12" id="gap_weeks_field" @if($holiday->repeat_pattern != 'gap') style="display: none;" @endif>
		        	{!! Form::label('gap_weeks', __( 'essentials::lang.gap_after_weeks' ) . ':') !!}
		          	{!! Form::number('gap_weeks', $holiday->gap_weeks ?? 1, ['class' => 'form-control', 'id' => 'gap_weeks_input', 'min' => 1, 'placeholder' => __('essentials::lang.gap_weeks_placeholder')]); !!}
		          	<small class="help-block">@lang('essentials::lang.gap_weeks_help')</small>
		      	</div>

		      	<div class="form-group col-md-12" id="repeat_days_field" @if($holiday->repeat_type != 'month') style="display: none;" @endif>
		        	{!! Form::label('repeat_days', __( 'essentials::lang.month_days' ) . ':*') !!}
		          	{!! Form::text('repeat_days', $holiday->repeat_days, ['class' => 'form-control', 'placeholder' => __('essentials::lang.month_days_placeholder'), 'id' => 'repeat_days_input']); !!}
		          	<small class="help-block">@lang('essentials::lang.month_days_help')</small>
		      	</div>

		      	<div class="form-group col-md-12" id="custom_dates_field" @if($holiday->repeat_type != 'custom') style="display: none;" @endif>
		        	{!! Form::label('custom_dates', __( 'essentials::lang.custom_dates' ) . ':*') !!}
		          	@php
		          		$custom_dates_value = '';
		          		if (!empty($holiday->custom_dates)) {
		          			$dates_array = json_decode($holiday->custom_dates, true);
		          			if (is_array($dates_array)) {
		          				$custom_dates_value = implode(', ', $dates_array);
		          			}
		          		}
		          	@endphp
		          	{!! Form::text('custom_dates', $custom_dates_value, ['class' => 'form-control', 'placeholder' => __('essentials::lang.custom_dates_placeholder'), 'id' => 'custom_dates_input']); !!}
		          	<small class="help-block">@lang('essentials::lang.custom_dates_help')</small>
		      	</div>
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('location_id', __( 'business.business_location' ) . ':') !!}
	          	{!! Form::select('location_id', $locations, $holiday->location_id, ['class' => 'form-control select2', 'placeholder' => __( 'lang_v1.all' ) ]); !!}
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('note', __( 'brand.note' ) . ':') !!}
	          	{!! Form::textarea('note', $holiday->note, ['class' => 'form-control', 'placeholder' => __( 'brand.note' ), 'rows' => 3 ]); !!}
	      	</div>
    	</div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.update' )</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->