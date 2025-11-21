<?php

namespace Modules\Essentials\Http\Controllers;

use App\BusinessLocation;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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

        // Get locations based on user's permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations == 'all' || $is_admin) {
            $locations = BusinessLocation::forDropdown($business_id, false, false, true, false);
        } else {
            $locations = BusinessLocation::forDropdown($business_id, false, false, true, true);
        }

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

        // Get locations based on user's permitted locations
        // If user has access_all_locations, show all locations, otherwise show only permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations == 'all' || $is_admin) {
            $locations = BusinessLocation::forDropdown($business_id, false, false, true, false);
        } else {
            $locations = BusinessLocation::forDropdown($business_id, false, false, true, true);
        }
        
        // Get employees based on user's permitted locations
        // If user has access_all_locations, show all employees, otherwise show only employees who have permissions for any of the permitted locations
        if ($permitted_locations == 'all' || $is_admin) {
            $users = User::forDropdown($business_id, false);
        } else {
            // Build location permission names (e.g., 'location.1', 'location.2')
            $location_permissions = [];
            foreach ($permitted_locations as $location_id) {
                $location_permissions[] = 'location.'.$location_id;
            }
            
            $users_query = User::where('business_id', $business_id)->user();
            // Filter employees who have any of the permitted location permissions OR have location_id matching permitted locations
            $users_query->where(function($query) use ($permitted_locations, $location_permissions) {
                $query->whereIn('location_id', $permitted_locations)
                      ->orWhereHas('permissions', function($q) use ($location_permissions) {
                          $q->whereIn('permissions.name', $location_permissions);
                      });
            });
            $all_users = $users_query->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"))->get();
            $users = $all_users->pluck('full_name', 'id');
        }

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
            
            // Handle location_id - if empty string, set to null
            if (isset($input['location_id']) && $input['location_id'] === '') {
                $input['location_id'] = null;
            }
            
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
    public function show($id)
    {
        // Redirect to index as show view is not needed
        return redirect()->action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'index']);
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

        // Get locations based on user's permitted locations
        // If user has access_all_locations, show all locations, otherwise show only permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations == 'all' || $is_admin) {
            $locations = BusinessLocation::forDropdown($business_id, false, false, true, false);
        } else {
            $locations = BusinessLocation::forDropdown($business_id, false, false, true, true);
        }
        
        // Get employees based on user's permitted locations
        // If user has access_all_locations, show all employees, otherwise show only employees who have permissions for any of the permitted locations
        if ($permitted_locations == 'all' || $is_admin) {
            $users = User::forDropdown($business_id, false);
        } else {
            // Build location permission names (e.g., 'location.1', 'location.2')
            $location_permissions = [];
            foreach ($permitted_locations as $location_id) {
                $location_permissions[] = 'location.'.$location_id;
            }
            
            $users_query = User::where('business_id', $business_id)->user();
            // Filter employees who have any of the permitted location permissions OR have location_id matching permitted locations
            $users_query->where(function($query) use ($permitted_locations, $location_permissions) {
                $query->whereIn('location_id', $permitted_locations)
                      ->orWhereHas('permissions', function($q) use ($location_permissions) {
                          $q->whereIn('permissions.name', $location_permissions);
                      });
            });
            $all_users = $users_query->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"))->get();
            $users = $all_users->pluck('full_name', 'id');
        }
        
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

            // Handle location_id - if empty string, set to null
            if (isset($input['location_id']) && $input['location_id'] === '') {
                $input['location_id'] = null;
            }

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

    /**
     * Get employee locations based on user_id
     */
    public function getEmployeeLocations(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->input('user_id');

        if (empty($user_id)) {
            return response()->json([
                'success' => false,
                'locations' => [],
                'location_id' => null,
                'is_readonly' => false
            ]);
        }

        try {
            $user = User::where('business_id', $business_id)->findOrFail($user_id);
            
            // Get employee's locations from permissions
            $employee_locations = [];
            
            // Check location_id column
            if (!empty($user->location_id)) {
                $employee_locations[] = $user->location_id;
            }
            
            // Get locations from permissions (location.1, location.2, etc.)
            $permissions = $user->permissions->pluck('name')->all();
            foreach ($permissions as $permission) {
                if (strpos($permission, 'location.') === 0) {
                    $location_id = (int) str_replace('location.', '', $permission);
                    if (!in_array($location_id, $employee_locations)) {
                        $employee_locations[] = $location_id;
                    }
                }
            }

            // Filter by current user's permitted locations
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $employee_locations = array_intersect($employee_locations, $permitted_locations);
            }

            // Get location names
            $locations = [];
            if (!empty($employee_locations)) {
                $locations = BusinessLocation::whereIn('id', $employee_locations)
                    ->where('business_id', $business_id)
                    ->select('id', DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"))
                    ->get()
                    ->pluck('name', 'id')
                    ->toArray();
            }

            $location_id = null;
            $is_readonly = false;

            // If only one location, auto-select and make readonly
            if (count($employee_locations) == 1) {
                $location_id = $employee_locations[0];
                $is_readonly = true;
            }

            return response()->json([
                'success' => true,
                'locations' => $locations,
                'location_id' => $location_id,
                'is_readonly' => $is_readonly
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            
            return response()->json([
                'success' => false,
                'locations' => [],
                'location_id' => null,
                'is_readonly' => false
            ]);
        }
    }
}
