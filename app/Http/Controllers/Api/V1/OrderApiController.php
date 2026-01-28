<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Order;
use App\Models\SalesPerson;

class OrderApiController extends Controller
{
    /**
     * Fetch total order amount by auth_token (from URL param)
     */
    public function getOrderAmountByToken(Request $request)
    {
        try {
            // ✅ Step 1: Validate the parameter
            if (!$request->has('auth_token')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'auth_token parameter is required.'
                ], 400);
            }

            // Read token from URL param
            $token = $request->query('auth_token');

            // ✅ Step 2: Authenticate salesperson
            $salesPerson = SalesPerson::where('auth_token', $token)->first();

            if (!$salesPerson) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid auth token'
                ], 401);
            }

            // ✅ Step 3: Fetch orders linked to this salesperson
            $orders = Order::where('sales_person_id', $salesPerson->id)->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'sales_person' => $salesPerson->name,
                    'sales_person_id' => $salesPerson->id,
                    'total_order_amount' => 0,
                    'message' => 'No orders found for this salesperson.'
                ]);
            }

            // ✅ Step 4: Calculate total safely (handles null values)
            $totalOrderAmount = $orders->sum(function ($order) {
                return (float) ($order->order_amount ?? 0);
            });

            // ✅ Step 5: Build safe order list
            $orderList = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'order_amount' => (float) ($order->order_amount ?? 0),
                    'created_at' => optional($order->created_at)->toDateTimeString(),
                ];
            });

            // ✅ Step 6: Return result
            return response()->json([
                'status' => 'success',
                'sales_person' => $salesPerson->name,
                'sales_person_id' => $salesPerson->id,
                'total_order_amount' => round($totalOrderAmount, 2),
                'orders' => $orderList
            ]);

        } catch (\Exception $e) {
            // ✅ Safe catch block to show real error if something goes wrong
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}