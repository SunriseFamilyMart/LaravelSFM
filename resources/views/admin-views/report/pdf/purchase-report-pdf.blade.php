<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Report</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data['company_name'] }}</h1>
        <h2>Purchase Report</h2>
        @if($data['start_date'] && $data['end_date'])
            <p>Period: {{ $data['start_date'] }} to {{ $data['end_date'] }}</p>
        @endif
    </div>

    <div class="summary">
        <p><strong>Total Amount:</strong> {{ \App\CentralLogics\Helpers::set_symbol($data['total_amount']) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Purchase ID</th>
                <th>Date</th>
                <th>Product</th>
                <th>Supplier</th>
                <th>Quantity</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['purchases'] as $purchase)
                <tr>
                    <td>{{ $purchase->purchase_id }}</td>
                    <td>{{ $purchase->purchase_date }}</td>
                    <td>{{ $purchase->product_name }}</td>
                    <td>{{ $purchase->supplier_name ?? 'N/A' }}</td>
                    <td class="text-right">{{ $purchase->quantity }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($purchase->total_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
