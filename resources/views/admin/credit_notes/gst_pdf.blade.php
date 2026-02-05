<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:6px; }
        th { background:#f2f2f2; }
        .right { text-align:right; }
    </style>
</head>
<body>

<h3 align="center">GST CREDIT NOTE</h3>

<p><strong>Credit Note No:</strong> {{ $creditNote->credit_note_no }}</p>
<p><strong>Order Ref:</strong> #{{ $creditNote->order_id }}</p>
<p><strong>Branch:</strong> {{ $creditNote->branch }}</p>

<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Rate</th>
            <th>Taxable</th>
            <th>GST %</th>
            <th>GST</th>
        </tr>
    </thead>
    <tbody>
        @foreach($creditNote->items as $item)
        @php
            $taxable = $item->price * $item->quantity;
            $gst = ($taxable * $item->gst_percent) / 100;
        @endphp
        <tr>
            <td>{{ $item->product->name ?? '' }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">{{ number_format($item->price,2) }}</td>
            <td class="right">{{ number_format($taxable,2) }}</td>
            <td class="right">{{ $item->gst_percent }}%</td>
            <td class="right">{{ number_format($gst,2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h4 class="right">Total GST: {{ number_format($creditNote->gst_amount,2) }}</h4>
<h3 class="right">Grand Total: {{ number_format($creditNote->total_amount,2) }}</h3>

<p style="margin-top:40px">
    Authorized Signature
</p>

</body>
</html>
