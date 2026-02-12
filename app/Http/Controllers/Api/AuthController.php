<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Otp;
use App\Models\SalesPerson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use Illuminate\Support\Facades\Cache;
use App\Model\DeliveryMan;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;




class AuthController extends Controller
{
    /**
     * Step 1: Request OTP for existing Sales Person
     */

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',   // can be phone or email
            'password' => 'required'
        ]);

        // Find sales person by phone or email
        $salesPerson = SalesPerson::where('phone_number', $request->login)
            ->orWhere('email', $request->login)
            ->first();

        if (!$salesPerson) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid phone number or email'
            ], 401);
        }

        // Verify password
        if (!Hash::check($request->password, $salesPerson->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        // Create a new token
        $token = bin2hex(random_bytes(40));

        $salesPerson->auth_token = $token;
        $salesPerson->save();

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'sales_person' => $salesPerson
        ], 200);
    }



    public function getTodayDistance(Request $request)
    {
        $token = $request->header('Authorization'); // Bearer <token>

        if (!$token) {
            return response()->json(['error' => 'Token missing'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Get today's date
        $today = Carbon::today();

        // All stores added today by this salesperson
        $locations = Store::where('sales_person_id', $salesPerson->id)
            ->whereDate('created_at', $today)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('created_at')
            ->get(['latitude', 'longitude']);

        if ($locations->count() < 2) {
            return response()->json([
                'sales_person' => $salesPerson->name,
                'total_distance_km' => 0,
                'message' => 'Not enough location points for today'
            ]);
        }

        // Calculate distance
        $totalDistance = 0;

        for ($i = 0; $i < $locations->count() - 1; $i++) {
            $totalDistance += $this->haversineDistance(
                $locations[$i]->latitude,
                $locations[$i]->longitude,
                $locations[$i + 1]->latitude,
                $locations[$i + 1]->longitude
            );
        }

        return response()->json([
            'sales_person' => $salesPerson->name,
            'total_distance_km' => round($totalDistance, 2),
            'points_count' => $locations->count(),
        ]);
    }
    
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2 +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }


    public function nearbyStores(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string',
        ]);

        // ✅ Step 1: Authenticate salesperson
        $salesPerson = SalesPerson::where('auth_token', $request->auth_token)->first();

        if (!$salesPerson) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid auth token'
            ], 401);
        }

        // ✅ Step 2: Get today's date
        $today = Carbon::today();

        // ✅ Step 3: Fetch all stores added today for this salesperson
        $stores = Store::where('sales_person_id', $salesPerson->id)
            ->whereDate('created_at', $today)
            ->get();

        if ($stores->count() < 2) {
            return response()->json([
                'status' => 'error',
                'message' => "At least two stores required to calculate distance for today's date.",
                'total_stores_today' => $stores->count(),
            ], 400);
        }

        // ✅ Step 4: Compute pairwise distances (store-to-store)
        $distances = [];
        foreach ($stores as $i => $storeA) {
            foreach ($stores as $j => $storeB) {
                if ($i >= $j)
                    continue; // avoid duplicate & self-comparison

                $distance = $this->calculateDistance(
                    floatval($storeA->latitude),
                    floatval($storeA->longitude),
                    floatval($storeB->latitude),
                    floatval($storeB->longitude)
                );

                $distances[] = [
                    'from_store' => $storeA->store_name,
                    'to_store' => $storeB->store_name,
                    'distance' => $distance . ' km',
                    'date' => $today->toDateString(),
                ];
            }
        }

        // ✅ Step 5: Response
        return response()->json([
            'status' => 'success',
            'sales_person' => $salesPerson->name,
            'store_count_today' => $stores->count(),
            'date' => $today->toDateString(),
            'distances' => $distances,
        ]);
    }

    // ✅ Haversine Distance Formula
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        // Check if phone number exists in sales_people table
        $salesPerson = SalesPerson::where('phone_number', $request->phone_number)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not registered as Sales Person.'
            ], 404);
        }

        // Generate dummy OTP
        $otpCode = rand(1000, 9999); // 4 digit OTP

        // Store OTP in DB
        $otp = Otp::create([
            'phone_number' => $request->phone_number,
            'otp' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP generated successfully',
            'otp' => $otpCode, // ⚠️ only for testing, remove in production
            'sales_person' => [
                'id' => $salesPerson->id,
                'name' => $salesPerson->name,
                'email' => $salesPerson->email,
            ],
        ]);
    }

    /**
     * Step 2: Verify OTP
     */


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp' => 'required|string',
        ]);

        $otp = Otp::where('phone_number', $request->phone_number)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $salesPerson = SalesPerson::where('phone_number', $request->phone_number)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Sales Person not found'
            ], 404);
        }

        // Generate token (like login)
        $token = Str::random(120);

        $salesPerson->auth_token = $token;
        $salesPerson->updated_at = now();
        $salesPerson->save();

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'sales_person' => [
                'id' => $salesPerson->id,
                'name' => $salesPerson->name,
                'email' => $salesPerson->email,
                'phone_number' => $salesPerson->phone_number,
            ],
            'token' => $token
        ]);
    }

    public function newCustomerApi(Request $request)
    {
        // Check SalesPerson auth
        $token = $request->header('Authorization'); // Token comes in header

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.'
            ], 401);
        }

        // ✅ from here onwards, only verified SalesPerson can create
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = new User();
        $customer->f_name = $request->f_name;
        $customer->l_name = $request->l_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->password = Hash::make('12345678');
        $customer->created_by = $salesPerson->id;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => translate('Customer added successfully!'),
            'customer' => [
                'id' => $customer->id,
                'f_name' => $customer->f_name,
                'l_name' => $customer->l_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'added_by' => [
                'id' => $salesPerson->id,
                'name' => $salesPerson->name,
            ]
        ], 201);
    }

    public function salesPersonLogout(Request $request)
    {
        // Get token from header
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.'
            ], 401);
        }

        // Invalidate token
        $salesPerson->auth_token = null;
        $salesPerson->updated_at = now();
        $salesPerson->save();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function stores(Request $request)
    {
        // 🔐 Get token from header
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.'
            ], 401);
        }

        // ✅ Validation (added gst_number)
        $request->validate([
            'store_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'taluk' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'address' => 'required|string|max:500',
            'phone_number' => 'required|string|max:15',
            'alternate_number' => 'nullable|string|max:15',
            'gst_number' => 'nullable|string|max:20', // ✅ GST field
            'landmark' => 'nullable|string|max:255',
            'route_name' => 'required|string|max:255',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'store_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // 📞 Normalize phone numbers
        $phone = $request->phone_number;
        if ($phone && !str_starts_with($phone, '+91')) {
            $phone = '+91' . ltrim($phone, '0');
        }

        $alternate = $request->alternate_number;
        if ($alternate && !str_starts_with($alternate, '+91')) {
            $alternate = '+91' . ltrim($alternate, '0');
        }
/*
        // 🌍 Latitude & Longitude
        $latitude = null;
        $longitude = null;

        if ($request->address) {
            $apiKey = env('GOOGLE_MAPS_API_KEY');
            $url = "https://maps.googleapis.com/maps/api/geocode/json";

            $response = Http::get($url, [
                'address' => $request->address,
                'key' => $apiKey,
            ]);

            $geoData = $response->json();

            if (!empty($geoData['results'][0]['geometry']['location'])) {
                $latitude = $geoData['results'][0]['geometry']['location']['lat'];
                $longitude = $geoData['results'][0]['geometry']['location']['lng'];
            }
        }
*/
        // 📌 Prepare data
       // $data = $request->all();

            $data = $request->only([
            'store_name',
            'customer_name',
            'street_address',
            'area',
            'city',
            'taluk',
            'district',
            'state',
            'pincode',
            'address',
            'phone_number',
            'alternate_number',
            'gst_number',
            'landmark',
        ]);

        $data['phone_number'] = $phone;
        $data['alternate_number'] = $alternate;
        $data['latitude'] = $request->latitude;
        $data['longitude'] = $request->longitude;
        $data['sales_person_id'] = $salesPerson->id;
        $data['route_name'] = $request->route_name;
        $data['full_address'] = implode(', ', array_filter([
            $data['street_address'] ?? null,
            $data['area'] ?? null,
            $data['city'] ?? null,
            $data['taluk'] ?? null,
            $data['district'] ?? null,
            $data['state'] ?? 'Karnataka',
            $data['pincode'] ?? null,
        ]));

        if ($request->hasFile('store_photo')) {
            $data['store_photo'] = $request->file('store_photo')
                ->store('stores', 'public');
        }

        // 🏪 Save Store
        $store = Store::create($data);
        \Log::info('STORE LAT LNG', [
    'lat' => $request->latitude,
    'lng' => $request->longitude,
]);


        return response()->json([
            'success' => true,
            'message' => 'Store created successfully',
            'data' => $store
        ]);
    }


public function myStores(Request $request)
{
    // 🔐 Get token from header
    $token = $request->header('Authorization');
    if (!$token) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Missing token.'
        ], 401);
    }

    $salesPerson = SalesPerson::where('auth_token', $token)->first();

    if (!$salesPerson) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Invalid token.'
        ], 401);
    }

    // ✅ FETCH ALL STORES (no salesman filter)
   // $stores = Store::latest()->get();
    $stores = Store::select(
        'id',
        'store_name',
        'customer_name',
        'address',
        'full_address', 
        'landmark',
        'phone_number',
        'latitude',
        'longitude',
        'sales_person_id',
        'route_name',
        'store_photo',
        'created_at'
    )->latest()->get();

    // ➕ Add arrear_amount for each store
    foreach ($stores as $store) {
        $arrear = DB::table('orders')
            ->where('store_id', $store->id)
            ->whereNotIn('order_status', ['cancelled', 'failed'])
            ->selectRaw('
                COALESCE(SUM(order_amount + COALESCE(total_tax_amount,0) + COALESCE(delivery_charge,0) - COALESCE(coupon_discount_amount,0)),0) as total_order,
                COALESCE(SUM(paid_amount),0) as total_paid
            ')
            ->first();

        $store->arrear_amount = max(($arrear->total_order ?? 0) - ($arrear->total_paid ?? 0), 0);
    }

    return response()->json([
        'success' => true,
        'message' => 'Stores fetched successfully',
        'data' => $stores
    ]);
}

   public function getAllOrdersArrear(Request $request): JsonResponse
{
// 🔐 Get token from header
$token = $request->header('Authorization');


if (!$token) {
    return response()->json([
        'success' => false,
        'message' => 'Unauthorized. Missing token.'
    ], 401);
}

$salesPerson = SalesPerson::where('auth_token', $token)->first();

if (!$salesPerson) {
    return response()->json([
        'success' => false,
        'message' => 'Unauthorized. Invalid token.'
    ], 401);
}

$orders = DB::table('orders')
    ->whereNotIn('order_status', ['cancelled', 'failed'])
    ->selectRaw('
        id as order_id,
        order_amount,
        COALESCE(paid_amount, 0) as total_paid,
        (order_amount + COALESCE(total_tax_amount,0) - COALESCE(paid_amount,0)) as arrear_balance
    ')
    ->orderByDesc('id')
    ->get();

return response()->json([
    'success' => true,
    'orders_count' => $orders->count(),
    'orders' => $orders
]);


}

    // 📌 Fetch profile by auth token
    public function saleprofile(Request $request)
    {
        // 🔐 Get token from header
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        // 🔍 Find salesperson by token
        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.'
            ], 401);
        }

        // 🎯 Return profile data
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $salesPerson->id,
                'name' => $salesPerson->name,
                'phone_number' => $salesPerson->phone_number,
                'email' => $salesPerson->email,
                'id_proof' => asset('storage/' . $salesPerson->id_proof),
                'person_photo' => asset('storage/' . $salesPerson->person_photo),

                'address' => $salesPerson->address,
                'emergency_contact_name' => $salesPerson->emergency_contact_name,
                'emergency_contact_number' => $salesPerson->emergency_contact_number,
                'created_at' => $salesPerson->created_at,
                'updated_at' => $salesPerson->updated_at,
            ]
        ], 200);
    }

