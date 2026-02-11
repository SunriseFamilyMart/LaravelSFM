<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreOtp;
use App\Models\SalesPerson;
use App\Models\Order;
use App\Models\OrderPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Store Self Registration + Password+OTP login.
 * IMPORTANT: Store cannot login until admin approves and assigns a salesperson.
 */
class StoreAuthController extends Controller
{
    /**
     * POST /api/v1/store/register
     * Create a store in PENDING state. Admin must approve & assign salesperson.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'store_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone_number' => 'required|string|max:15',
            'alternate_number' => 'nullable|string|max:15',
            'gst_number' => 'nullable|string|max:20',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'password' => 'required|string|min:6|max:60',
            'store_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Normalize phone to match existing pattern (+91)
        $phone = $data['phone_number'];
        if ($phone && !str_starts_with($phone, '+91')) {
            $phone = '+91' . ltrim($phone, '0');
        }
        $data['phone_number'] = $phone;

        if (!empty($data['alternate_number'])) {
            $alt = $data['alternate_number'];
            if ($alt && !str_starts_with($alt, '+91')) {
                $alt = '+91' . ltrim($alt, '0');
            }
            $data['alternate_number'] = $alt;
        }

        // Prevent duplicates by phone
        $exists = Store::where('phone_number', $data['phone_number'])->first();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Store phone already registered. Please contact admin if you cannot login.',
            ], 409);
        }

        // 🌍 Geocoding: Get latitude/longitude from address if not provided
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $apiKey = env('GOOGLE_MAPS_API_KEY');
            if ($apiKey && !empty($data['address'])) {
                try {
                    $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $data['address'],
                        'key' => $apiKey,
                    ]);
                    
                    $geoData = $response->json();
                    if (!empty($geoData['results'][0]['geometry']['location'])) {
                        $data['latitude'] = $geoData['results'][0]['geometry']['location']['lat'];
                        $data['longitude'] = $geoData['results'][0]['geometry']['location']['lng'];
                    }
                } catch (\Exception $e) {
                    // Geocoding failed, continue without coordinates
                    \Log::warning('Geocoding failed for store registration: ' . $e->getMessage());
                }
            }
        }

        $data['password'] = Hash::make($data['password']);
        $data['registration_source'] = 'self';
        $data['approval_status'] = 'pending';
        $data['can_login'] = false;
        $data['sales_person_id'] = null;

        if ($request->hasFile('store_photo')) {
            $data['store_photo'] = $request->file('store_photo')->store('stores', 'public');
        }

        $store = Store::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted. Await admin approval and salesperson assignment.',
            'store_id' => $store->id,
            'approval_status' => $store->approval_status,
        ], 201);
    }

    /**
     * POST /api/v1/store/login
     * Step 1: verify phone + password, then send OTP.
     */
    public function login(Request $request)
{
    $payload = $request->validate([
        'phone_number' => 'required|string|max:15',
        'password' => 'required|string',
        'skip_otp' => 'nullable|boolean', // 👈 NEW
    ]);

    $phone = $payload['phone_number'];
    if ($phone && !str_starts_with($phone, '+91')) {
        $phone = '+91' . ltrim($phone, '0');
    }

    $store = Store::where('phone_number', $phone)->first();
    if (!$store) {
        return response()->json(['success' => false, 'message' => 'Store not found.'], 404);
    }

    // Approval checks
    $approved = ($store->approval_status ?? 'pending') === 'approved';
    $assigned = !empty($store->sales_person_id);
    $canLogin = (bool) ($store->can_login ?? false);

    if (!$approved || !$canLogin || !$assigned) {
        return response()->json([
            'success' => false,
            'message' => 'Account pending approval.',
        ], 403);
    }

    if (!Hash::check($payload['password'], $store->password ?? '')) {
        return response()->json(['success' => false, 'message' => 'Invalid password.'], 401);
    }

    // ✅ PASSWORD-ONLY LOGIN
    if (!empty($payload['skip_otp']) && $payload['skip_otp'] === true) {
        $token = bin2hex(random_bytes(40));
        $store->auth_token = $token;
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'store' => $store,
        ], 200);
    }

    // 🔁 OTP FLOW (unchanged)
    $otpCode = (string) random_int(1000, 9999);
    StoreOtp::create([
        'phone_number' => $phone,
        'otp' => $otpCode,
        'expires_at' => Carbon::now()->addMinutes(5),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'OTP sent successfully',
        'otp' => $otpCode, // testing only
        'expires_in_sec' => 300,
    ], 200);
}


    /**
     * POST /api/v1/store/verify-otp
     * Step 2: verify OTP and issue auth token.
     */
    public function verifyOtp(Request $request)
    {
        $payload = $request->validate([
            'phone_number' => 'required|string|max:15',
            'otp' => 'required|string|max:6',
        ]);

        $phone = $payload['phone_number'];
        if ($phone && !str_starts_with($phone, '+91')) {
            $phone = '+91' . ltrim($phone, '0');
        }

        $otp = StoreOtp::where('phone_number', $phone)
            ->where('otp', $payload['otp'])
            ->orderByDesc('id')
            ->first();

        if (!$otp) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.'], 401);
        }

        if ($otp->expires_at && Carbon::parse($otp->expires_at)->isPast()) {
            return response()->json(['success' => false, 'message' => 'OTP expired.'], 401);
        }

        $store = Store::where('phone_number', $phone)->first();
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Store not found.'], 404);
        }

        // Re-check active
        $approved = ($store->approval_status ?? 'pending') === 'approved';
        $assigned = !empty($store->sales_person_id);
        $canLogin = (bool) ($store->can_login ?? false);
        if (!$approved || !$canLogin || !$assigned) {
            return response()->json([
                'success' => false,
                'message' => 'Account pending approval. Please wait for admin approval and salesperson assignment.',
                'approval_status' => $store->approval_status,
                'assigned' => !empty($store->sales_person_id),
                'can_login' => (bool) ($store->can_login ?? false),
            ], 403);
        }

        $token = bin2hex(random_bytes(40));
        $store->auth_token = $token;
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'store' => $store,
        ], 200);
    }

    /**
     * GET /api/v1/store/me
     * Return the authenticated store profile with:
     * - Sales person details (name, phone)
     * - Arrear/outstanding amount
     */
    public function me(Request $request)
    {
        /** @var \App\Models\Store|null $store */
        $store = $request->attributes->get('auth_store');

        if (!$store) {
            $token = $request->header('Authorization') ?: $request->header('X-Store-Token');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Missing token.'], 401);
            }
            $token = trim(str_replace('Bearer ', '', $token));
            $store = Store::where('auth_token', $token)->first();
            if (!$store) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid token.'], 401);
            }
        }

        // Get sales person details (name and phone)
        $salesPersonData = null;
        if ($store->sales_person_id) {
            $salesPerson = SalesPerson::find($store->sales_person_id);
            if ($salesPerson) {
                $salesPersonData = [
                    'id' => $salesPerson->id,
                    'name' => $salesPerson->name,
                    'phone_number' => $salesPerson->phone_number,
                ];
            }
        }

        // Calculate arrear (outstanding amount)
        $arrearData = $this->calculateStoreArrear($store->id);

        return response()->json([
            'success' => true,
            'store' => $store,
            'sales_person' => $salesPersonData,
            'arrear' => $arrearData,
        ], 200);
    }

    /**
     * GET /api/v1/store/arrear
     * Get arrear/outstanding amount for the store
     */
    public function getArrear(Request $request)
    {
        /** @var \App\Models\Store|null $store */
        $store = $request->attributes->get('auth_store');

        if (!$store) {
            $token = $request->header('Authorization') ?: $request->header('X-Store-Token');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Missing token.'], 401);
            }
            $token = trim(str_replace('Bearer ', '', $token));
            $store = Store::where('auth_token', $token)->first();
            if (!$store) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid token.'], 401);
            }
        }

        $arrearData = $this->calculateStoreArrear($store->id);

        return response()->json([
            'success' => true,
            'data' => $arrearData,
        ], 200);
    }

    /**
     * Calculate store arrear (outstanding amount)
     * Arrear = Total Order Amount - Total Paid Amount
     * 
     * Uses order_payments table with payment_status = 'complete'
     */
    private function calculateStoreArrear($storeId)
    {
        // Get all orders for this store that are not cancelled/returned/failed
        $orders = Order::where('store_id', $storeId)
            ->whereNotIn('order_status', ['cancelled', 'failed', 'returned'])
            ->get();

        $totalOrderAmount = 0;
        $totalPaidAmount = 0;
        $unpaidOrders = [];

        foreach ($orders as $order) {
            // Calculate order total
            $orderTotal = ($order->order_amount ?? 0) + 
                          ($order->delivery_charge ?? 0) + 
                          ($order->total_tax_amount ?? 0) - 
                          ($order->coupon_discount_amount ?? 0);
            
            $totalOrderAmount += $orderTotal;

            // Get paid amount for this order from order_payments table
            // payment_status = 'complete' means payment is done
            $paidAmount = OrderPayment::where('order_id', $order->id)
                ->where('payment_status', 'complete')
                ->sum('amount');

            // Also check if order itself is marked as paid
            if ($order->payment_status === 'paid' && $paidAmount < $orderTotal) {
                $paidAmount = $orderTotal;
            }

            $totalPaidAmount += $paidAmount;

            // Track unpaid orders
            $dueAmount = $orderTotal - $paidAmount;
            if ($dueAmount > 0.01) {
                $unpaidOrders[] = [
                    'order_id' => $order->id,
                    'order_total' => round($orderTotal, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'due_amount' => round($dueAmount, 2),
                    'order_date' => $order->created_at ? $order->created_at->format('Y-m-d') : null,
                    'order_status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                ];
            }
        }

        $totalArrear = $totalOrderAmount - $totalPaidAmount;
        if ($totalArrear < 0) {
            $totalArrear = 0;
        }

        return [
            'total_arrear' => round($totalArrear, 2),
            'total_order_amount' => round($totalOrderAmount, 2),
            'total_paid_amount' => round($totalPaidAmount, 2),
            'unpaid_orders_count' => count($unpaidOrders),
            'unpaid_orders' => $unpaidOrders,
        ];
    }

    /**
     * POST /api/v1/store/logout
     */
    public function logout(Request $request)
    {
        /** @var \App\Models\Store|null $store */
        $store = $request->attributes->get('auth_store');

        if (!$store) {
            $token = $request->header('Authorization') ?: $request->header('X-Store-Token');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token missing'], 401);
            }
            $token = trim(str_replace('Bearer ', '', $token));
            $store = Store::where('auth_token', $token)->first();
        }

        if ($store) {
            $store->auth_token = null;
            $store->save();
        }

        return response()->json(['success' => true, 'message' => 'Logged out'], 200);
    }

    /**
     * POST /api/v1/store/change-password
     * Authenticated store can change their password.
     */
    public function changePassword(Request $request)
    {
        /** @var \App\Models\Store|null $store */
        $store = $request->attributes->get('auth_store');

        if (!$store) {
            $token = $request->header('Authorization') ?: $request->header('X-Store-Token');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Missing token.'], 401);
            }
            $token = trim(str_replace('Bearer ', '', $token));
            $store = Store::where('auth_token', $token)->first();
            if (!$store) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid token.'], 401);
            }
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|different:current_password',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if (empty($store->password) || !Hash::check($request->current_password, $store->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 401);
        }

        $store->password = Hash::make($request->new_password);
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ], 200);
    }
}