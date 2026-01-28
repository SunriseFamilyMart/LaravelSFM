@extends('inventory.layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Purchase List</h2>
        <a href="{{ route('inventory.purchases.create') }}" class="btn btn-primary">Add New Purchase</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('inventory.purchases.index') }}" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by Product, Supplier, Invoice..."
                value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="supplier_id" class="form-select">
                <option value="">All Suppliers</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="product_id" class="form-select">
                <option value="">All Products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex">
            <button type="submit" class="btn btn-primary me-2">Search</button>
            <a href="{{ route('inventory.purchases.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Quantity</th>
                        <th>Price per Unit (₹)</th>
                        <th>GST (%)</th>
                        <th>Product Gst Amount (₹)</th>
                        <th>Total Amount (₹)</th>
                        <th>Invoice No</th>
                        <th>Invoice File</th>
                        <th>Purchased At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $loop->iteration + ($purchases->currentPage() - 1) * $purchases->perPage() }}</td>
                            <td>{{ $purchase->product->name ?? 'N/A' }}</td>
                            <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $purchase->quantity }}</td>
                            <td>{{ number_format($purchase->price, 2) }}</td>
                           <td>{{ rtrim(rtrim($purchase->gst, '0'), '.') }}%</td>

                            <td>
    {{ number_format(($purchase->price * $purchase->quantity) + (($purchase->price * $purchase->quantity) * ($purchase->gst / 100)), 2) }}
</td>
<td>{{ number_format($invoiceTotals[$purchase->invoice_number] ?? 0, 2) }}</td>

<td>{{ $purchase->invoice_number ?? '—' }}</td>
                            <td>
                                @if ($purchase->invoice)
                                    <a href="{{ asset('storage/' . $purchase->invoice) }}" target="_blank"
                                        class="btn btn-sm btn-info">View</a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $purchase->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No purchases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                Showing {{ $purchases->firstItem() ?? 0 }} to {{ $purchases->lastItem() ?? 0 }} of
                {{ $purchases->total() }} purchases
            </div>
            <div>
                {{ $purchases->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

