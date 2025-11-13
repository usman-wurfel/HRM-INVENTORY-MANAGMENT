<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Rules\ReCaptcha;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function showLoginForm()
    {
        // Get business logo for login page
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
        
        return view('auth.login', compact('business_logo'));
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout()
    {
        $this->businessUtil->activityLog(auth()->user(), 'logout');

        request()->session()->flush();
        \Auth::logout();

        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $this->businessUtil->activityLog($user, 'login', null, [], false, $user->business_id);

        if (! $user->business->is_active) {
            \Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
              );
        } elseif ($user->status != 'active') {
            \Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
              );
        } elseif (! $user->allow_login) {
            \Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]
                );
        } elseif (($user->user_type == 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            \Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]
                );
        }
    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        
        // Check if user is not admin
        $is_admin = $this->businessUtil->is_admin($user);
     
        if (! $is_admin) {
            // Check if HRM module is enabled
         
              
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
        
        if (! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }

        return '/home';
    }

    public function validateLogin(Request $request)
    {
        if(config('constants.enable_recaptcha')){
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);
        }else{
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ]);
        }
       
    }

}
