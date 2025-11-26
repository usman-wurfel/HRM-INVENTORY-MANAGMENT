<div class="modal-dialog modal-lg" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
	      	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      	<h4 class="modal-title">@lang( 'lang_v1.history' ) - @lang('essentials::lang.loan')</h4>
	    </div>
  		<div class="modal-body">
  			<div class="row">
  				<div class="col-md-12">
  					<h4>@lang('purchase.ref_no'): {{$loan->ref_no}}</h4>
  					<strong>@lang('essentials::lang.employee'):</strong> {{$loan->user->user_full_name ?? ($loan->user->first_name . ' ' . $loan->user->last_name)}} &nbsp; &nbsp;
  					<strong>@lang('essentials::lang.loan_amount'):</strong> <span class="display_currency" data-currency_symbol="true">{{$loan->loan_amount}}</span> &nbsp; &nbsp;
  					<strong>@lang('sale.status'):</strong> <span class="label {{$loan->status == 'pending' ? 'bg-yellow' : ($loan->status == 'approved' ? 'bg-green' : 'bg-red')}}">{{ucfirst($loan->status)}}</span>
  				</div>
  			</div>
  			<br>
  			<div class="row">
  				<div class="col-md-12">
		  			<table class="table table-condensed bg-gray table-bordered">
		                <thead>
		                    <tr>
		                        <th>@lang('lang_v1.date')</th>
		                        <th>@lang('messages.action')</th>
		                        <th>@lang('lang_v1.by')</th>
		                        <th>@lang('essentials::lang.role')</th>
		                        <th>@lang('essentials::lang.reason')</th>
		                    </tr>
		                </thead>
		                <tbody>
		                @php
		                	// Add initial loan creation entry if not in activities
		                	$has_created = false;
		                	foreach($activities as $act) {
		                		if($act->description == 'created') {
		                			$has_created = true;
		                			break;
		                		}
		                	}
		                @endphp
		                @if(!$has_created && !empty($loan->reason))
		                	<tr>
		                		<td>{{@format_datetime($loan->created_at)}}</td>
		                		<td>@lang('lang_v1.created')</td>
		                		<td>{{$loan->user->user_full_name ?? ($loan->user->first_name . ' ' . $loan->user->last_name)}}</td>
		                		<td>
		                			@if($loan->user->roles->count() > 0)
		                				{{$loan->user->roles->first()->display_name ?? $loan->user->roles->first()->name}}
		                			@else
		                				-
		                			@endif
		                		</td>
		                		<td><strong>@lang('essentials::lang.reason'):</strong> {{$loan->reason}}</td>
		                	</tr>
		                @endif
		                @forelse($activities as $activity)
		                    <tr>
		                        <td>{{@format_datetime($activity->created_at)}}</td>
		                        <td>
		                        	@if($activity->description == 'created')
		                        		@lang('lang_v1.created')
		                        	@elseif($activity->description == 'updated')
		                        		@lang('lang_v1.updated')
		                        	@else
		                        		{{ucfirst($activity->description)}}
		                        	@endif
		                        </td>
		                        <td>
		                        	@if($activity->causer)
		                        		{{$activity->causer->user_full_name ?? ($activity->causer->first_name . ' ' . $activity->causer->last_name)}}
		                        	@else
		                        		@lang('lang_v1.system')
		                        	@endif
		                        </td>
		                        <td>
		                        	@if($activity->causer && $activity->causer->roles->count() > 0)
		                        		{{$activity->causer->roles->first()->display_name ?? $activity->causer->roles->first()->name}}
		                        	@else
		                        		-
		                        	@endif
		                        </td>
		                        <td>
		                        	@if($activity->description == 'updated')
		                        		@if(!empty($activity->changes['attributes']['status_note']))
		                        			<strong>@lang('brand.note'):</strong> {{$activity->changes['attributes']['status_note']}}
		                        			<br>
		                        		@endif
		                        		@if(!empty($activity->changes['attributes']['status']))
		                        			<strong>@lang('sale.status'):</strong> {{ucfirst($activity->changes['attributes']['status'])}}
		                        			<br>
		                        		@endif
		                        		@if(!empty($activity->changes['attributes']['reason']))
		                        			<strong>@lang('essentials::lang.reason'):</strong> {{$activity->changes['attributes']['reason']}}
		                        		@endif
		                        		@if(empty($activity->changes['attributes']['status_note']) && empty($activity->changes['attributes']['status']) && empty($activity->changes['attributes']['reason']))
		                        			-
		                        		@endif
		                        	@elseif($activity->description == 'created')
		                        		@if(!empty($loan->reason))
		                        			<strong>@lang('essentials::lang.reason'):</strong> {{$loan->reason}}
		                        		@else
		                        			-
		                        		@endif
		                        	@else
		                        		-
		                        	@endif
		                        </td>
		                    </tr>
		                @empty
		                    <tr>
		                      <td colspan="5" class="text-center">
		                        @lang('purchase.no_records_found')
		                      </td>
		                    </tr>
		                @endforelse
		                </tbody>
		            </table>
		        </div>
		    </div>
  		</div>
  		<div class="modal-footer">
	      	<button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
	    </div>
  	</div>
</div>

