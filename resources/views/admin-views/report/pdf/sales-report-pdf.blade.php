<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 5px 0; font-size: 20px; }
        .header p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .summary { margin-bottom: 20px; }
        .summary-box { display: inline-block; width: 48%; margin: 5px; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data['company_name'] }}</h1>
        <h2>Sales Report</h2>
        @if($data['start_date'] && $data['end_date'])
            <p>Period: {{ $data['start_date'] }} to {{ $data['end_date'] }}</p>
        @endif
    </div>

    <div class="summary">
        <p><strong>Total Amount:</strong> {{ \App\CentralLogics\Helpers::set_symbol($data['total_amount']) }}</p>
        <p><strong>Total Tax:</strong> {{ \App\CentralLogics\Helpers::set_symbol($data['total_tax']) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Amount</th>
                <th>Tax</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['orders'] as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->created_at->format('Y-m-d') }}</td>
                    <td>{{ $order->branch->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($order->order_amount) }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($order->total_tax_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
