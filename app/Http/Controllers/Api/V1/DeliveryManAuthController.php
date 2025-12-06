<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\DeliveryMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliveryManAuthController extends Controller
{
    // -----------------------------------------------------
    // Send OTP
    // -----------------------------------------------------
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $phone = $request->phone;
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        // store OTP
        DB::table('delivery_man_otps')->insert([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ---- SEND SMS (you can integrate any SMS API here) ----
        // Example: sendOtpSms($phone, $otp);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'otp' => $otp // remove in production
        ]);
    }

    // -----------------------------------------------------
    // Verify OTP + Login
    // -----------------------------------------------------
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'otp' => 'required'
        ]);

        $data = DB::table('delivery_man_otps')
            ->where('phone', $request->phone)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        // check user exists
        $deliveryMan = DeliveryMan::where('phone', $request->phone)->first();

        if (!$deliveryMan) {
            return response()->json([
                'success' => false,
                'message' => 'No delivery man found with this phone number'
            ], 404);
        }

        // generate auth token
        $token = bin2hex(random_bytes(32));

        $deliveryMan->auth_token = $token;
        $deliveryMan->save();

        // delete used otp
        DB::table('delivery_man_otps')
            ->where('phone', $request->phone)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'auth_token' => $token,
            'user' => $deliveryMan
        ]);
    }
}

