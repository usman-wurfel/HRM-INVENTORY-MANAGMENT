<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
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
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

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
