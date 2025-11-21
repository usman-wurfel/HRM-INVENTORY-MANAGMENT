<?php

namespace Modules\Essentials\Http\Controllers;

use App\BusinessLocation;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsHoliday;
use Yajra\DataTables\Facades\DataTables;

class EssentialsHolidayController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $can_manage_holiday = auth()->user()->can('essentials.crud_holiday') || $is_admin;

        $essentialsUtil = new \Modules\Essentials\Utils\EssentialsUtil;

        if (request()->ajax()) {
            
            $permitted_locations = auth()->user()->permitted_locations();

            $holidays = $essentialsUtil->Gettotalholiday($business_id, request()->input('location_id'), request()->start_date, request()->end_date, $permitted_locations);

            return Datatables::of($holidays)
                ->addColumn(
                    'action',
                    function ($row) use ($can_manage_holiday) {
                        $html = '';
                        if ($can_manage_holiday) {
                            $html .= '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container="#add_holiday_modal" data-href="'.action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'edit'], [$row->id]).'"><i class="fa fa-edit"></i> '.__('messages.edit').'</button>
                            &nbsp;
                            <button class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete-holiday" data-href="'.action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'destroy'], [$row->id]).'"><i class="fa fa-trash"></i> '.__('messages.delete').'</button>
                            ';
                        }

                        return $html;
                    }
                )
                ->editColumn('location', '{{$location ?? __("lang_v1.all")}}')
                ->editColumn('start_date', function ($row) {
                    if (!empty($row->type) && $row->type == 'consecutive') {
                        $repeat_info = '';
                        if (!empty($row->repeat_type) && $row->repeat_type == 'week' && isset($row->weekdays) && $row->weekdays !== '') {
                            $weekdays = explode(',', $row->weekdays);
                            $day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            $selected_days = array_map(function($day) use ($day_names) {
                                return $day_names[(int)$day];
                            }, $weekdays);
                            $repeat_info = __('essentials::lang.week') . ': ' . implode(', ', $selected_days);
                        } elseif (!empty($row->repeat_type) && $row->repeat_type == 'month' && !empty($row->repeat_days)) {
                            $repeat_info = __('essentials::lang.month') . ': ' . $row->repeat_days;
                        }
                        return __('essentials::lang.consecutive_holidays') . ' (' . $repeat_info . ')';
                    } else {
                        if (empty($row->start_date) || empty($row->end_date)) {
                            return '-';
                        }
                        $start_date = \Carbon::parse($row->start_date);
                        $end_date = \Carbon::parse($row->end_date);

                        $diff = $start_date->diffInDays($end_date);
                        $diff += 1;
                        $start_date_formated = $this->moduleUtil->format_date($start_date);
                        $end_date_formated = $this->moduleUtil->format_date($end_date);
                        
                        // Add day name to start date
                        $start_day_name = $start_date->format('l'); // Full day name (Monday, Tuesday, etc.)
                        $end_day_name = $end_date->format('l'); // Full day name

                        return $start_date_formated.' ('.$start_day_name.') - '.$end_date_formated.' ('.$end_day_name.') ('.$diff.\Str::plural(__('lang_v1.day'), $diff).')';
                    }
                })
                ->addColumn('employee', function ($row) {
                    return !empty($row->employee) ? $row->employee : '-';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        $locations = BusinessLocation::forDropdown($business_id);

        return view('essentials::holiday.index')->with(compact('locations', 'can_manage_holiday', 'is_admin'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        if (! $is_admin && ! auth()->user()->can('essentials.crud_holiday')) {
            abort(403, 'Unauthorized action.');
        }

        $locations = BusinessLocation::forDropdown($business_id);
        $users = User::forDropdown($business_id, false);

        return view('essentials::holiday.create')->with(compact('locations', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        if (! $is_admin && ! auth()->user()->can('essentials.crud_holiday')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'type', 'start_date', 'end_date', 'location_id', 'note', 'user_id', 'repeat_type', 'repeat_days', 'repeat_pattern', 'gap_weeks', 'custom_dates']);
            
            // Get weekdays separately to handle value 0 (Sunday) properly
            $weekdays = $request->input('weekdays', []);
            
            $input['business_id'] = $business_id;
            $input['type'] = $input['type'] ?? 'normal';

            if ($input['type'] == 'normal') {
                $input['start_date'] = $this->moduleUtil->uf_date($input['start_date']);
                $input['end_date'] = $this->moduleUtil->uf_date($input['end_date']);
            } else {
                // For consecutive holidays, weekdays or repeat_days will be used
                if (is_array($weekdays) && count($weekdays) > 0) {
                    $input['weekdays'] = implode(',', $weekdays);
                } else {
                    $input['weekdays'] = null;
                }
                // start_date and end_date not required for consecutive
                $input['start_date'] = null;
                $input['end_date'] = null;
                
                // Handle custom dates - convert to JSON if provided
                if (!empty($input['custom_dates'])) {
                    $dates = explode(',', $input['custom_dates']);
                    $dates = array_map('trim', $dates);
                    $input['custom_dates'] = json_encode($dates);
                }
            }

            EssentialsHoliday::create($input);
            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return view('essentials::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        if (! $is_admin && ! auth()->user()->can('essentials.crud_holiday')) {
            abort(403, 'Unauthorized action.');
        }

        $holiday = EssentialsHoliday::where('business_id', $business_id)
                                    ->findOrFail($id);

        $locations = BusinessLocation::forDropdown($business_id);
        $users = User::forDropdown($business_id, false);
        
        // Parse weekdays if exists - use isset to handle value "0" (Sunday)
        $holiday->weekdays_array = (isset($holiday->weekdays) && $holiday->weekdays !== '') ? explode(',', $holiday->weekdays) : [];

        return view('essentials::holiday.edit')->with(compact('locations', 'holiday', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        if (! $is_admin && ! auth()->user()->can('essentials.crud_holiday')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'type', 'start_date', 'end_date', 'location_id', 'note', 'user_id', 'repeat_type', 'repeat_days', 'repeat_pattern', 'gap_weeks', 'custom_dates']);

            // Get weekdays separately to handle value 0 (Sunday) properly
            $weekdays = $request->input('weekdays', []);

            $input['type'] = $input['type'] ?? 'normal';

            if ($input['type'] == 'normal') {
                $input['start_date'] = $this->moduleUtil->uf_date($input['start_date']);
                $input['end_date'] = $this->moduleUtil->uf_date($input['end_date']);
            } else {
                // For consecutive holidays
                if (is_array($weekdays) && count($weekdays) > 0) {
                    $input['weekdays'] = implode(',', $weekdays);
                } else {
                    $input['weekdays'] = null;
                }
                $input['start_date'] = null;
                $input['end_date'] = null;
                
                // Handle custom dates - convert to JSON if provided
                if (!empty($input['custom_dates'])) {
                    $dates = explode(',', $input['custom_dates']);
                    $dates = array_map('trim', $dates);
                    $input['custom_dates'] = json_encode($dates);
                }
            }

            EssentialsHoliday::where('business_id', $business_id)
                        ->where('id', $id)
                        ->update($input);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        if (! $is_admin && ! auth()->user()->can('essentials.crud_holiday')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            EssentialsHoliday::where('business_id', $business_id)
                        ->where('id', $id)
                        ->delete();

            $output = ['success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
