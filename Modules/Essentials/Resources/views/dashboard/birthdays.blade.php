<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
    @component('components.widget', [
        'class' => '',
        'title' => __('essentials::lang.birthdays'),
        'icon' => '<i class="fas fa-birthday-cake"></i>',
    ])
        <style>
            .birthdays-upcoming-scroll {
                max-height: 120px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .birthdays-upcoming-scroll::-webkit-scrollbar {
                width: 6px;
            }
            .birthdays-upcoming-scroll::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            .birthdays-upcoming-scroll::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 10px;
            }
            .birthdays-upcoming-scroll::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        </style>
        <div class="widget-content-scroll">
            <table class="table no-margin">
                <tbody>
                    <tr>
                        <th class="bg-light-gray" colspan="3">@lang('home.today')</th>
                    </tr>
                    @forelse($today_births as $birthday)
                        <tr>
                            <td>{{ $birthday->surname }} {{ $birthday->first_name }} {{ $birthday->last_name }}</td>
                            <td>{{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y'))) }} </td>
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
                </tbody>
            </table>
        </div>
        @if($up_comming_births->count() > 2)
            <div class="birthdays-upcoming-scroll">
                <table class="table no-margin">
                    <tbody>
                        @foreach($up_comming_births as $birthday)
                            <tr>
                                <td>{{ $birthday->surname }} {{ $birthday->first_name }} {{ $birthday->last_name }}</td>
                                @if (date('m') == '12' && Carbon::parse($birthday->dob)->format('m') == '1')
                                    <td>{{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y') + 1)) }} </td>
                                @else
                                    <td>{{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y'))) }} </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="widget-content-scroll">
                <table class="table no-margin">
                    <tbody>
                        @forelse($up_comming_births as $birthday)
                            <tr>
                                <td>{{ $birthday->surname }} {{ $birthday->first_name }} {{ $birthday->last_name }}</td>
                                @if (date('m') == '12' && Carbon::parse($birthday->dob)->format('m') == '1')
                                    <td>{{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y') + 1)) }} </td>
                                @else
                                    <td>{{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y'))) }} </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">@lang('lang_v1.no_data')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endcomponent
</div>
