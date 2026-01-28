<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Model\EmailVerifications;
use App\Model\PhoneVerification;
use App\Models\LoginSetup;
use App\User;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterval;
use Modules\Gateways\Traits\SmsGateway;

class CustomerAuthController extends Controller
{
    public function __construct(
        private PhoneVerification $phone_verification,
        private User $user,
        private LoginSetup $loginSetup,
        private PhoneVerification $phoneVerification
    )
    {}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function registration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users',
            'phone' => 'required|min:11|max:15|unique:users',
            'password' => 'required|min:6',
        ], [
            'f_name.required' => 'The first name field is required.',
            'l_name.required' => 'The last name field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->referral_code){
            $refer_user = $this->user->where(['referral_code' => $request->referral_code])->first();
            if (!$refer_user){
                return response()->json(['errors' => [
                    ['code' => 'referral', 'message' => translate('Invalid Referral Code!')]
                ]], 403);
            }
        }

        $temporaryToken = Str::random(40);

        $user = $this->user->create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email??'',
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'temporary_token' => $temporaryToken,
            'referral_code' => Helpers::generate_referer_code(),
            'referred_by' => $refer_user->id ?? null,
        ]);

        $emailVerification = (int) $this->loginSetup->where(['key' => 'email_verification'])?->first()->value ?? 0;
        $phoneVerification = (int) $this->loginSetup->where(['key' => 'phone_verification'])?->first()->value ?? 0;

        if ($phoneVerification && !$user->is_phone_verified) {
            return response()->json(['temporary_token' => $temporaryToken], 200);
        }
        if ($emailVerification && $user->email_verified_at == null) {
            return response()->json(['temporary_token' => $temporaryToken], 200);
        }

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

        try {
            $emailServices = Helpers::get_business_settings('mail_config');
            if (isset($emailServices['status']) && $emailServices['status'] == 1 && $request->email) {
                $name = $request->f_name. ' '. $request->l_name;
                Mail::to($request->email)->send(new \App\Mail\Customer\CustomerRegistration($name));
            }
        } catch (\Exception $e) {
        }

        return response()->json(['token' => $token], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:11|max:14'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $phoneVerification = (int) $this->loginSetup->where(['key' => 'phone_verification'])?->first()->value ?? 0;

        if ($phoneVerification == 1) {

            $otpIntervalTime = Helpers::get_business_settings('otp_resend_time') ?? 60;
            $otpVerificationData = DB::table('phone_verifications')->where('phone', $request['phone'])->first();

            if(isset($otpVerificationData) &&  Carbon::parse($otpVerificationData->created_at)->DiffInSeconds() < $otpIntervalTime){
                $time= $otpIntervalTime - Carbon::parse($otpVerificationData->created_at)->DiffInSeconds();

                $errors = [];
                $errors[] = [
                    'code' => 'otp',
                    'message' => translate('please_try_again_after_') . $time . ' ' . translate('seconds')
                ];
                return response()->json(['errors' => $errors], 403);
            }

            $token = (env('APP_MODE') == 'live') ? rand(100000, 999999) : 123456;

            DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']], [
                'phone' => $request['phone'],
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if(addon_published_status('Gateways')){
                $response = SmsGateway::send($request['phone'],$token);
            }else{
                $response = SMS_module::send($request['phone'], $token);
            }

            return response()->json([
                'message' => $response,
                'token' => 'active'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Number is ready to register',
                'token' => 'inactive'
            ], 200);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $emailVerification = (int) $this->loginSetup->where(['key' => 'email_verification'])?->first()->value ?? 0;

        if ($emailVerification) {

            $otpIntervalTime = Helpers::get_business_settings('otp_resend_time') ?? 60;
            $otpVerificationData = DB::table('email_verifications')->where('email', $request['email'])->first();

            if(isset($otpVerificationData) &&  Carbon::parse($otpVerificationData->created_at)->DiffInSeconds() < $otpIntervalTime){
                $time = $otpIntervalTime - Carbon::parse($otpVerificationData->created_at)->DiffInSeconds();

                $errors = [];
                $errors[] = [
                    'code' => 'otp',
                    'message' => translate('please_try_again_after_') . $time . ' ' . translate('seconds')
                ];
                return response()->json(['errors' => $errors], 403);
            }

            $token = (env('APP_MODE') == 'live') ? rand(100000, 999999) : 123456;

            DB::table('email_verifications')->updateOrInsert(['email' => $request['email']], [
                'email' => $request['email'],
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            try {
                $emailServices = Helpers::get_business_settings('mail_config');

                if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                    Mail::to($request['email'])->send(new \App\Mail\Customer\EmailVerification($token));
                }

            } catch (\Exception $exception) {
                $errors = [];
                $errors[] = [
                    'code' => 'otp',
                    'message' => 'Token sent failed'
                ];
                return response()->json(['errors' => $errors], 403);
            }

            return response()->json([
                'message' => 'Email is ready to register',
                'token' => 'active'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Email is ready to register',
                'token' => 'inactive'
            ], 200);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $maxOTPHit = Helpers::get_business_settings('maximum_otp_hit') ?? 5;
        $maxOTPHitTime = Helpers::get_business_settings('otp_resend_time') ?? 60;
        $tempBlockTime = Helpers::get_business_settings('temporary_block_time') ?? 600;

        $verify = $this->phone_verification->where(['phone' => $request['phone'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();

                $errors = [];
                $errors[] = ['code' => 'otp_block_time',
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ];
                return response()->json(['errors' => $errors], 403);
            }
            $user = $this->user->where(['phone' => $request['phone']])->first();
            $user->is_phone_verified = 1;
            $user->save();

            $verify->delete();

            $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

            return response()->json(['message' => 'OTP verified!', 'token' => $token, 'status' => true], 200);
        }
        else{
            $verificationData = DB::table('phone_verifications')->where('phone', $request['phone'])->first();

            if(isset($verificationData)){
                if(isset($verificationData->temp_block_time ) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();

                    $errors = [];
                    $errors[] = ['code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

                if($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->updated_at)->DiffInSeconds() >= $maxOTPHitTime){
                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                            'otp_hit_count' => 0,
                            'is_temp_blocked' => 0,
                            'temp_block_time' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                if($verificationData->otp_hit_count >= $maxOTPHit &&  Carbon::parse($verificationData->updated_at)->DiffInSeconds() < $maxOTPHitTime &&  $verificationData->is_temp_blocked == 0){
                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                            'is_temp_blocked' => 1,
                            'temp_block_time' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $time = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                    $errors = [];
                    $errors[] = [
                        'code' => 'otp_temp_blocked',
                        'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }
            }

            DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                [
                    'otp_hit_count' => DB::raw('otp_hit_count + 1'),
                    'updated_at' => now(),
                    'temp_block_time' => null,
                ]);
        }

        return response()->json(['errors' => [
            ['code' => 'token', 'message' => 'OTP is not matched!']
        ]], 403);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $maxOTPHit = Helpers::get_business_settings('maximum_otp_hit') ?? 5;
        $maxOTPHitTime = Helpers::get_business_settings('otp_resend_time') ?? 60;// seconds
        $tempBlockTime = Helpers::get_business_settings('temporary_block_time') ?? 600; // seconds

        $verify = EmailVerifications::where(['email' => $request['email'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();

                $errors = [];
                $errors[] = ['code' => 'otp_block_time',
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ];
                return response()->json(['errors' => $errors], 403);
            }
            $user = $this->user->where(['email' => $request['email']])->first();
            $user->email_verified_at = Carbon::now();
            $user->save();

            $verify->delete();

            $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

            return response()->json(['message' => 'OTP verified!', 'token' => $token, 'status' => true], 200);

        } else{
            $verificationData = DB::table('email_verifications')->where('email', $request['email'])->first();

            if(isset($verificationData)){
                if(isset($verificationData->temp_block_time ) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();

                    $errors = [];
                    $errors[] = ['code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

                if($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->updated_at)->DiffInSeconds() >= $maxOTPHitTime){
                    DB::table('email_verifications')->updateOrInsert(['email' => $request['email']],
                        [
                            'otp_hit_count' => 0,
                            'is_temp_blocked' => 0,
                            'temp_block_time' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                if($verificationData->otp_hit_count >= $maxOTPHit &&  Carbon::parse($verificationData->updated_at)->DiffInSeconds() < $maxOTPHitTime &&  $verificationData->is_temp_blocked == 0){

                    DB::table('email_verifications')->updateOrInsert(['email' => $request['email']],
                        [
                            'is_temp_blocked' => 1,
                            'temp_block_time' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $time = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                    $errors = [];
                    $errors[] = ['code' => 'otp_temp_blocked', 'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }
            }

            DB::table('email_verifications')->updateOrInsert(['email' => $request['email']],
                [
                    'otp_hit_count' => DB::raw('otp_hit_count + 1'),
                    'updated_at' => now(),
                    'temp_block_time' => null,
                ]);
        }

        return response()->json(['errors' => [
            ['code' => 'otp', 'message' => 'OTP is not matched!']
        ]], 403);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required',
            'password' => 'required|min:6',
            'type' => 'required|in:phone,email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = $request['email_or_phone'];
        $type = $request['type'];

        $user = $this->user->where('is_block', 0)
            ->where(function ($query) use ($userId) {
                $query->where(['email' => $userId])->orWhere('phone', $userId);
            })->first();

        $maxLoginHit = Helpers::get_business_settings('maximum_login_hit') ?? 5;
        $tempBlockTime = Helpers::get_business_settings('temporary_login_block_time') ?? 600;

        if (isset($user)) {
            if(isset($user->temp_block_time ) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($user->temp_block_time)->DiffInSeconds();

                $errors = [];
                $errors[] = ['code' => 'login_block_time',
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ];
                return response()->json(['errors' => $errors], 403);
            }

            if ($type == 'phone'){
                $data = [
                    'phone' => $user->phone,
                    'password' => $request->password,
                    'is_block' => 0
                ];
            }elseif ($type == 'email'){
                $data = [
                    'email' => $user->email,
                    'password' => $request->password,
                    'is_block' => 0
                ];
            }


            if (auth()->attempt($data)) {
                $temporaryToken = Str::random(40);

                $emailVerification = (int) $this->loginSetup->where(['key' => 'email_verification'])?->first()->value ?? 0;
                $phoneVerification = (int) $this->loginSetup->where(['key' => 'phone_verification'])?->first()->value ?? 0;

                if ($type == 'phone' && $phoneVerification && !$user->is_phone_verified) {
                    return response()->json(['temporary_token' => $temporaryToken, 'status' => false], 200);
                }
                if ($type == 'email' && $emailVerification && $user->email_verified_at == null) {
                    return response()->json(['temporary_token' => $temporaryToken, 'status' => false], 200);
                }

                $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;

                $user->login_hit_count = 0;
                $user->is_temp_blocked = 0;
                $user->temp_block_time = null;
                $user->updated_at = now();
                $user->language_code = $request->header('X-localization');
                $user->save();

                return response()->json(['token' => $token, 'status' => true], 200);

            }
            else{
                $customer = $this->user->where(['email' => $userId])->orWhere(['phone' => $userId])->first();

                if(isset($customer)){
                    if(isset($user->temp_block_time ) && Carbon::parse($user->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                        $time= $tempBlockTime - Carbon::parse($user->temp_block_time)->DiffInSeconds();

                        $errors = [];
                        $errors[] = [
                            'code' => 'login_block_time',
                            'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                        ];
                        return response()->json([
                            'errors' => $errors
                        ], 403);
                    }


                    if($user->is_temp_blocked == 1 && Carbon::parse($user->temp_block_time)->DiffInSeconds() >= $tempBlockTime){
                        $user->login_hit_count = 0;
                        $user->is_temp_blocked = 0;
                        $user->temp_block_time = null;
                        $user->updated_at = now();
                        $user->save();
                    }

                    if($user->login_hit_count >= $maxLoginHit &&  $user->is_temp_blocked == 0){
                        $user->is_temp_blocked = 1;
                        $user->temp_block_time = now();
                        $user->updated_at = now();
                        $user->save();

                        $time= $tempBlockTime - Carbon::parse($user->temp_block_time)->DiffInSeconds();

                        $errors = [];
                        $errors[] = [
                            'code' => 'login_temp_blocked',
                            'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()
                        ];
                        return response()->json([
                            'errors' => $errors
                        ], 403);
                    }
                }

                $user->login_hit_count += 1;
                $user->temp_block_time = null;
                $user->updated_at = now();
                $user->save();
            }
        }

        $errors = [];
        $errors[] = ['code' => 'auth-001', 'message' => 'Invalid credential.'];
        return response()->json([
            'errors' => $errors
        ], 401);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function firebaseAuthVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionInfo' => 'required',
            'phoneNumber' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $firebaseOTPVerification = Helpers::get_business_settings('firebase_otp_verification');
        $webApiKey = $firebaseOTPVerification ? $firebaseOTPVerification['web_api_key'] : '';

        $response = Http::post('https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key='. $webApiKey, [
            'sessionInfo' => $request->sessionInfo,
            'phoneNumber' => $request->phoneNumber,
            'code' => $request->code,
        ]);

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $errors = [];
            $errors[] = ['code' => "403", 'message' => $responseData['error']['message']];
            return response()->json(['errors' => $errors], 403);
        }

        $user = $this->user->where('phone', $responseData['phoneNumber'])->first();

        if (isset($user)){
            if ($request['is_reset_token'] == 1){
                DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request->phoneNumber], [
                    'email_or_phone' => $request->phoneNumber,
                    'token' => $request->code,
                    'created_at' => now(),
                ]);
            }else{
                $token = $user->createToken('AuthToken')->accessToken;
                $user->is_phone_verified = 1;
                $user->save();
                return response()->json(['errors' => null, 'token' => $token], 200);
            }
        }

        $tempToken = Str::random(120);
        return response()->json(['errors' => null, 'temp_token' => $tempToken], 200);
    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $maxOTPHit = Helpers::get_business_settings('maximum_otp_hit') ?? 5;
        $maxOTPHitTime = Helpers::get_business_settings('otp_resend_time') ?? 60;// seconds
        $tempBlockTime = Helpers::get_business_settings('temporary_block_time') ?? 600; // seconds

        $verify = $this->phoneVerification->where(['phone' => $request['phone'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();

                $errors = [];
                $errors[] = ['code' => 'otp_block_time',
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ];
                return response()->json(['errors' => $errors], 403);
            }

            $verify->delete();

            $temporaryToken = Str::random(40);

            $isUserExist = $this->user->where(['phone' => $request['phone']])->first();
            if (!$isUserExist){
                return response()->json(['temporary_token' => $temporaryToken, 'status' => false], 200);
            }

            $isUserExist->is_phone_verified = 1;
            $isUserExist->save();

            if ($isUserExist->is_block != 0){
                return response()->json(['errors' => [
                    ['code' => 'active', 'message' => translate('This user is not active!')]
                ]], 403);
            }

            $token = $isUserExist->createToken('RestaurantCustomerAuth')->accessToken;

            return response()->json(['token' => $token, 'status' => true], 200);

        }else{
            $verificationdata = DB::table('phone_verifications')->where('phone', $request['phone'])->first();

            if(isset($verificationdata)){
                if(isset($verificationdata->temp_block_time ) && Carbon::parse($verificationdata->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                    $time= $tempBlockTime - Carbon::parse($verificationdata->temp_block_time)->DiffInSeconds();

                    $errors = [];
                    $errors[] = ['code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

                if($verificationdata->is_temp_blocked == 1 && Carbon::parse($verificationdata->updated_at)->DiffInSeconds() >= $tempBlockTime){
                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                            'otp_hit_count' => 0,
                            'is_temp_blocked' => 0,
                            'temp_block_time' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                if($verificationdata->otp_hit_count >= $maxOTPHit &&  Carbon::parse($verificationdata->updated_at)->DiffInSeconds() < $maxOTPHitTime &&  $verificationdata->is_temp_blocked == 0){

                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                            'is_temp_blocked' => 1,
                            'temp_block_time' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $time = $tempBlockTime - Carbon::parse($verificationdata->temp_block_time)->DiffInSeconds();
                    $errors = [];
                    $errors[] = ['code' => 'otp_temp_blocked', 'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

            }

            DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                [
                    'otp_hit_count' => DB::raw('otp_hit_count + 1'),
                    'updated_at' => now(),
                    'temp_block_time' => null,
                ]);
        }

        return response()->json(['errors' => [
            ['code' => 'token', 'message' => translate('OTP is not matched!')]
        ]], 403);
    }

    public function registrationWithOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|max:255',
            'phone' => 'required|string|max:15',
            'referral_code' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request['email']){
            $isEmailExist = $this->user->where(['email' => $request['email']])->first();

            if ($isEmailExist){
                return response()->json(['errors' => [
                    ['code' => 'email', 'message' => translate('This email has already been used in another account!')]
                ]], 403);
            }
        }

        $temporaryToken = Str::random(40);

        if ($request->referral_code){
            $refer_user = $this->user->where(['referral_code' => $request->referral_code])->first();
            if (!$refer_user){
                return response()->json(['errors' => [
                    ['code' => 'referral', 'message' => translate('Invalid Referral Code!')]
                ]], 403);
            }
        }

        $name = $request->name;
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $user = new User();
        $user->f_name = $firstName;
        $user->l_name = $lastName;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = bcrypt(rand(11111111, 99999999));
        $user->temporary_token = $temporaryToken;
        $user->language_code = $request->header('X-localization') ?? 'en';
        $user->is_phone_verified = 1;
        $user->referral_code = Helpers::generate_referer_code();
        $user->referred_by = $refer_user->id ?? null;
        $user->login_medium = 'OTP';
        $user->save();

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;
        return response()->json(['token' => $token], 200);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function customerSocialLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'unique_id' => 'required',
            'email' => 'required_if:medium,google,facebook',
            'medium' => 'required|in:google,facebook,apple',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client = new Client();
        $token = $request['token'];
        $email = $request['email'];
        $uniqueId = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
            } elseif ($request['medium'] == 'facebook') {
                $res = $client->request('GET', 'https://graph.facebook.com/' . $uniqueId . '?access_token=' . $token . '&&fields=name,email');
                $data = json_decode($res->getBody()->getContents(), true);
            }elseif ($request['medium'] == 'apple') {
                $appleLogin = Helpers::get_business_settings('apple_login');
                $teamId = $appleLogin['team_id'];
                $keyId = $appleLogin['key_id'];
                $sub = $appleLogin['client_id'];
                $aud = 'https://appleid.apple.com';
                $iat = strtotime('now');
                $exp = strtotime('+60days');
                $keyContent = file_get_contents('storage/app/public/apple-login/'.$appleLogin['service_file']);

                $token = JWT::encode([
                    'iss' => $teamId,
                    'iat' => $iat,
                    'exp' => $exp,
                    'aud' => $aud,
                    'sub' => $sub,
                ], $keyContent, 'ES256', $keyId);

                $redirectUri = $appleLogin['redirect_url']??'www.example.com/apple-callback';

                $res = Http::asForm()->post('https://appleid.apple.com/auth/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $uniqueId,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $sub,
                    'client_secret' => $token,
                ]);

                $claims = explode('.', $res['id_token'])[1];
                $data = json_decode(base64_decode($claims),true);
            }
        } catch (\Exception $exception) {
            $errors = [];
            $errors[] = ['code' => 'auth-001', 'message' => 'Invalid Token'];
            return response()->json([
                'errors' => $errors
            ], 401);
        }


        if (!isset($claims)) {
            if (strcmp($email, $data['email']) != 0) {
                if ($request['medium'] == 'apple' && (!isset($data['id']) && !isset($data['kid']))) {
                    return response()->json(['error' => translate('messages.email_does_not_match')], 403);
                } else {
                    return response()->json(['error' => translate('messages.email_does_not_match')], 403);
                }
            }
        }

        $existingUser =  $this->user->where('email', $data['email'])->first();
        $temporaryToken = Str::random(40);

        if (!$existingUser){
            return response()->json(['temp_token' => $temporaryToken, 'status' => false], 200);
        }

        if ($existingUser->email_verified_at != null){
            $token = $existingUser->createToken('RestaurantCustomerAuth')->accessToken;
            return response()->json(['token' => $token, 'status' => true], 200);
        }else{
            return response()->json(['user' => $existingUser, 'status' => false], 200);
        }
    }

    public function existingAccountCheck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'user_response' => 'required|in:0,1',
            'medium' => 'required|in:google,facebook,apple',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $this->user->where('email', $request['email'])->first();

        $temporaryToken = Str::random(40);
        if (!$user) {
            return response()->json(['temp_token' => $temporaryToken, 'status' => false], 200);
        }

        if ($request['user_response'] == 1) {
            $user->email_verified_at = now();
            $user->login_medium = $request['medium'];
            $user->save();

            $token = $user->createToken('RestaurantCustomerAuth')->accessToken;
            return response()->json(['token' => $token, 'status' => true], 200);
        }

//        $user->email = null;
//        $user->email_verified_at = null;
//        $user->save();

        return response()->json(['temp_token' => $temporaryToken, 'status' => false], 200);
    }

    public function registrationWithSocialMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:15',
            'referral_code' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $isPhoneExist = $this->user->where(['phone' => $request['phone']])->first();

        if ($isPhoneExist){
            return response()->json(['errors' => [
                ['code' => 'email', 'message' => translate('This phone has already been used in another account!')]
            ]], 403);
        }


        $temporaryToken = Str::random(40);

        if ($request->referral_code){
            $refer_user = $this->user->where(['referral_code' => $request->referral_code])->first();
            if (!$refer_user){
                return response()->json(['errors' => [
                    ['code' => 'referral', 'message' => translate('Invalid Referral Code!')]
                ]], 403);
            }
        }

        $existingUser = $this->user->where('email', $request['email'])->first();
        if ($existingUser){
            $existingUser->email = null;
            $existingUser->email_verified_at = null;
            $existingUser->save();
        }

        $name = $request->name;
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $user = new User();
        $user->f_name = $firstName;
        $user->l_name = $lastName;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = bcrypt(rand(11111111, 99999999));
        $user->temporary_token = $temporaryToken;
        $user->language_code = $request->header('X-localization') ?? 'en';
        $user->email_verified_at = now();
        $user->referral_code = Helpers::generate_referer_code();
        $user->referred_by = $refer_user->id ?? null;
        $user->login_medium = 'social';
        $user->save();

        $phoneVerificationStatus = (int) $this->loginSetup->where(['key' => 'phone_verification'])?->first()->value ?? 0;
        if ($phoneVerificationStatus){
            return response()->json(['temp_token' => $temporaryToken, 'status' => false], 200);
        }

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;
        return response()->json(['token' => $token], 200);
    }


}
