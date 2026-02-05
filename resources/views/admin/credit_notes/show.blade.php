@extends('layouts.admin.app')

@section('title','Credit Note')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4>Credit Note : {{ $creditNote->credit_note_no }}</h4>

            <a href="{{ route('admin.credit-note.pdf', $creditNote->id) }}"
               class="btn btn-success">
               Download PDF
            </a>
        </div>

        <div class="card-body">
            <p><strong>Order ID:</strong> #{{ $creditNote->order_id }}</p>
            <p><strong>Branch:</strong> {{ $creditNote->branch }}</p>
            <p><strong>Reason:</strong> {{ ucfirst($creditNote->reason) }}</p>

            <table class="table table-bordered mt-3">
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
                    <tr>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ Helpers::set_symbol($item->price) }}</td>
                        <td>{{ Helpers::set_symbol($item->price * $item->quantity) }}</td>
                        <td>{{ $item->gst_percent }}%</td>
                        <td>{{ Helpers::set_symbol(($item->price * $item->quantity) * $item->gst_percent / 100) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h5 class="text-right mt-3">
                Total: {{ Helpers::set_symbol($creditNote->total_amount) }}
            </h5>
        </div>
    </div>
</div>
@endsection