// App PlaceOrder :Pavan

  public function orders(Request $request)
{
    Log::info('Sales order request received', ['payload' => $request->all()]);

    /* ================= AUTH ================= */
    $token = $request->header('Authorization');
    $salesPerson = SalesPerson::where('auth_token', $token)->first();

    if (!$salesPerson) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Invalid Sales Person token.'
        ], 401);
    }

    /* ================= VALIDATION ================= */
    $validator = Validator::make($request->all(), [
        'store_id' => 'required|exists:stores,id',
        'order_details' => 'required|array|min:1',
        'order_details.*.product_id' => 'required|exists:products,id',
        'order_details.*.quantity' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    DB::beginTransaction();

    try {
        /* ================= STORE → BRANCH RESOLUTION ================= */
        $store  = Store::lockForUpdate()->findOrFail($request->store_id);
        $branch = $store->branch; // STRING

        Log::info('Branch resolved from store', [
            'store_id' => $store->id,
            'branch' => $branch
        ]);

        /* ================= ORDER CREATE ================= */
        $order = Order::create([
            'user_id' => null,
            'is_guest' => 0,
            'order_amount' => 0,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'order_type' => 'sales_person',
            'branch' => $branch,
            'date' => now()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'sales_person_id' => $salesPerson->id,
            'store_id' => $store->id,
            'checked' => 1,
            'delivery_charge' => 0,
            'extra_discount' => 0,
        ]);

        Log::info('Sales order created', ['order_id' => $order->id]);

        /* ================= AUDIT LOG (ORDER) ================= */
        DB::table('audit_logs')->insert([
            'user_id' => $salesPerson->id,
            'branch' => $branch,
            'action' => 'sales_order_created',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'new_values' => json_encode($order->toArray()),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $subTotal = 0;
        $totalTax = 0;

        /* ================= ORDER DETAILS + FIFO INVENTORY ================= */
        foreach ($request->order_details as $detail) {

            $product = Product::lockForUpdate()->findOrFail($detail['product_id']);
            $qty = (int) $detail['quantity'];

            /* ---- FIFO STOCK CHECK ---- */
            $availableStock = DB::table('inventory_transactions')
                ->where('product_id', $product->id)
                ->where('branch', $branch)
                ->where('remaining_qty', '>', 0)
                ->lockForUpdate()
                ->sum('remaining_qty');

            if ($availableStock < $qty) {
                Log::error('Insufficient stock', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'branch' => $branch,
                    'requested_qty' => $qty,
                    'available_qty' => $availableStock,
                ]);

                throw new \Exception("Insufficient stock for {$product->name}");
            }

            /* ---- DISCOUNT ---- */
            $discount = ($product->discount_type === 'percent')
                ? ($product->price * $product->discount / 100)
                : ($product->discount ?? 0);

            $priceAfterDiscount = $product->price - $discount;

            /* ---- TAX ---- */
            $taxAmount = ($product->tax_type === 'percent')
                ? ($priceAfterDiscount * $product->tax / 100)
                : ($product->tax ?? 0);

            $lineTotal = $priceAfterDiscount * $qty;
            $subTotal += $lineTotal;
            $totalTax += ($taxAmount * $qty);

            /* ---- ORDER DETAIL ---- */
            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'price' => $product->price,
                'quantity' => $qty,
                'discount_on_product' => $discount,
                'discount_type' => $product->discount_type ?? 'amount',
                'tax_amount' => $taxAmount,
              //  'product_details' => json_encode($product),
                'product_details' => [
    'id' => $product->id,
    'name' => $product->name,
    'image' => json_decode($product->image, true),
    'unit' => $product->unit,
    'price' => $product->price,
    'tax' => $product->tax,
    'tax_type' => $product->tax_type,
],

                'unit' => $product->unit ?? 'pc',
                'vat_status' => $product->tax_type ?? 'excluded',
                'variation' => $product->variations ?? '[]',
            ]);

            /* ---- FIFO INVENTORY OUT ---- */
            $remaining = $qty;

            $batches = DB::table('inventory_transactions')
                ->where('product_id', $product->id)
                ->where('branch', $branch)
                ->where('remaining_qty', '>', 0)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $take = min($remaining, $batch->remaining_qty);

                DB::table('inventory_transactions')->insert([
                    'product_id' => $product->id,
                    'branch' => $branch,
                    'type' => 'OUT',
                    'quantity' => $take,
                    'unit_price' => $batch->unit_price,
                    'total_value' => $take * $batch->unit_price,
                    'remaining_qty' => 0,
                    'reference_type' => 'sales_order',
                    'reference_id' => $order->id,
                    'order_id' => $order->id,
                    'batch_id' => $batch->batch_id,
                    'created_at' => now(),
                ]);

                DB::table('inventory_transactions')
                    ->where('id', $batch->id)
                    ->update([
                        'remaining_qty' => $batch->remaining_qty - $take
                    ]);

                $remaining -= $take;
            }
        }

        /* ================= UPDATE ORDER TOTAL ================= */
        $order->update([
            'order_amount' => $subTotal,
            'total_tax_amount' => $totalTax,
        ]);

        /* ================= GST LEDGER (OUTPUT) ================= */
        if ($totalTax > 0) {
            DB::table('gst_ledgers')->insert([
                'branch' => $branch,
                'type' => 'OUTPUT',
                'taxable_amount' => $subTotal,
                'gst_amount' => $totalTax,
                'reference_type' => 'sales_order',
                'reference_id' => $order->id,
                'created_at' => now(),
            ]);
        }

        /* ================= STORE LEDGER (RECEIVABLE) ================= */
        DB::table('store_ledgers')->insert([
            'store_id' => $store->id,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'debit' => $subTotal + $totalTax,
            'credit' => 0,
            'balance_type' => 'receivable',
            'remarks' => 'Sales order created',
            'created_at' => now(),
        ]);

        DB::commit();

        Log::info('Sales order completed', ['order_id' => $order->id]);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'order' => $order->load('salesPerson', 'details')
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Sales order failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



    public function listCustomers(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        $salesPerson = SalesPerson::with('customers')->where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'salesperson' => [
                'id' => $salesPerson->id,
                'name' => $salesPerson->name,
                'email' => $salesPerson->email,
            ],
            'customers' => $salesPerson->customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'f_name' => $customer->f_name,
                    'l_name' => $customer->l_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ];
            })
        ]);
    }

    public function allDeliveryMen(Request $request)
    {
        // Check Authorization Token
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        // Validate Sales Person Token
        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.'
            ], 401);
        }

        // Fetch all delivery men
        $deliveryMen = DeliveryMan::select(
            'id',
            'f_name',
            'l_name',
            'phone',
            'email',
            'identity_number',
            'identity_type',
            'identity_image',
            'image',
            'is_active',
            'branch_id',
            'application_status',
            'language_code',
            'created_at'
        )->get();

        return response()->json([
            'success' => true,
            'delivery_men' => $deliveryMen
        ]);
    }

    public function totalOrders(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.'
            ], 401);
        }

        $totalOrders = Order::where('sales_person_id', $salesPerson->id)->count();

        $orders = Order::where('sales_person_id', $salesPerson->id)
            ->select('id', 'store_id', 'order_amount', 'order_status', 'payment_status', 'created_at', 'delivery_man_id')
            ->with([
                'orderDetails:id,order_id,product_id,price,quantity,tax_amount,discount_on_product,unit,product_details'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'salesperson' => [
                'id' => $salesPerson->id,
                'name' => $salesPerson->name,
                'email' => $salesPerson->email,
            ],
            'total_orders' => $totalOrders,
            'orders' => $orders
        ]);
    }





    // Get all conversations of sales_person
    public function conversations(Request $request)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        // Find salesperson using auth_token
        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        $conversations = \App\Model\Conversation::where('sales_person_id', $salesPerson->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    // Send message (chat)
    public function sendMessage(Request $request)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing token.'
            ], 401);
        }

        // Find salesperson using auth_token
        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        $request->validate([
            'message' => 'nullable|string',
            'image.*' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // each file
        ]);

        $imagePaths = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $file) {
                $path = $file->store('conversation', 'public');
                $imagePaths[] = asset('storage/' . $path);
            }
        }

        $conversation = \App\Model\Conversation::create([
            'sales_person_id' => $salesPerson->id,
            'message' => $request->message,
            'reply' => null,
            'image' => $imagePaths ? json_encode($imagePaths) : null,
            'is_reply' => 0,
            'checked' => 1,
        ]);

        return response()->json(['success' => true, 'data' => $conversation]);
    }
    public function reorder(Request $request, $orderId)
    {
        // 🔐 Authenticate Sales Person by token
        $token = $request->header('Authorization');
        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid Sales Person token.'
            ], 401);
        }

        // ✅ Validate store_id input
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Store ID provided.'
            ], 422);
        }

        // ✅ Find the old order
        $oldOrder = Order::with('details')->where('id', $orderId)
            ->where('sales_person_id', $salesPerson->id)
            ->first();

        if (!$oldOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or not owned by this sales person.'
            ], 404);
        }

        // ✅ Create New Order with new store_id
        $newOrder = Order::create([
            'user_id' => null,
            'is_guest' => 0,
            'order_amount' => 0,
            'coupon_discount_amount' => 0,
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
            'total_tax_amount' => 0,
            'payment_method' => 'cash_on_delivery',
            'checked' => 1,
            'delivery_charge' => 0,
            'order_type' => 'sales_person',
            'branch_id' => 1,
            'date' => Carbon::now()->toDateString(),
            'delivery_date' => Carbon::now()->toDateString(),
            'extra_discount' => 0,
            'sales_person_id' => $salesPerson->id,
            'store_id' => $request->store_id, // <-- use new store_id
        ]);

        $subTotal = 0;
        $totalTax = 0;

        // ✅ Copy old order details
        foreach ($oldOrder->details as $oldDetail) {
            $product = Product::find($oldDetail->product_id);
            if (!$product)
                continue;

            $quantity = $oldDetail->quantity;

            // Discount
            $discount = $product->discount_type === 'percent'
                ? ($product->price * $product->discount / 100)
                : $product->discount;

            $priceAfterDiscount = $product->price - $discount;

            // Tax
            $taxAmount = $product->tax_type === 'percent'
                ? ($priceAfterDiscount * $product->tax / 100)
                : $product->tax;

            $subTotal += ($priceAfterDiscount * $quantity);
            $totalTax += ($taxAmount * $quantity);

            $productDetails = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image' => json_decode($product->image, true),
                'attributes' => json_decode($product->attributes, true),
                'category_ids' => json_decode($product->category_ids, true),
                'choice_options' => json_decode($product->choice_options, true),
            ];

            OrderDetail::create([
                'order_id' => $newOrder->id,
                'product_id' => $product->id,
                'price' => $product->price,
                'quantity' => $quantity,
                'discount_on_product' => $product->discount ?? 0,
                'discount_type' => $product->discount_type ?? 'amount',
                'tax_amount' => $taxAmount,
                'product_details' => $productDetails,
                'unit' => $product->unit ?? 'pc',
                'vat_status' => $product->tax_type ?? 'excluded',
                'variation' => $product->variations ?? '[]',
            ]);
        }

        // ✅ Update totals
        $newOrder->update([
            'order_amount' => $subTotal,
            'total_tax_amount' => $totalTax,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reorder placed successfully',
            'order' => $newOrder->load('salesPerson', 'details')
        ]);
    }


    /**
     * Authenticate sales person by token
     */
    protected function authenticateSalesPerson(Request $request)
    {
        // Directly get token from header
        $token = $request->header('Authorization');

        $salesPerson = SalesPerson::where('auth_token', $token)->first();

        return $salesPerson;
    }


    /**
     * Add product to cart
     */
    public function addToCart(Request $request)
    {
        $salesPerson = $this->authenticateSalesPerson($request);
        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid Sales Person token.'
            ], 401);
        }

        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $cartKey = 'cart_' . $salesPerson->id;
        $cart = Cache::get($cartKey, []);

        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['quantity'] += $request->quantity;
        } else {
            $cart[$request->product_id] = [
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ];
        }

        // Store cart in cache for 24 hours
        Cache::put($cartKey, $cart, now()->addHours(24));

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'cart' => array_values($cart),
        ]);
    }

    /**
     * Remove product from cart
     */
    public function removeFromCart(Request $request)
    {
        $salesPerson = $this->authenticateSalesPerson($request);
        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid Sales Person token.'
            ], 401);
        }

        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1', // optional quantity
        ]);

        $cartKey = 'cart_' . $salesPerson->id;
        $cart = Cache::get($cartKey, []);

        if (isset($cart[$request->product_id])) {
            if ($request->quantity) {
                // Reduce quantity
                $cart[$request->product_id]['quantity'] -= $request->quantity;
                // Remove if quantity <= 0
                if ($cart[$request->product_id]['quantity'] <= 0) {
                    unset($cart[$request->product_id]);
                }
            } else {
                // Remove entire product if no quantity is specified
                unset($cart[$request->product_id]);
            }

            // Update cache
            Cache::put($cartKey, $cart, now()->addHours(24));
        }

        return response()->json([
            'success' => true,
            'message' => 'Product updated in cart',
            'cart' => array_values($cart),
        ]);
    }


    /**
     * Get all cart items
     */
    public function getCartItems(Request $request)
    {
        $salesPerson = $this->authenticateSalesPerson($request);
        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid Sales Person token.'
            ], 401);
        }

        $cartKey = 'cart_' . $salesPerson->id;
        $cart = Cache::get($cartKey, []);

        return response()->json([
            'success' => true,
            'cart' => array_values($cart),
        ]);
    }

    public function clearCart(Request $request)
    {
        $salesPerson = $this->authenticateSalesPerson($request);

        if (!$salesPerson) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid Sales Person token.'
            ], 401);
        }

        // Clear the cart cache for this salesperson
        $cartKey = 'cart_' . $salesPerson->id;
        Cache::forget($cartKey);

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
        ]);
    }

}




