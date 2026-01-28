<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Store self app order placement (does NOT change existing Sales-person flow).
 */
class StoreOrderController extends Controller
{

/**
 * ✅ List orders for authenticated store (GET /store/orders)
 * Keeps existing POST /store/orders (place) intact.
 */
public function index(Request $request)
{
    $store = $request->attributes->get('auth_store');

    if (!$store) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
    }

    $perPage = (int) ($request->get('per_page', 20));
    if ($perPage < 1) $perPage = 20;
    if ($perPage > 100) $perPage = 100;

    $q = Order::query()->where('store_id', $store->id)->orderByDesc('id');

    // Optional filters (safe - no breaking changes)
    if ($request->filled('order_status')) {
        $q->where('order_status', $request->get('order_status'));
    }
    if ($request->filled('payment_status')) {
        $q->where('payment_status', $request->get('payment_status'));
    }

    // date range: from=YYYY-MM-DD, to=YYYY-MM-DD
    if ($request->filled('from')) {
        $q->whereDate('created_at', '>=', $request->get('from'));
    }
    if ($request->filled('to')) {
        $q->whereDate('created_at', '<=', $request->get('to'));
    }

    $orders = $q->with(['salesPerson', 'details.product'])->paginate($perPage);

    return response()->json([
        'success' => true,
        'orders' => $orders,
    ], 200);
}
    /**
     * POST /api/v1/store/orders
     * Body: { order_details: [ {product_id, quantity} ] }
     */
    public function place(Request $request)
    {
        /** @var \App\Models\Store $store */
        $store = $request->attributes->get('auth_store');

        $validator = Validator::make($request->all(), [
            'order_details' => 'required|array|min:1',
            'order_details.*.product_id' => 'required|exists:products,id',
            'order_details.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Create Order linked to store and assigned salesperson
        $order = Order::create([
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
            'order_type' => 'store_self',
            'branch_id' => 1,
            'date' => Carbon::now()->toDateString(),
            'delivery_date' => Carbon::now()->toDateString(),
            'extra_discount' => 0,
            'sales_person_id' => $store->sales_person_id,
            'store_id' => $store->id,
        ]);

        $subTotal = 0;
        $totalTax = 0;

        foreach ($request->order_details as $detail) {
            $product = Product::find($detail['product_id']);
            if (!$product) continue;

            $quantity = $detail['quantity'] ?? 1;

            // Discount
            if ($product->discount_type === 'percent') {
                $discount = ($product->price * $product->discount / 100);
            } else {
                $discount = $product->discount;
            }

            $priceAfterDiscount = $product->price - $discount;

            // Tax
            if ($product->tax_type === 'percent') {
                $taxAmount = ($priceAfterDiscount * $product->tax / 100);
            } else {
                $taxAmount = $product->tax;
            }

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
                'order_id' => $order->id,
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

        $order->update([
            'order_amount' => $subTotal,
            'total_tax_amount' => $totalTax,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'order' => $order->load('salesPerson', 'details'),
        ], 200);
    }
}
