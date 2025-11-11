<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action([\App\Http\Controllers\BusinessLocationController::class, 'update'], [$location->id]), 'method' => 'PUT', 'id' => 'business_location_add_form' ]) !!}

        {!! Form::hidden('hidden_id', $location->id, ['id' => 'hidden_id']); !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'business.edit_business_location' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('name', __( 'invoice.name' ) . ':*') !!}
                        {!! Form::text('name', $location->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'invoice.name' ) ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __( 'lang_v1.location_id' ) . ':') !!}
                        {!! Form::text('location_id', $location->location_id, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.location_id' ) ]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('city', __( 'business.city' ) . ':*') !!}
                        {!! Form::text('city', $location->city, ['class' => 'form-control', 'placeholder' => __( 'business.city'), 'required' ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('zip_code', __( 'business.zip_code' ) . ':*') !!}
                        {!! Form::text('zip_code', $location->zip_code, ['class' => 'form-control', 'placeholder' => __( 'business.zip_code'), 'required' ]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('state', __( 'business.state' ) . ':*') !!}
                        {!! Form::text('state', $location->state, ['class' => 'form-control', 'placeholder' => __( 'business.state'), 'required' ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('country', __( 'business.country' ) . ':*') !!}
                        {!! Form::text('country', $location->country, ['class' => 'form-control', 'placeholder' => __( 'business.country'), 'required' ]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('mobile', __( 'business.mobile' ) . ':') !!}
                        {!! Form::text('mobile', $location->mobile, ['class' => 'form-control', 'placeholder' => __( 'business.mobile')]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('email', __( 'business.email' ) . ':') !!}
                        {!! Form::email('email', $location->email, ['class' => 'form-control', 'placeholder' => __( 'business.email')]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme_for_pos') . ':*') !!}
                        {!! Form::select('invoice_scheme_id', $invoice_schemes, $location->invoice_scheme_id ?? $default_scheme_id, ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
                <div class="col-sm-6" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('sale_invoice_scheme_id', __('invoice.invoice_scheme_for_sale') . ':*') !!}
                        {!! Form::select('sale_invoice_scheme_id', $invoice_schemes, $location->sale_invoice_scheme_id ?? $default_scheme_id, ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
                <div class="col-sm-6" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('invoice_layout_id', __('lang_v1.invoice_layout_for_pos') . ':*') !!}
                        {!! Form::select('invoice_layout_id', $invoice_layouts, $location->invoice_layout_id ?? $default_layout_id, ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
                <div class="col-sm-6" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('sale_invoice_layout_id', __('lang_v1.invoice_layout_for_sale') . ':*') !!}
                        {!! Form::select('sale_invoice_layout_id', $invoice_layouts, $location->sale_invoice_layout_id ?? $default_layout_id, ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
