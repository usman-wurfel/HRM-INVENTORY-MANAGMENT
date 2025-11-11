<div class="pos-tab-content active">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('name',__('business.business_name') . ':*') !!}
                {!! Form::text('name', $business->name, ['class' => 'form-control', 'required',
                'placeholder' => __('business.business_name')]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('currency_id', __('business.currency') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-money-bill-alt"></i>
                    </span>
                    {!! Form::select('currency_id', $currencies, $business->currency_id, ['class' => 'form-control select2','placeholder' => __('business.currency'), 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('time_zone', __('business.time_zone') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-clock"></i>
                    </span>
                    {!! Form::select('time_zone', $timezone_list, $business->time_zone, ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <!-- Hidden fields for removed settings -->
        {!! Form::hidden('start_date', $business->start_date); !!}
        {!! Form::hidden('default_profit_percent', $business->default_profit_percent); !!}
        {!! Form::hidden('currency_symbol_placement', $business->currency_symbol_placement); !!}
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('business_logo', __('business.upload_logo') . ':') !!}
                    {!! Form::file('business_logo', ['accept' => 'image/*']); !!}
                    <p class="help-block"><i> @lang('business.logo_help')</i></p>
            </div>
        </div>
        <!-- Hidden fields for removed settings -->
        {!! Form::hidden('fy_start_month', $business->fy_start_month); !!}
        {!! Form::hidden('accounting_method', $business->accounting_method); !!}
        {!! Form::hidden('transaction_edit_days', $business->transaction_edit_days); !!}
        {!! Form::hidden('date_format', $business->date_format); !!}
        {!! Form::hidden('time_format', $business->time_format); !!}
        {!! Form::hidden('currency_precision', $business->currency_precision); !!}
        {!! Form::hidden('quantity_precision', $business->quantity_precision); !!}
    </div>
     {{-- code --}}
    <div class="row hide">
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('code_label_1', __('lang_v1.code_1_name') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-info"></i>
                    </span>
                    {!! Form::text('code_label_1', $business->code_label_1, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('code_1', __('lang_v1.code_1') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-info"></i>
                    </span>
                    {!! Form::text('code_1', $business->code_1, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('code_label_2', __('lang_v1.code_2_name') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-info"></i>
                    </span>
                    {!! Form::text('code_label_2', $business->code_label_2, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('code_2', __('lang_v1.code_2') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-info"></i>
                    </span>
                    {!! Form::text('code_2', $business->code_2, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
    </div>
    <div class="row hide">
        <div class="col-sm-8">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('common_settings[is_enabled_export]', true, !empty($common_settings['is_enabled_export']) ? true : false , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.enable_export' ) }}
                </label>
            </div>
        </div>
    </div>
</div>