<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
    @component('components.widget', [
        'class' => '',
        'title' => __('essentials::lang.holidays'),
        'icon' => '<i class="fas fa-suitcase-rolling"></i>',
    ])
      <div class="">
        <table class="table no-margin">
            <tbody>
                <tr>
                    <th class="bg-light-gray" colspan="3">@lang('home.today')</th>
                </tr>
                @forelse($todays_holidays as $holiday)
                    @php
                        $start_date = \Carbon::parse($holiday->start_date);
                        $end_date = \Carbon::parse($holiday->end_date);

                        $diff = $start_date->diffInDays($end_date);
                        $diff += 1;
                        $start_day_name = $start_date->format('l');
                    @endphp
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if(!empty($holiday->user))
                                    @php
                                        if(isset($holiday->user->media->display_url)) {
                                            $img_src = $holiday->user->media->display_url;
                                        } else {
                                            $name = trim(($holiday->user->surname ?? '') . ' ' . ($holiday->user->first_name ?? ''));
                                            $img_src = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=40&background=random&color=fff&bold=true';
                                        }
                                    @endphp
                                    <img src="{{$img_src}}" alt="{{$holiday->user->user_full_name}}" class="img-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                @endif
                                <div>
                                    <strong>{{$holiday->name}}</strong>
                                    @if(!empty($holiday->user))
                                        <br><small class="text-muted">{{$holiday->user->user_full_name}}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{@format_date($holiday->start_date)}} ({{ $start_day_name }}) ({{ $diff . ' ' . Str::plural(__('lang_v1.day'), $diff)}})</td>
                        <td>{{$holiday->location->name ?? __("lang_v1.all")}}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">@lang('lang_v1.no_data')</td>
                    </tr>
                @endforelse
                <tr>
                    <td colspan="3">&nbsp;</td>
                </tr>
                <tr>
                    <th class="bg-light-gray" colspan="3">@lang('lang_v1.upcoming')</th>
                </tr>
                @forelse($upcoming_holidays as $holiday)
                    @php
                        $start_date = \Carbon::parse($holiday->start_date);
                        $end_date = \Carbon::parse($holiday->end_date);

                        $diff = $start_date->diffInDays($end_date);
                        $diff += 1;
                        $start_day_name = $start_date->format('l');
                    @endphp
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if(!empty($holiday->user))
                                    @php
                                        if(isset($holiday->user->media->display_url)) {
                                            $img_src = $holiday->user->media->display_url;
                                        } else {
                                            $name = trim(($holiday->user->surname ?? '') . ' ' . ($holiday->user->first_name ?? ''));
                                            $img_src = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=40&background=random&color=fff&bold=true';
                                        }
                                    @endphp
                                    <img src="{{$img_src}}" alt="{{$holiday->user->user_full_name}}" class="img-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                @endif
                                <div>
                                    <strong>{{$holiday->name}}</strong>
                                    @if(!empty($holiday->user))
                                        <br><small class="text-muted">{{$holiday->user->user_full_name}}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{@format_date($holiday->start_date)}} ({{ $start_day_name }}) ({{ $diff . ' ' . Str::plural(__('lang_v1.day'), $diff)}})</td>
                        <td>{{$holiday->location->name ?? __("lang_v1.all")}}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">@lang('lang_v1.no_data')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endcomponent
</div>