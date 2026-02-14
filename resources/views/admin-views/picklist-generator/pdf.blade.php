<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Picklist Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .filter-info {
            margin-bottom: 20px;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
        }
        .filter-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #333;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .route-total {
            background: #e9ecef !important;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Picklist Report</h1>
        <p>Generated on {{ date('F d, Y h:i A') }}</p>
    </div>

    <div class="filter-info">
        <p><strong>Date Range:</strong> 
            @if($startDate && $endDate)
                {{ date('M d, Y', strtotime($startDate)) }} - {{ date('M d, Y', strtotime($endDate)) }}
            @elseif($startDate)
                From {{ date('M d, Y', strtotime($startDate)) }}
            @elseif($endDate)
                Until {{ date('M d, Y', strtotime($endDate)) }}
            @else
                All Dates
            @endif
        </p>
        <p><strong>Store:</strong> {{ $storeName }}</p>
        <p><strong>Route:</strong> {{ $routeName }}</p>
        <p><strong>Picking Status:</strong> {{ ucfirst($pickingStatus) }}</p>
    </div>

    @if($picklistData->isEmpty())
        <p style="text-align: center; padding: 50px;">No data found with current filters</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Route</th>
                    <th>Product Name</th>
                    <th>Unit Weight (kg)</th>
                    <th>Quantity</th>
                    <th>Total Weight (kg)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentRoute = null;
                @endphp
                @foreach($picklistData as $item)
                    @if($currentRoute !== $item->route_name && $currentRoute !== null)
                        <tr class="route-total">
                            <td colspan="4" class="text-right">
                                <strong>TOTAL FOR ROUTE: {{ $currentRoute }}</strong>
                            </td>
                            <td>
                                <strong>{{ number_format($routeTotals[$currentRoute]['total_weight'] ?? 0, 2) }} kg</strong>
                            </td>
                        </tr>
                    @endif
                    @php
                        $currentRoute = $item->route_name;
                    @endphp
                    <tr>
                        <td>{{ $item->route_name ?? 'N/A' }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ number_format($item->weight ?? 0, 2) }}</td>
                        <td>{{ $item->total_quantity }}</td>
                        <td>{{ number_format($item->total_weight, 2) }}</td>
                    </tr>
                @endforeach
                @if($currentRoute !== null)
                    <tr class="route-total">
                        <td colspan="4" class="text-right">
                            <strong>TOTAL FOR ROUTE: {{ $currentRoute }}</strong>
                        </td>
                        <td>
                            <strong>{{ number_format($routeTotals[$currentRoute]['total_weight'] ?? 0, 2) }} kg</strong>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
