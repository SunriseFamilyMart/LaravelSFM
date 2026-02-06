<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #f2f2f2; }
        .right { text-align: right; }
    </style>
</head>
<body>

<h2>GST Credit Note</h2>

<p>
    <strong>Credit Note No:</strong> {{ $creditNote->credit_note_no }} <br>
    <strong>Date:</strong> {{ $creditNote->created_at->format('d M Y') }} <br>
    <strong>Customer:</strong> {{ $creditNote->order->customer?->f_name }} <br>
    <strong>Branch:</strong> {{ $creditNote->order->branch?->name }}
</p>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Taxable</th>
            <th>GST %</th>
            <th>GST</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($creditNote->items as $item)
        <tr>
            <td>{{ $item->product?->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td class="right">{{ number_format($item->price, 2) }}</td>
            <td class="right">{{ number_format($item->price * $item->quantity, 2) }}</td>
            <td class="right">{{ $item->gst_percent }}%</td>
            <td class="right">{{ number_format($item->gst_amount, 2) }}</td>
            <td class="right">
                {{ number_format(($item->price * $item->quantity) + $item->gst_amount, 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<h3 class="right">
    Taxable: ₹{{ number_format($creditNote->taxable_amount, 2) }} <br>
    GST: ₹{{ number_format($creditNote->gst_amount, 2) }} <br>
    Total: ₹{{ number_format($creditNote->total_amount, 2) }}
</h3>

</body>
</html>
