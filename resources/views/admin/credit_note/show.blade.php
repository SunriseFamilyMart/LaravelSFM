@extends('layouts.admin.app')

@section('content')
<div class="container">
    <h3>Credit Note (GST)</h3>

    <p><strong>Credit Note No:</strong> {{ $creditNote->credit_note_no }}</p>
    <p><strong>Date:</strong> {{ $creditNote->created_at->format('d M Y') }}</p>
    <p><strong>Customer:</strong> {{ $creditNote->order->customer?->f_name }}</p>
    <p><strong>Branch:</strong> {{ $creditNote->order->branch?->name }}</p>
    <p><strong>Reason:</strong> {{ ucfirst($creditNote->reason) }}</p>

    <table class="table table-bordered mt-3">
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
                <td>{{ number_format($item->price, 2) }}</td>
                <td>{{ number_format($item->price * $item->quantity, 2) }}</td>
                <td>{{ $item->gst_percent }}%</td>
                <td>{{ number_format($item->gst_amount, 2) }}</td>
                <td>{{ number_format(($item->price * $item->quantity) + $item->gst_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h5 class="text-right">
        Taxable: ₹{{ number_format($creditNote->taxable_amount, 2) }} <br>
        GST: ₹{{ number_format($creditNote->gst_amount, 2) }} <br>
        <strong>Total: ₹{{ number_format($creditNote->total_amount, 2) }}</strong>
    </h5>

    <a href="{{ route('admin.credit-note.pdf', $creditNote->id) }}"
       class="btn btn-primary mt-3">
        Download PDF
    </a>
</div>
@endsection
