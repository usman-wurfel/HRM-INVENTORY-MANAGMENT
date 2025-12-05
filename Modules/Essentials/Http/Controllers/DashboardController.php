<?php

namespace Modules\Essentials\Http\Controllers;

use App\Category;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsHoliday;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;
use Modules\Essentials\Utils\EssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $essentialsUtil;

    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(
        ModuleUtil $moduleUtil,
        EssentialsUtil $essentialsUtil,
        TransactionUtil $transactionUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function hrmDashboard()
    {
        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        $user_id = auth()->user()->id;

        $users = User::where('business_id', $business_id)
            ->user()
            ->get();

        $departments = Category::where('business_id', $business_id)
            ->where('category_type', 'hrm_department')
            ->get();
        $users_by_dept = $users->groupBy('essentials_department_id');

        $today = new \Carbon('today');

        $one_month_from_today = \Carbon::now()->addMonth();
        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->with(['user', 'leave_type'])
            ->orderBy('start_date', 'asc')
            ->get();

        $todays_leaves = [];
        $upcoming_leaves = [];

        $users_leaves = [];
        foreach ($leaves as $leave) {
            $leave_start = \Carbon::parse($leave->start_date);
            $leave_end = \Carbon::parse($leave->end_date);

            if ($today->gte($leave_start) && $today->lte($leave_end)) {
                $todays_leaves[] = $leave;

                if ($leave->user_id == $user_id) {
                    $users_leaves[] = $leave;
                }
            } elseif ($today->lt($leave_start) && $leave_start->lte($one_month_from_today)) {
                $upcoming_leaves[] = $leave;

                if ($leave->user_id == $user_id) {
                    $users_leaves[] = $leave;
                }
            }
        }

        // Get normal holidays without location filtering
        $holidays_query = EssentialsHoliday::where(
            'essentials_holidays.business_id',
            $business_id
        )
            ->where('type', 'normal')
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->orderBy('start_date', 'asc')
            ->with(['location', 'user.media']);

        $holidays = $holidays_query->get();

        // Get consecutive holidays without location filtering
        $consecutive_holidays_query = EssentialsHoliday::where('essentials_holidays.business_id', $business_id)
            ->where('type', 'consecutive')
            ->with(['user.media', 'location', 'user.permissions']);
        
        $consecutive_holidays = $consecutive_holidays_query->get();

        $todays_holidays = [];
        $upcoming_holidays = [];

        // Process normal holidays
        foreach ($holidays as $holiday) {
            $holiday_start = \Carbon::parse($holiday->start_date);
            $holiday_end = \Carbon::parse($holiday->end_date);

            if ($today->gte($holiday_start) && $today->lte($holiday_end)) {
                $todays_holidays[] = $holiday;
            } elseif ($today->lt($holiday_start) && $holiday_start->lte($one_month_from_today)) {
                $upcoming_holidays[] = $holiday;
            }
        }

        // Process consecutive holidays - show only closest holiday for each employee
        foreach ($consecutive_holidays as $holiday) {
            $holiday_dates = $this->getConsecutiveHolidayDates($holiday, $today->format('Y-m-d'), $one_month_from_today->format('Y-m-d'));
            
            $closest_date = null;
            $is_today = false;
            
            // Find closest date (today has priority, then next upcoming)
            foreach ($holiday_dates as $date) {
                $holiday_date = \Carbon::parse($date);
                
                // Check if date is today or upcoming (within one month)
                if ($today->format('Y-m-d') == $date) {
                    // Today's holiday - highest priority
                    $closest_date = $date;
                    $is_today = true;
                    break; // Break as soon as we find today's date
                } elseif ($holiday_date->gt($today) && $holiday_date->lte($one_month_from_today)) {
                    // Upcoming holiday - check if it's closer than previous
                    if ($closest_date === null || $holiday_date->lt(\Carbon::parse($closest_date))) {
                        $closest_date = $date;
                        $is_today = false;
                    }
                }
            }
            
            // Add only the closest holiday if found
            if ($closest_date !== null) {
                $holiday_copy = clone $holiday;
                $holiday_copy->start_date = $closest_date;
                $holiday_copy->end_date = $closest_date;
                // Preserve user and location relationships
                $holiday_copy->user = $holiday->user;
                $holiday_copy->location = $holiday->location;
                
                if ($is_today) {
                    $todays_holidays[] = $holiday_copy;
                } else {
                    $upcoming_holidays[] = $holiday_copy;
                }
            }
        }
        
        // Sort holidays by date
        usort($todays_holidays, function($a, $b) {
            return \Carbon::parse($a->start_date)->timestamp - \Carbon::parse($b->start_date)->timestamp;
        });
        
        usort($upcoming_holidays, function($a, $b) {
            return \Carbon::parse($a->start_date)->timestamp - \Carbon::parse($b->start_date)->timestamp;
        });
        
        $todays_attendances = [];
        if ($is_admin) {
            $todays_attendances = EssentialsAttendance::where('business_id', $business_id)
                ->whereDate('clock_in_time', \Carbon::now()->format('Y-m-d'))
                ->with(['employee'])
                ->orderBy('clock_in_time', 'asc')
                ->get();
        }

        $settings = $this->essentialsUtil->getEssentialsSettings();

        $sales_targets = EssentialsUserSalesTarget::where('user_id', $user_id)
            ->get();

        $start_date = \Carbon::today()->startOfMonth()->format('Y-m-d');
        $end_date = \Carbon::today()->endOfMonth()->format('Y-m-d');

        $sale_totals = $this->transactionUtil->getUserTotalSales($business_id, $user_id, $start_date, $end_date);

        $target_achieved_this_month = !empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1 ? $sale_totals['total_sales_without_tax'] : $sale_totals['total_sales'];

        $start_date = \Carbon::parse('first day of last month')->format('Y-m-d');
        $end_date = \Carbon::parse('last day of last month')->format('Y-m-d');

        $sale_totals = $this->transactionUtil->getUserTotalSales($business_id, $user_id, $start_date, $end_date);

        $target_achieved_last_month = !empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1 ? $sale_totals['total_sales_without_tax'] : $sale_totals['total_sales'];

        // Get today's birthdays
        $today_births = User::where('business_id', $business_id)
            ->whereNotNull('dob')
            ->whereMonth('dob', \Carbon::now()->format('m'))
            ->whereDay('dob', \Carbon::now()->format('d'))
            ->get();
        
        // Get upcoming birthdays (next 30 days, excluding today)
        $today = \Carbon::now();
        $today_md = $today->format('m-d');
        $thirty_days_later = $today->copy()->addDays(30);
        $end_md = $thirty_days_later->format('m-d');
        
        // Use DATE_FORMAT to compare month-day, handling year rollover
        if ($end_md >= $today_md) {
            // Normal case: within same year (e.g., Jan 15 to Feb 15)
            $up_comming_births = User::where('business_id', $business_id)
                ->whereNotNull('dob')
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') > ?", [$today_md])
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') <= ?", [$end_md])
                ->orderByRaw("MONTH(dob), DAY(dob)")
                ->get();
        } else {
            // Year rollover case: from today to end of year, then from start of year to end date
            $up_comming_births = User::where('business_id', $business_id)
                ->whereNotNull('dob')
                ->where(function($query) use ($today_md, $end_md) {
                    $query->whereRaw("DATE_FORMAT(dob, '%m-%d') > ?", [$today_md])
                          ->orWhereRaw("DATE_FORMAT(dob, '%m-%d') <= ?", [$end_md]);
                })
                ->orderByRaw("CASE WHEN DATE_FORMAT(dob, '%m-%d') > ? THEN 0 ELSE 1 END, MONTH(dob), DAY(dob)", [$today_md])
                ->get();
        }

        return view('essentials::dashboard.hrm_dashboard')
            ->with(compact('users', 'departments', 'users_by_dept', 'todays_holidays', 'todays_leaves', 'upcoming_leaves', 'is_admin', 'users_leaves', 'upcoming_holidays', 'todays_attendances', 'sales_targets', 'target_achieved_this_month', 'target_achieved_last_month', 'up_comming_births', 'today_births'));
    }

    /**
     * Get consecutive holiday dates based on repeat type
     */
    private function getConsecutiveHolidayDates($holiday, $start_date, $end_date)
    {
        $dates = [];
        $current = \Carbon::parse($start_date)->startOfDay();
        $end = \Carbon::parse($end_date)->endOfDay();

        if ($holiday->repeat_type == 'week' && isset($holiday->weekdays) && $holiday->weekdays !== '') {
            $weekdays = array_map('intval', explode(',', $holiday->weekdays));
            $repeat_pattern = $holiday->repeat_pattern ?? 'every';
            $gap_weeks = (int)($holiday->gap_weeks ?? 1);
            
            $week_counter = 0;
            $last_week_number = null;
            
            while ($current->lte($end)) {
                $dayOfWeek = $current->dayOfWeek; // 0 = Sunday, 6 = Saturday
                $current_week_number = $current->format('Y-W'); // Year-Week number
                
                if (in_array($dayOfWeek, $weekdays)) {
                    $should_include = false;
                    
                    if ($repeat_pattern == 'every') {
                        $should_include = true;
                    } elseif ($repeat_pattern == 'alternate') {
                        // Alternate week - every 2nd week
                        if ($last_week_number != $current_week_number) {
                            $week_counter++;
                            $last_week_number = $current_week_number;
                        }
                        $should_include = ($week_counter % 2 == 1);
                    } elseif ($repeat_pattern == 'gap') {
                        // Gap pattern - skip N weeks
                        if ($last_week_number != $current_week_number) {
                            $week_counter++;
                            $last_week_number = $current_week_number;
                        }
                        $should_include = ($week_counter % ($gap_weeks + 1) == 1);
                    }
                    
                    if ($should_include) {
                        $dates[] = $current->format('Y-m-d');
                    }
                }
                $current->addDay();
            }
        } elseif ($holiday->repeat_type == 'month' && !empty($holiday->repeat_days)) {
            $days = array_map('intval', explode(',', $holiday->repeat_days));
            
            while ($current->lte($end)) {
                $dayOfMonth = (int)$current->format('d');
                if (in_array($dayOfMonth, $days)) {
                    $dates[] = $current->format('Y-m-d');
                }
                $current->addDay();
            }
        } elseif ($holiday->repeat_type == 'custom' && !empty($holiday->custom_dates)) {
            // Custom dates from JSON
            $custom_dates = json_decode($holiday->custom_dates, true);
            if (is_array($custom_dates)) {
                foreach ($custom_dates as $date_str) {
                    $date = \Carbon::parse($date_str);
                    if ($date->gte($current) && $date->lte($end)) {
                        $dates[] = $date->format('Y-m-d');
                    }
                }
            }
        }

        return $dates;
    }

    public function getUserSalesTargets()
    {
        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        $user_id = auth()->user()->id;

        if (!$is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $this_month_start_date = \Carbon::today()->startOfMonth()->format('Y-m-d');
        $this_month_end_date = \Carbon::today()->endOfMonth()->format('Y-m-d');
        $last_month_start_date = \Carbon::parse('first day of last month')->format('Y-m-d');
        $last_month_end_date = \Carbon::parse('last day of last month')->format('Y-m-d');

        $settings = $this->essentialsUtil->getEssentialsSettings();

        $query = User::where('users.business_id', $business_id)
            ->join('transactions as t', 't.commission_agent', '=', 'users.id')
            ->where('t.type', 'sell')
            ->whereDate('transaction_date', '>=', $last_month_start_date)
            ->where('t.status', 'final');

        if (!empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1) {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$last_month_start_date}' AND '{$last_month_end_date}', total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=t.id), 0) ) as total_sales_last_month"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$this_month_start_date}' AND '{$this_month_end_date}', total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=t.id), 0) ) as total_sales_this_month")
            );
        } else {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$last_month_start_date}' AND '{$last_month_end_date}', final_total, 0)) as total_sales_last_month"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$this_month_start_date}' AND '{$this_month_end_date}', final_total, 0)) as total_sales_this_month")
            );
        }

        $query->groupBy('users.id');

        return Datatables::of($query)
            ->editColumn('total_sales_this_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_this_month, true);
            })
            ->editColumn('total_sales_last_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_last_month, true);
            })
            ->make(false);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function essentialsDashboard()
    {
        return view('essentials::dashboard.essentials_dashboard');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('essentials::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('essentials::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('essentials::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
