<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Pick List</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .filter-info {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .filter-info table {
            width: 100%;
        }

        .filter-info td {
            padding: 3px 5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #333;
            padding: 8px 6px;
            text-align: left;
        }

        .table th {
            background: #e0e0e0;
            font-weight: bold;
        }

        .order-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .order-header {
            background: #d0d0d0;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ccc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>

</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <h1>PICK LIST</h1>
        <p>Warehouse Picking Document</p>
    </div>

    <!-- FILTER INFO -->
    <div class="filter-info">
        <table>
            <tr>
                <td width="15%"><strong>Branch:</strong></td>
                <td width="35%">{{ $filterInfo['branch'] }}</td>
                <td width="15%"><strong>Route:</strong></td>
                <td width="35%">{{ $filterInfo['route'] }}</td>
            </tr>
            <tr>
                <td><strong>Date From:</strong></td>
                <td>{{ $filterInfo['date_from'] }}</td>
                <td><strong>Date To:</strong></td>
                <td>{{ $filterInfo['date_to'] }}</td>
            </tr>
            <tr>
                <td><strong>Time From:</strong></td>
                <td>{{ $filterInfo['time_from'] }}</td>
                <td><strong>Time To:</strong></td>
                <td>{{ $filterInfo['time_to'] }}</td>
            </tr>
            <tr>
                <td><strong>Generated:</strong></td>
                <td colspan="3">{{ $filterInfo['generated_at'] }}</td>
            </tr>
        </table>
    </div>

    @php
        $grandTotalItems = 0;
        $grandTotalWeight = 0;
        $totalOrders = $orders->count();
    @endphp

    <!-- ORDERS -->
    @foreach ($orders as $index => $order)
        <div class="order-section">
            <!-- Order Header -->
            <div class="order-header">
                Order #{{ $order->id }} | 
                Store: {{ $order->store->store_name ?? 'N/A' }} | 
                Route: {{ $order->store->route_name ?? 'N/A' }} | 
                Status: {{ ucfirst($order->order_status) }} | 
                Date: {{ date('Y-m-d H:i', strtotime($order->created_at)) }}
            </div>

            <!-- Delivery Info -->
            <table style="margin-bottom: 10px; width: 100%; border: none;">
                <tr>
                    <td style="border: none; padding: 3px;"><strong>Branch:</strong> {{ $order->branch->name ?? 'N/A' }}</td>
                    <td style="border: none; padding: 3px;"><strong>Delivery Date:</strong> {{ $order->delivery_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 3px;" colspan="2"><strong>Delivery Instructions:</strong> {{ $order->order_note ?? 'None' }}</td>
                </tr>
                @if ($order->time_slot)
                <tr>
                    <td style="border: none; padding: 3px;" colspan="2"><strong>Time Slot:</strong> {{ $order->time_slot->start_time ?? '' }} - {{ $order->time_slot->end_time ?? '' }}</td>
                </tr>
                @endif
            </table>

            <!-- Items Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="35%">Product Name</th>
                        <th width="15%">Unit</th>
                        <th width="10%" class="text-right">Qty</th>
                        <th width="15%" class="text-right">Unit Weight (kg)</th>
                        <th width="15%" class="text-right">Total Weight (kg)</th>
                        <th width="5%">✓</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $orderTotalWeight = 0;
                        $orderTotalItems = 0;
                    @endphp
                    @foreach ($order->details as $key => $detail)
                        @php
                            $product = $detail->product;
                            $productName = $product ? $product->name : 'Product #' . $detail->product_id;
                            $unit = $detail->unit ?? 'pcs';
                            $quantity = $detail->quantity;
                            $unitWeight = $product && isset($product->weight) ? $product->weight : 0;
                            $totalWeight = $unitWeight * $quantity;
                            $orderTotalWeight += $totalWeight;
                            $orderTotalItems += $quantity;
                        @endphp
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $productName }}
                                @if ($detail->variant)
                                    <br><small style="color: #666;">Variant: {{ $detail->variant }}</small>
                                @endif
                            </td>
                            <td>{{ $unit }}</td>
                            <td class="text-right">{{ $quantity }}</td>
                            <td class="text-right">{{ number_format($unitWeight, 2) }}</td>
                            <td class="text-right">{{ number_format($totalWeight, 2) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    <tr style="background: #f0f0f0; font-weight: bold;">
                        <td colspan="3" class="text-right">Order Totals:</td>
                        <td class="text-right">{{ $orderTotalItems }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($orderTotalWeight, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            @php
                $grandTotalItems += $orderTotalItems;
                $grandTotalWeight += $orderTotalWeight;
            @endphp
        </div>

        @if ($index < $totalOrders - 1)
            <hr style="margin: 20px 0; border: 0; border-top: 2px dashed #999;">
        @endif
    @endforeach

    <!-- GRAND SUMMARY -->
    <div class="summary">
        <table width="100%" style="border: none;">
            <tr>
                <td style="border: none; padding: 5px;"><strong>Total Orders:</strong> {{ $totalOrders }}</td>
                <td style="border: none; padding: 5px; text-align: right;"><strong>Total Items:</strong> {{ $grandTotalItems }}</td>
                <td style="border: none; padding: 5px; text-align: right;"><strong>Total Weight:</strong> {{ number_format($grandTotalWeight, 2) }} kg</td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #999; text-align: center; font-size: 10px;">
        <p>This is a computer-generated pick list. Please ensure all items are checked before dispatch.</p>
    </div>

</body>

</html>
