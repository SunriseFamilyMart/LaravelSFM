<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>GST Invoice</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 6px;
        }

        .heading {
            background: #f1f1f1;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>

</head>

<body>

    @foreach ($orders as $order)
        <div style="padding:20px;">

            <!-- TOP HEADER -->
            <table width="100%">
                <tr>
                    <td>
                        <h2 style="margin:0;">TAX INVOICE</h2>
                        <strong>Invoice No:</strong> #{{ $order->id }}<br>
                        <strong>Date:</strong> {{ date('d M Y h:i A', strtotime($order->created_at)) }}<br>
                        <strong>Payment:</strong> {{ ucfirst($order->payment_method) }}
                    </td>
                    <td align="right">
                        @if (!empty($business_logo))
                            <img src="{{ $business_logo }}" style="height:70px;">
                        @endif
                    </td>
                </tr>
            </table>


            <!-- BILL FROM / BILL TO / SHIP TO -->
            <table class="table" style="margin-top:15px;">
                <tr class="heading">
                    <td>Bill From</td>
                    <td>Bill To (Store)</td>
                    <td>Ship To</td>
                </tr>

                <tr>
                    <!-- BILL FROM -->
                    <td>
                        <strong>{{ $business_name }}</strong><br>
                        {{ $business_address }}<br>
                        Phone: {{ $business_phone }}<br>
                        Email: {{ $business_email }}<br>
                        GSTIN: {{ $business_gst }}<br>
                    </td>

                    <!-- BILL TO STORE -->
                    <td>
                        <strong>{{ $order->store->store_name ?? '-' }}</strong><br>
                        {{ $order->store->address ?? '-' }}<br>
                        Phone: {{ $order->store->phone_number ?? '-' }}<br>
                        GSTIN: {{ $order->store->gst_number ?? '-' }}<br>
                    </td>

                    <!-- SHIP TO -->
                    <td>
                        <strong>{{ $order->store->store_name ?? '-' }}</strong><br>
                        {{ $order->store->address ?? '-' }}<br>
                        Phone: {{ $order->store->phone_number ?? '-' }}<br>
                    </td>
                </tr>
            </table>


            <!-- ORDER DETAILS -->
            <table width="100%" style="margin:15px 0;">
                <tr>
                    <td>
                        <strong>Delivery Man:</strong> {{ $order->delivery_man->f_name ?? '' }}
                        {{ $order->delivery_man->l_name ?? '' }}<br>

                        <strong>Ordered By (Sales Person):</strong>
                        {{ $order->salesPerson->name ?? '-' }} ({{ $order->salesPerson->phone_number ?? '-' }})<br>

                        <strong>Trip No:</strong> {{ $order->trip_number ?? '-' }}
                    </td>
                </tr>
            </table>


            <!-- ITEMS TABLE -->
            <table class="table">
                <thead>
                    <tr class="heading">
                        <th>Item</th>
                        <th>HSN</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Tax %</th>
                        <th>Tax Amt</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($order->details as $d)
                        <tr>
                            <td>{{ $d->product->name ?? ($d->product_details['name'] ?? 'Item') }}</td>
                            <td>{{ $d->product->hsn_code ?? '-' }}</td>
                            <td align="center">{{ $d->quantity }}</td>
                            <td align="right">₹{{ number_format($d->price, 2) }}</td>
                            <td align="center">5%</td>
                            <td align="right">₹{{ number_format($d->tax_amount, 2) }}</td>
                            <td align="right">₹{{ number_format($d->price * $d->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>


            <!-- TOTALS -->
            <table width="100%" style="margin-top:15px;">
                <tr>
                    <td align="right">
                        <strong>Subtotal:</strong>
                        ₹{{ number_format($order->order_amount - $order->total_tax_amount, 2) }}<br>

                        <strong>CGST @2.5%:</strong>
                        ₹{{ number_format($order->total_tax_amount / 2, 2) }}<br>

                        <strong>SGST @2.5%:</strong>
                        ₹{{ number_format($order->total_tax_amount / 2, 2) }}<br>

                        <strong>Grand Total:</strong>
                        <span style="font-size:15px;font-weight:bold;">
                            ₹{{ number_format($order->order_amount, 2) }}
                        </span>
                    </td>
                </tr>
            </table>


            <!-- SINGLE ROW: BANK DETAILS + QR CODE -->
            <table width="100%" style="margin-top:20px;">
                <tr>
                    <!-- LEFT: BANK DETAILS -->
                    <td width="65%" valign="top">
                        <h4 style="margin-top:0;">BANK DETAILS</h4>

                        <table>
                            <tr>
                                <td><strong>Bank Name:</strong></td>
                                <td>{{ $business_bank_name ?? 'HDFC Bank' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Account Holder:</strong></td>
                                <td>{{ $business_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Account No:</strong></td>
                                <td>{{ $business_bank_account ?? '50200058934605' }}</td>
                            </tr>
                            <tr>
                                <td><strong>IFSC:</strong></td>
                                <td>{{ $business_bank_ifsc ?? 'HDFC0001753' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Branch:</strong></td>
                                <td>{{ $business_bank_branch ?? 'Kanakpura Road' }}</td>
                            </tr>
                            <tr>
                                <td><strong>UPI ID:</strong></td>
                                <td>{{ $business_upi ?? 'paytmqr5jjsna@ptys' }}</td>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT: QR CODE -->
                    <td width="35%" valign="top" align="center">
                        @if (!empty($paytm_qr_code))
                            <h4>Scan & Pay</h4>
                            <img src="{{ $paytm_qr_code }}"
                                style="width:150px; height:auto; border:1px solid #000; padding:5px;">
                        @endif
                    </td>
                </tr>
            </table>




            <!-- ✅ TERMS & CONDITIONS -->
            <h4 style="margin-top:10px;">TERMS AND CONDITIONS</h4>
            <ul style="font-size:11px; margin-top:5px;">
                <li>All disputes are subjected to Ramnagar jurisdiction only.</li>
                <li>All Credits must be cleared between 7-10 Working days, late payments may have additional charges of
                    0.2% of the Bill.</li>
            </ul>

            <h4 style="margin-top:10px;">Return and Refund policy</h4>
            <ul style="font-size:11px; margin-top:5px;">
                <li>We will take the product only if there is Quality issues.</li>
            </ul>
            <!-- FOOTER -->
            <div style="margin-top:20px; font-size:11px;">
                {!! $footer_text !!}
            </div>

        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>
