@extends('layouts.admin.app')

@section('title', "Store Details - {$store->store_name}")

@push('css')
<style>
    .summary-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        color: white;
        transition: transform 0.2s ease;
    }
    .summary-card:hover {
        transform: translateY(-2px);
    }
    .summary-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .summary-card.danger {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    }
    .summary-card.warning {
        background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
    }
    .summary-card .value {
        font-size: 1.75rem;
        font-weight: 700;
    }
    .summary-card .label {
        font-size: 0.85rem;
        opacity: 0.9;
    }
    
    .orders-table {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .orders-table thead {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    }
    .orders-table thead th {
        color: white;
        font-weight: 600;
        padding: 12px 15px;
        border: none;
        white-space: nowrap;
    }
    .orders-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    .orders-table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .badge-paid {
        background-color: #28a745;
        color: white;
    }
    .badge-partial {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-unpaid {
        background-color: #dc3545;
        color: white;
    }
    .badge-delivered {
        background-color: #17a2b8;
        color: white;
    }
    .badge-pending {
        background-color: #6c757d;
        color: white;
    }
    .badge-returned {
        background-color: #6f42c1;
        color: white;
    }
    
    .amount-positive {
        color: #28a745;
        font-weight: 600;
    }
    .amount-negative {
        color: #dc3545;
        font-weight: 600;
    }
    .amount-adjusted {
        color: #007bff;
        font-weight: 600;
    }
    .amount-zero {
        color: #6c757d;
    }
    
    .filter-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .store-info-card {
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-store me-2"></i>Store Details
        </h2>
        <a href="{{ route('admin.stores.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Stores
        </a>
    </div>

    {{-- Store Details Card --}}
    <div class="card store-info-card shadow-sm p-4 mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-info-circle me-2"></i>Store Information
                </h5>
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 140px;">Store Name</td>
                        <td><strong>{{ $store->store_name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Customer</td>
                        <td>{{ $store->customer_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Address</td>
                        <td>{{ $store->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">GST Number</td>
                        <td>{{ $store->gst_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td>
                            @if($store->phone_number)
                                <a href="tel:+91{{ $store->phone_number }}" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>{{ $store->phone_number }}
                                </a>
                            @else N/A @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Alternate</td>
                        <td>
                            @if($store->alternate_number)
                                <a href="tel:+91{{ $store->alternate_number }}" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>{{ $store->alternate_number }}
                                </a>
                            @else N/A @endif
                        </td>
                    </tr>
                </table>

                {{-- Assign Sales Person --}}
                <div class="mt-4">
                    <form action="{{ route('admin.stores.updateSalesPerson', $store->id) }}"
                          method="POST" class="border p-3 rounded bg-light">
                        @csrf
                        @method('PATCH')
                        <label class="form-label"><strong>Assign Sales Person</strong></label>
                        <select name="sales_person_id" class="form-select">
                            <option value="">Select Sales Person</option>
                            @foreach ($salesPeople as $person)
                                <option value="{{ $person->id }}"
                                    {{ $store->sales_person_id == $person->id ? 'selected' : '' }}>
                                    {{ $person->name }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary mt-3 w-100">
                            <i class="fas fa-save me-1"></i>Update
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-map-marker-alt me-2"></i>Location & Photo
                </h5>
                <p><strong>Latitude:</strong> {{ $store->latitude ?? '-' }}</p>
                <p><strong>Longitude:</strong> {{ $store->longitude ?? '-' }}</p>
                <p><strong>Store Photo:</strong></p>
                @if($store->store_photo)
                    <img src="{{ asset('storage/' . $store->store_photo) }}"
                         class="img-fluid rounded shadow" style="max-width:260px;">
                @else
                    <span class="text-muted">No photo available</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card summary-card p-3 text-center">
                <div class="label">Total Orders</div>
                <div class="value">{{ $summary->total_orders ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card warning p-3 text-center">
                <div class="label">Total Order Amount</div>
                <div class="value">₹{{ number_format($summary->total_order_amount ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card success p-3 text-center">
                <div class="label">Total Paid</div>
                <div class="value">₹{{ number_format($summary->total_paid ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card danger p-3 text-center">
                <div class="label">Outstanding Amount</div>
                <div class="value">₹{{ number_format($summary->outstanding_amount ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small">Order ID</label>
                <input type="text" name="order_id" class="form-control" placeholder="Search Order ID" value="{{ request('order_id') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Orders Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>Orders
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table orders-table mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total Amount</th>
                            <th>Direct Paid</th>
                            <th>Adjusted</th>
                            <th>Total Paid</th>
                            <th>Pending</th>
                            <th>Pay Status</th>
                            <th>Order Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($orders as $o)
                        @php
                            $totalAmount = $o->total_amount ?? 0;
                            $directPaid = $o->direct_paid ?? $o->total_paid ?? 0;
                            $adjustedValue = $o->adjusted_value ?? 0;
                            $totalPaid = $o->total_paid ?? 0;
                            $pendingAmount = max(0, $o->pending_amount ?? ($totalAmount - $totalPaid));
                            
                            // Determine payment status badge
                            $paymentStatus = $o->payment_status ?? 'unpaid';
                            if ($totalPaid >= $totalAmount && $totalAmount > 0) {
                                $paymentStatus = 'paid';
                            } elseif ($totalPaid > 0 && $totalPaid < $totalAmount) {
                                $paymentStatus = 'partial';
                            }
                        @endphp
                        <tr>
                            <td>
                                <strong>#{{ $o->id }}</strong>
                            </td>
                            <td>
                                <span class="fw-bold">₹{{ number_format($totalAmount, 2) }}</span>
                            </td>
                            <td>
                                @if($directPaid > 0)
                                    <span class="amount-positive">₹{{ number_format($directPaid, 2) }}</span>
                                @else
                                    <span class="amount-zero">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($adjustedValue > 0)
                                    <span class="amount-adjusted" title="Adjusted from other order payments">
                                        <i class="fas fa-exchange-alt me-1"></i>₹{{ number_format($adjustedValue, 2) }}
                                    </span>
                                @else
                                    <span class="amount-zero">-</span>
                                @endif
                            </td>
                            <td>
                                @if($totalPaid > 0)
                                    <span class="amount-positive">₹{{ number_format($totalPaid, 2) }}</span>
                                @else
                                    <span class="amount-zero">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($pendingAmount > 0)
                                    <span class="amount-negative">₹{{ number_format($pendingAmount, 2) }}</span>
                                @else
                                    <span class="amount-positive">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($paymentStatus == 'paid')
                                    <span class="badge badge-paid rounded-pill px-3">Paid</span>
                                @elseif($paymentStatus == 'partial')
                                    <span class="badge badge-partial rounded-pill px-3">Partial</span>
                                @else
                                    <span class="badge badge-unpaid rounded-pill px-3">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $orderStatus = strtolower($o->order_status ?? 'pending');
                                    $statusClass = match($orderStatus) {
                                        'delivered' => 'badge-delivered',
                                        'returned' => 'badge-returned',
                                        'pending' => 'badge-pending',
                                        default => 'badge-pending'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }} rounded-pill px-3">
                                    {{ ucfirst($o->order_status ?? 'Pending') }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($o->order_date)->format('d-m-Y') }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                No orders found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $orders->appends(request()->except('orders_page'))->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- Order Items Table --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="fas fa-box me-2"></i>Order Items
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID</th>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Discount</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td><strong>#{{ $item->order_id }}</strong></td>
                            <td>{{ $item->product['name'] ?? 'N/A' }}</td>
                            <td>₹{{ number_format($item->price, 2) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->discount_on_product ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                No items found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $items->appends(request()->except('items_page'))->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
