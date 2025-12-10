@extends('layouts.admin.app')

@section('title', translate('Order Management'))

@push('css_or_js')
<style>
    .table th, .table td {
        vertical-align: middle;
        white-space: nowrap;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-delivered { background:#28a745; color:#fff; }
    .badge-progress  { background:#ffc107; color:#000; }
    .badge-delayed   { background:#dc3545; color:#fff; }
    .badge-cancel    { background:#6c757d; color:#fff; }
</style>
@endpush

@section('content')
<div class="container-fluid">

{{-- ================= HEADER ================= --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <h3 class="mb-0"><i class="tio-file"></i> Purchase Management</h3>
</div>

{{-- ================= FILTER ================= --}}
<div class="card mb-4 shadow-sm">
<div class="card-body">
<form method="GET" class="row g-3">
<!-- 
<div class="col-md-3">
<label class="form-label fw-bold">Supplier</label>
<select name="supplier" class="form-control">
<option value="">All Suppliers</option>
@foreach($suppliers as $supplier)
<option value="{{ $supplier->id }}" {{ request('supplier')==$supplier->id?'selected':'' }}>
{{ $supplier->name }}
</option>
@endforeach
</select>
</div> -->

<div class="col-md-3">
<label class="form-label fw-bold">Product</label>
<select name="product" class="form-control">
<option value="">All Products</option>
@foreach($products as $product)
<option value="{{ $product->id }}" {{ request('product')==$product->id?'selected':'' }}>
{{ $product->name }}
</option>
@endforeach
</select>
</div>

<div class="col-md-2">
<label class="form-label fw-bold">Status</label>
<select name="status" class="form-control">
<option value="">All</option>
<option value="delivered" {{ request('status')=='delivered'?'selected':'' }}>Delivered</option>
<option value="processing" {{ request('status')=='processing'?'selected':'' }}>Processing</option>
<option value="failed" {{ request('status')=='failed'?'selected':'' }}>Delayed</option>
<option value="canceled" {{ request('status')=='canceled'?'selected':'' }}>Cancelled</option>
</select>
</div>

<div class="col-md-2">
<label class="form-label fw-bold">&nbsp;</label>
<button class="btn btn-primary w-100">Filter</button>
</div>

<div class="col-md-2">
<label class="form-label fw-bold">&nbsp;</label>
<a href="{{ route('admin.orders.ordermanagement') }}" class="btn btn-secondary w-100">Reset</a>
</div>

<div class="d-flex align-items-center justify-content-between mb-4">
   

    <a href="{{ route('admin.orders.orders.create') }}"
       class="btn btn-success">
        <i class="tio-add-circle"></i> Add Product
    </a>
</div>

</form>
</div>
</div>

{{-- ================= ORDERS TABLE ================= --}}
<div class="card shadow-sm">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-bordered table-hover mb-0">

<thead class="bg-light text-center">
<tr>
  
<th>Edit</th>
<th>Order ID</th>
<!-- <th>Supplier</th> -->
<th>Product</th>
<th>Ordered By</th>
<th>Date</th>
<th>Status</th>
<th>Qty</th>
<th>Total ₹</th>
<th>Paid ₹</th>
<th>Balance ₹</th>
<th>Payment</th>
<th>Note</th>
<th>Invoice</th>    
<th>Expected Date</th>
</tr>
</thead>

<tbody>
@forelse($orders as $order)

@php
$rowspan = $order->details->count();
$paid = $order->payments->sum('amount');
$balance = $order->order_amount - $paid;
$first = true;
@endphp

@foreach($order->details as $detail)
<tr class="text-center">

@if($first)
<td rowspan="{{ $rowspan }}">
<button class="btn btn-sm btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#editOrderModal"
        onclick="loadEditModal({{ $order->id }}, '{{ $order->order_status }}')">
    Edit
</button>
</td>
@endif
@if($first)
<td rowspan="{{ $rowspan }}"><b>ORD-{{ $order->id }}</b></td>
@endif
<!-- 
<td>{{ $detail->product?->supplier?->name ?? '-' }}</td> -->

<td>{{ $detail->product?->name ?? '-' }}</td>
@if($first)
<td rowspan="{{ $rowspan }}">
    {{ $order->details->first()?->order_user ?? '-' }}
</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}">{{ $order->created_at->format('d-M-Y') }}</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}">
@if($order->order_status=='delivered')
<span class="status-badge badge-delivered">Delivered</span>
@elseif($order->order_status=='processing')
<span class="status-badge badge-progress">Processing</span>
@elseif($order->order_status=='failed')
<span class="status-badge badge-delayed">Delayed</span>
elseif($order->order_status=='rejected')
<span class="status-badge badge-delayed">Rejected</span>
elseif($order->order_status=='ordered')
<span class="status-badge badge-progress">Ordered</span>
@else
<span class="status-badge badge-cancel">{{ ucfirst($order->order_status) }}</span>
@endif
</td>
@endif

<td>{{ $detail->quantity }}</td>

@if($first)
<td rowspan="{{ $rowspan }}">{{ number_format($order->order_amount,2) }}</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}">{{ number_format($paid,2) }}</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}" class="fw-bold text-danger">
{{ number_format($balance,2) }}
</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}">{{ ucfirst($order->payment_method ?? '-') }}</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}">{{ $order->order_note ?? '-' }}</td>
@endif
@if($first)
<td rowspan="{{ $rowspan }}">
    {{ $order->details->first()?->invoice_number ?? '-' }}
</td>
@endif

@if($first)
<td rowspan="{{ $rowspan }}">
    {{ $order->details->first()?->expected_date ? \Carbon\Carbon::parse($order->details->first()->expected_date)->format('d-M-Y') : '-' }}
</td>
@endif

</tr>
@php $first = false; @endphp
@endforeach

@empty
<tr>
<td colspan="13" class="text-center py-4">No Orders Found</td>
</tr>
@endforelse
</tbody>

</table>
</div>
</div>
</div>

{{-- ================= PAGINATION ================= --}}
<div class="mt-4">
{{ $orders->appends(request()->query())->links() }}
</div>

{{-- ================= SUMMARY ================= --}}
<div class="card mt-4 shadow-sm">
<div class="card-body">
<h5 class="fw-bold">Summary</h5>
<p>
Total Orders: <b>{{ $summary['total_orders'] }}</b> |
Delivered: <b>{{ $summary['delivered'] }}</b> |
Processing: <b>{{ $summary['in_progress'] }}</b> |
Delayed: <b>{{ $summary['delayed'] }}</b> |
Cancelled: <b>{{ $summary['cancelled'] }}</b>
</p>
<hr>
<p>
Total Purchase: <b>₹{{ number_format($summary['total_purchase'],2) }}</b> |
Paid: <b>₹{{ number_format($summary['total_paid'],2) }}</b> |
Outstanding: <b class="text-danger">₹{{ number_format($summary['outstanding'],2) }}</b>
</p>
</div>
</div>

</div>

{{-- ================= EDIT MODAL ================= --}}
<div class="modal fade" id="editOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editOrderForm">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Order Status -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Order Status</label>
                        <select class="form-control" name="order_status" id="order_status">
                            <option value="processing">Processing</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Delayed</option>
                            <option value="canceled">Cancelled</option>
                            <option value="ordered">Ordered</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Invoice Number -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice Number</label>
                        <input type="text" name="invoice_number" id="invoice_number" class="form-control" placeholder="Enter invoice number">
                    </div>

                    <!-- Expected Delivery Date -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expected Delivery Date</label>
                        <input type="date" name="expected_date" id="expected_date" class="form-control">
                    </div>

                    <!-- Add Payment -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Add Payment (₹)</label>
                        <input type="number" name="paid_amount" id="paid_amount" class="form-control" placeholder="Enter paid amount">
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select class="form-control" name="payment_method" id="payment_method">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="credit_sale">Credit Sale</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
function loadEditModal(orderId, status) {
    document.getElementById('order_status').value = status;
    document.getElementById('paid_amount').value = "";
    document.getElementById('editOrderForm').action =
        "{{ route('admin.orders.orders.update', '') }}/" + orderId;
}
</script>

@endsection
