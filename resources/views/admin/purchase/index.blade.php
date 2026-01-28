@extends('layouts.admin.app')

@section('content')
<div class="container">
    <br/> <br/>

    <h4 class="mb-3">Purchase Management</h4>

    {{-- Top Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.purchase.create') }}" class="btn btn-primary">
            + Add Purchase
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.purchase.index') }}" class="row mb-3">

        <div class="col-md-3">
            <select name="supplier_id" class="form-control">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}"
                        {{ request('supplier_id')==$supplier->id?'selected':'' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <select name="product_id" class="form-control">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}"
                        {{ request('product_id')==$product->id?'selected':'' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                @foreach(['Pending','In Progress','Delivered','Delayed'] as $s)
                    <option value="{{ $s }}"
                        {{ request('status')==$s?'selected':'' }}>
                        {{ $s }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <button class="btn btn-secondary w-100">Filter</button>
        </div>
    </form>

    {{-- TABLE --}}
    <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
        <tr>
             <th>Action</th>
            <th>Purchase ID</th>
            <th>Supplier</th>
            <th>Product</th>
            <th>Purchased By</th>
            <th>Order Date</th>
            <th>Expected Delivery</th>
            <th>Actual Delivery</th>
            <th>Invoice No</th>
            <th>Status</th>
            <th>MRP (₹)</th>
            <th>Purchase Price (₹)</th>
            <th>Qty</th>
            <th>Total (₹)</th>
            <th>Paid (₹)</th>
            <th>Balance (₹)</th>
            <th>Payment Mode</th>
            <th>Comments</th>
         
        </tr>
        </thead>

        <tbody>
        @forelse($purchases as $p)
            <tr>
              <td>
                    <a href="{{ route('admin.purchase.edit',$p->id) }}"
                       class="btn btn-sm btn-warning">
                        Edit
                    </a>
                </td>
                <td>{{ $p->purchase_id }}</td>
                <td>{{ $p->supplier->name ?? '-' }}</td>
                <td>{{ $p->product->name ?? '-' }}</td>
                <td>{{ $p->purchased_by }}</td>
                <td>{{ $p->purchase_date }}</td>
                <td>{{ $p->expected_delivery_date }}</td>
                <td>{{ $p->actual_delivery_date ?? '-' }}</td>
                <td>{{ $p->invoice_number ?? '-' }}</td>

                <td>
                    <span class="badge
                        {{ $p->status=='Delivered'?'bg-success':
                           ($p->status=='Delayed'?'bg-danger':
                           ($p->status=='In Progress'?'bg-warning':'bg-secondary')) }}">
                        {{ $p->status }}
                    </span>
                </td>

                <td>₹ {{ number_format($p->mrp,2) }}</td>
                <td>₹ {{ number_format($p->purchase_price,2) }}</td>
                <td>{{ $p->quantity }}</td>
                <td>₹ {{ number_format($p->total_amount,2) }}</td>
                <td>₹ {{ number_format($p->paid_amount,2) }}</td>
                <td>₹ {{ number_format($p->balance_amount,2) }}</td>
                <td>{{ $p->payment_mode }}</td>
                <td>{{ $p->comments }}</td>

                
            </tr>
        @empty
            <tr>
                <td colspan="18" class="text-center text-muted">
                    No purchases found
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    </div>

</div>
@endsection
