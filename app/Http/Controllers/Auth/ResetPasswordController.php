<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Utils\BusinessUtil;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {
        $this->middleware('guest');
        $this->businessUtil = $businessUtil;
    }

    /**
     * Where to redirect users after resetting their password.
     * Same logic as login - redirect to HRM dashboard if user has HRM permissions
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = \Auth::user();
        
        if (!$user) {
            return '/home';
        }
        
        // Check if user is not admin
        $is_admin = $this->businessUtil->is_admin($user);
     
        if (!$is_admin) {
            // Check if user has any HRM permission
            $has_hrm_permission = $user->can('essentials.crud_leave_type') || 
                                 $user->can('essentials.crud_all_leave') || 
                                 $user->can('essentials.crud_own_leave') ||
                                 $user->can('essentials.crud_all_attendance') || 
                                 $user->can('essentials.view_own_attendance') ||
                                 $user->can('essentials.access_sales_target') ||
                                 $user->can('essentials.loan_request') ||
                                 $user->can('essentials.loan_manage') ||
                                 $user->can('essentials.crud_department') ||
                                 $user->can('essentials.crud_designation');
                              
            if ($has_hrm_permission) {
                return '/hrm/dashboard';
            }
        }
        
        if (!$user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }

        return '/home';
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        // Get business logo for password reset page
        $business_logo = null;
        try {
            $business = \App\Business::first();
            if ($business && !empty($business->logo)) {
                $logo_path = public_path('uploads/business_logos/' . $business->logo);
                if (file_exists($logo_path)) {
                    $business_logo = asset('uploads/business_logos/' . $business->logo);
                }
            }
        } catch (\Exception $e) {
            // If business not found or error, use default logo
        }

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email, 'business_logo' => $business_logo]
        );
    }
}
