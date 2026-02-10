<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GSTR-3 Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 5px 0; font-size: 20px; }
        .header p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .summary-section { margin-bottom: 30px; }
        .net-tax { text-align: center; margin-top: 30px; padding: 20px; border: 2px solid #333; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $data['company_name'] }}</h1>
        <h2>GSTR-3: Summary Report</h2>
        @if($data['start_date'] && $data['end_date'])
            <p>Period: {{ $data['start_date'] }} to {{ $data['end_date'] }}</p>
        @endif
    </div>

    <div class="summary-section">
        <h3>Outward Supplies (Sales)</h3>
        <table>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
            <tr>
                <td>Taxable Value</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($data['outward_taxable']) }}</td>
            </tr>
            <tr>
                <td>CGST</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($data['outward_cgst']) }}</td>
            </tr>
            <tr>
                <td>SGST</td>
                <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($data['outward_sgst']) }}</td>
            </tr>
        </table>
    </div>

    <div class="net-tax">
        <h3>Net Tax Liability</h3>
        <p style="font-size: 18px; margin: 10px 0;">
            <strong>{{ \App\CentralLogics\Helpers::set_symbol($data['outward_cgst'] + $data['outward_sgst']) }}</strong>
        </p>
        <p style="font-size: 10px; margin-top: 10px;">
            Net Tax = Output Tax - Input Tax Credit
        </p>
    </div>
</body>
</html>
