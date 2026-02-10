<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GSTR-1 Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 5px 0; font-size: 20px; }
        .header p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-size: 10px; }
        .text-right { text-align: right; }
        .total-row { background-color: #e8e8e8; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data['company_name'] }}</h1>
        <h2>GSTR-1: Outward Supplies</h2>
        @if($data['start_date'] && $data['end_date'])
            <p>Period: {{ $data['start_date'] }} to {{ $data['end_date'] }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>HSN Code</th>
                <th>Tax Rate</th>
                <th>Taxable Value</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>Total Tax</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalTaxable = 0;
                $totalCGST = 0;
                $totalSGST = 0;
                $totalTax = 0;
            @endphp
            @foreach($data['gstr_data'] as $row)
                <tr>
                    <td>{{ $row['hsn_code'] }}</td>
                    <td class="text-right">{{ number_format($row['tax_rate'], 2) }}%</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($row['taxable_value']) }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($row['cgst_amount']) }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($row['sgst_amount']) }}</td>
                    <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($row['total_tax']) }}</td>
                </tr>
                @php
                    $totalTaxable += $row['taxable_value'];
                    $totalCGST += $row['cgst_amount'];
                    $totalSGST += $row['sgst_amount'];
                    $totalTax += $row['total_tax'];
                @endphp
            @endforeach
            <tr class="total-row">
                <td colspan="2">Grand Total</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($totalTaxable) }}</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($totalCGST) }}</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($totalSGST) }}</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($totalTax) }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 10px;">
        <strong>Note:</strong> CGST and SGST are calculated as 50% each of the total tax for intra-state transactions.
    </p>
</body>
</html>
