<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Models\AddonSetting;
use App\Models\LoginSetup;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginSetupController extends Controller
{
    public function __construct(
        private LoginSetup $loginSetup
    ) {
    }

    /**
     * @return Application|Factory|View|\Illuminate\Foundation\Application
     */
    public function loginSetup(): Factory|\Illuminate\Foundation\Application|View|Application
    {
        $loginOptionsValue = $this->loginSetup->where(['key' => 'login_options'])?->first()->value;
        $loginOptions = json_decode($loginOptionsValue);

        $socialMediaLoginValue = $this->loginSetup->where(['key' => 'social_media_for_login'])?->first()->value;
        $socialMediaLoginOptions = json_decode($socialMediaLoginValue);

        $emailVerification = (int) $this->loginSetup->where(['key' => 'email_verification'])?->first()->value ?? 0;
        $phoneVerification = (int) $this->loginSetup->where(['key' => 'phone_verification'])?->first()->value ?? 0;
        return view('admin-views.business-settings.login-setup', compact('emailVerification', 'phoneVerification', 'loginOptions', 'socialMediaLoginOptions'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function loginSetupUpdate(Request $request): RedirectResponse
    {
        if ($request->has('otp_login')) {
            if (!$request->has('phone_verification')) {
                Toastr::error(translate('Please active Phone Verification when OTP Login is active.'));
                return back();
            }
        }

        if ($request->has('social_media_login')) {
            if (!$request->has('google') && !$request->has('facebook')) {
                Toastr::error(translate('Please active at lest one social media login option between google or facebook.'));
                return back();
            }
        }

        $this->loginSetup->updateOrInsert(
            ['key' => 'login_options'],
            [
                'value' => json_encode([
                    'manual_login' => $request->has('manual_login') ? 1 : 0,
                    'otp_login' => $request->has('otp_login') ? 1 : 0,
                    'social_media_login' => $request->has('social_media_login') ? 1 : 0,
                ])
            ]
        );

        $this->loginSetup->updateOrInsert(
            ['key' => 'social_media_for_login'],
            [
                'value' => json_encode([
                    'google' => $request->has('google') ? 1 : 0,
                    'facebook' => $request->has('facebook') ? 1 : 0,
                    'apple' => $request->has('apple') ? 1 : 0,
                ])
            ]
        );

        $this->loginSetup->updateOrInsert(
            ['key' => 'email_verification'],
            [
                'value' => $request->has('email_verification') ? 1 : 0,
            ]
        );

        $this->loginSetup->updateOrInsert(
            ['key' => 'phone_verification'],
            [
                'value' => $request->has('phone_verification') ? 1 : 0,
            ]
        );

        Toastr::success(translate('Settings updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkActiveSMSGateway(Request $request): JsonResponse
    {
        $dataValues = AddonSetting::where('settings_type', 'sms_config')->get();
        $count = 0;
        foreach ($dataValues as $gateway) {
            $status = isset($gateway->live_values['status']) ? (int) $gateway->live_values['status'] : 0;
            if ($status == 1) {
                $count++;
            }
        }

        $firebaseOTPVerification = Helpers::get_business_settings('firebase_otp_verification');
        $firebaseOTPVerificationStatus = (integer) ($firebaseOTPVerification ? $firebaseOTPVerification['status'] : 0);
        if ($firebaseOTPVerificationStatus == 1) {
            $count++;
        }

        return response()->json($count);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkActiveSocialMedia(Request $request): JsonResponse
    {
        $appleLogin = BusinessSetting::where('key', 'apple_login')->first();
        $appleLoginService = json_decode($appleLogin->value, true);
        $clientId = $appleLoginService['client_id'];
        $teamId = $appleLoginService['team_id'];
        $keyId = $appleLoginService['key_id'];
        $serviceFile = $appleLoginService['service_file'];

        $apple = 1;
        if ($clientId == null || $teamId == null || $keyId == null || $serviceFile == null) {
            $apple = 0;
        }

        return response()->json([
            'apple' => $apple,
        ]);
    }
}
