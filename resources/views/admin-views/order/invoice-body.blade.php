<div style="padding: 20px; font-family: DejaVu Sans, sans-serif; font-size: 12px;">

    <!-- Logo + Order Info -->
    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td>
                <h2 style="margin: 0; font-size: 20px; font-weight: bold;">INVOICE</h2>
                <strong>Order ID:</strong> #{{ $order->id }}<br>
                <strong>Date:</strong> {{ date('d M Y', strtotime($order->created_at)) }}<br>
                <strong>Payment:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
            </td>

            <td align="right">
                @if (!empty($business_logo))
                    <img src="{{ $business_logo }}" style="height: 60px;">
                @endif
            </td>
        </tr>
    </table>

    <!-- BILL FROM + BILL TO -->
    <table width="100%" style="margin-bottom: 20px;">
        <tr>

            <!-- Bill From -->
            <td width="50%" valign="top">
                <h4 style="margin: 0 0 8px 0;">Bill From:</h4>

                <strong>{{ $business_name ?? 'Sunrise Family Mart' }}</strong><br>
                {{ $business_address ?? 'Your Address Line' }}<br>

                @if (!empty($business_phone))
                    Phone: {{ $business_phone }}<br>
                @endif

                @if (!empty($business_email))
                    Email: {{ $business_email }}<br>
                @endif

                @if (!empty($business_gst))
                    GST: {{ $business_gst }}<br>
                @endif
            </td>

            <!-- Bill To Store -->
            <td width="50%" valign="top">
                <h4 style="margin: 0 0 8px 0;">Bill To (Store):</h4>

                @if ($order->store)
                    <strong>{{ $order->store->store_name }}</strong><br>

                    @if (!empty($order->store->customer_name))
                        Contact: {{ $order->store->customer_name }}<br>
                    @endif

                    Address: {{ $order->store->address ?? '-' }}<br>
                    Phone: {{ $order->store->phone_number ?? '-' }}<br>

                    @if (!empty($order->store->gst_number))
                        GST: {{ $order->store->gst_number }}<br>
                    @endif
                @else
                    Store not available<br>
                @endif
            </td>
        </tr>
    </table>

    <!-- Order Summary -->
    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td width="50%">
                <h4 style="margin: 0 0 8px 0;">Order Details:</h4>
                <strong>Order Type:</strong> {{ strtoupper($order->order_type ?? 'Normal') }}<br>
                <strong>Status:</strong> {{ ucfirst($order->order_status) }}<br>

                @if ($order->delivery_man)
                    <strong>Delivery Man:</strong>
                    {{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}<br>
                @endif

                @if ($order->salesPerson)
                    <strong>Ordered By:</strong>
                    {{ $order->salesPerson->name }} ({{ $order->salesPerson->phone_number }})<br>
                @endif

                @if ($order->trip_number)
                    <strong>Trip Number:</strong> {{ $order->trip_number }}<br>
                @endif

            </td>
        </tr>
    </table>

    <!-- Order Items Table -->
    <table width="100%" cellspacing="0" cellpadding="5" style="border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background: #f1f1f1; border-bottom: 1px solid #ddd;">
                <th align="left">Item</th>
                <th align="center">Qty</th>
                <th align="right">Price</th>
                <th align="right">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($order->details as $detail)
                <tr style="border-bottom: 1px solid #eee;">
                    <td>
                        {{ $detail->product->name ?? ($detail->product_details['name'] ?? 'Product') }}
                    </td>
                    <td align="center">
                        {{ $detail->quantity }}
                    </td>
                    <td align="right">
                        ₹{{ number_format($detail->price, 2) }}
                    </td>
                    <td align="right">
                        ₹{{ number_format($detail->price * $detail->quantity, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table width="100%" cellspacing="0" cellpadding="5" style="border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td width="70%" align="right"><strong>Subtotal:</strong></td>
            <td width="30%" align="right">
                ₹{{ number_format($order->order_amount - $order->total_tax_amount, 2) }}
            </td>
        </tr>

        <tr>
            <td width="70%" align="right"><strong>Tax:</strong></td>
            <td width="30%" align="right">
                ₹{{ number_format($order->total_tax_amount, 2) }}
            </td>
        </tr>

        @if ($order->coupon_discount_amount > 0)
            <tr>
                <td width="70%" align="right"><strong>Coupon Discount:</strong></td>
                <td width="30%" align="right">
                    - ₹{{ number_format($order->coupon_discount_amount, 2) }}
                </td>
            </tr>
        @endif

        <tr>
            <td width="70%" align="right">
                <strong style="font-size: 15px;">Grand Total:</strong>
            </td>
            <td width="30%" align="right">
                <strong style="font-size: 15px;">
                    ₹{{ number_format($order->order_amount, 2) }}
                </strong>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    @if (!empty($footer_text))
        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 11px; color: #555;">
            {!! $footer_text !!}
        </div>
    @endif

</div>
