@extends('layouts.admin.app')

@section('content')
<div class="content container-fluid">
    <h3>Delivery Trip: {{ $trip->trip_number }}</h3>
    <p><strong>Delivery Man:</strong> {{ $trip->deliveryMan->f_name ?? 'N/A' }}</p>
    <p><strong>Status:</strong> <span class="badge badge-info">{{ ucfirst($trip->status) }}</span></p>

    <hr>

    <h5>Orders in this Trip:</h5>
    @foreach ($orders as $order)
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-light">
                <strong>Order #{{ $order->id }}</strong> 
                <span class="text-muted"> — Status: {{ ucfirst($order->order_status) }}</span>
            </div>
            <div class="card-body">
                <p><strong>Order Amount:</strong> ₹{{ number_format($order->order_amount, 2) }}</p>
                <p><strong>Payment Method:</strong> {{ $order->payment_method ?? 'N/A' }}</p>

                <h6>Products:</h6>
                <ul>
                    @foreach ($orderDetails->where('order_id', $order->id) as $detail)
                        @php
                            $product = is_array($detail->product_details)
                                ? $detail->product_details
                                : json_decode($detail->product_details, true);
                        @endphp
                        <li>
                            {{ $product['name'] ?? 'Unknown Product' }}
                            — Qty: {{ $detail->quantity }},
                            Price: ₹{{ $detail->price }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 text-end">
                    <a href="{{ route('admin.orders.details', $order->id) }}" class="btn btn-primary btn-sm">
                        <i class="tio-info-outlined"></i> View All Details
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
