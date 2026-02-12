@extends('layouts.admin.app')

@section('title', translate('Purchase Management'))

@push('css_or_js')
    <style>
        table {
            width: 100%;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-draft { background-color: #f0f0f0; color: #666; }
        .status-ordered { background-color: #e3f2fd; color: #1976d2; }
        .status-partial_delivered { background-color: #fff3e0; color: #f57c00; }
        .status-delivered { background-color: #e8f5e9; color: #388e3c; }
        .status-cancelled { background-color: #ffebee; color: #d32f2f; }
        .status-delayed { background-color: #fce4ec; color: #c2185b; }
        
        .payment-unpaid { background-color: #ffebee; color: #d32f2f; }
        .payment-partial { background-color: #fff3e0; color: #f57c00; }
        .payment-paid { background-color: #e8f5e9; color: #388e3c; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <i class="tio-shopping-cart"></i>
                </span>
                <span>
                    {{ translate('Purchase Management') }}
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $purchases->total() }}</span>
                </span>
            </h1>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{ translate('Total Purchases') }}</h6>
                        <h2 class="card-title">{{ $totalPurchases }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{ translate('Total Amount') }}</h6>
                        <h2 class="card-title">₹{{ number_format($totalAmount, 2) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{ translate('Paid Amount') }}</h6>
                        <h2 class="card-title text-success">₹{{ number_format($totalPaid, 2) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{ translate('Outstanding') }}</h6>
                        <h2 class="card-title text-danger">₹{{ number_format($totalOutstanding, 2) }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Counts -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <h6>{{ translate('Draft') }}</h6>
                                <h4>{{ $statusCounts['draft'] }}</h4>
                            </div>
                            <div class="col-md-2 text-center">
                                <h6>{{ translate('Ordered') }}</h6>
                                <h4>{{ $statusCounts['ordered'] }}</h4>
                            </div>
                            <div class="col-md-2 text-center">
                                <h6>{{ translate('Partial Delivered') }}</h6>
                                <h4>{{ $statusCounts['partial_delivered'] }}</h4>
                            </div>
                            <div class="col-md-2 text-center">
                                <h6>{{ translate('Delivered') }}</h6>
                                <h4>{{ $statusCounts['delivered'] }}</h4>
                            </div>
                            <div class="col-md-2 text-center">
                                <h6>{{ translate('Cancelled') }}</h6>
                                <h4>{{ $statusCounts['cancelled'] }}</h4>
                            </div>
                            <div class="col-md-2 text-center">
                                <h6>{{ translate('Delayed') }}</h6>
                                <h4>{{ $statusCounts['delayed'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Filters') }}</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.purchase.index') }}">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>{{ translate('Supplier') }}</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">{{ translate('All') }}</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="">{{ translate('All') }}</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ translate('Draft') }}</option>
                                <option value="ordered" {{ request('status') == 'ordered' ? 'selected' : '' }}>{{ translate('Ordered') }}</option>
                                <option value="partial_delivered" {{ request('status') == 'partial_delivered' ? 'selected' : '' }}>{{ translate('Partial Delivered') }}</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>{{ translate('Delivered') }}</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                                <option value="delayed" {{ request('status') == 'delayed' ? 'selected' : '' }}>{{ translate('Delayed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('Payment Status') }}</label>
                            <select name="payment_status" class="form-control">
                                <option value="">{{ translate('All') }}</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>{{ translate('Unpaid') }}</option>
                                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>{{ translate('Partial') }}</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>{{ translate('Paid') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('From Date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('To Date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('Search PR Number') }}</label>
                            <input type="text" name="search" class="form-control" placeholder="PR-..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                            <a href="{{ route('admin.purchase.index') }}" class="btn btn-secondary">{{ translate('Clear') }}</a>
                            <a href="{{ route('admin.purchase.create') }}" class="btn btn-success float-right">
                                <i class="tio-add"></i> {{ translate('Create New Purchase') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Purchases Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('PR Number') }}</th>
                                <th>{{ translate('Supplier') }}</th>
                                <th>{{ translate('Purchase Date') }}</th>
                                <th>{{ translate('Expected Delivery') }}</th>
                                <th>{{ translate('Items') }}</th>
                                <th>{{ translate('Total Amount') }}</th>
                                <th>{{ translate('Paid') }}</th>
                                <th>{{ translate('Balance') }}</th>
                                <th>{{ translate('Payment Status') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.purchase.show', $purchase->id) }}" class="font-weight-bold">
                                            {{ $purchase->pr_number }}
                                        </a>
                                    </td>
                                    <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                    <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                                    <td>{{ $purchase->expected_delivery_date ? $purchase->expected_delivery_date->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $purchase->items->count() }}</td>
                                    <td>₹{{ number_format($purchase->total_amount, 2) }}</td>
                                    <td class="text-success">₹{{ number_format($purchase->paid_amount, 2) }}</td>
                                    <td class="text-danger">₹{{ number_format($purchase->balance_amount, 2) }}</td>
                                    <td>
                                        <span class="status-badge payment-{{ $purchase->payment_status }}">
                                            {{ translate(ucfirst($purchase->payment_status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $purchase->status }}">
                                            {{ translate(ucfirst(str_replace('_', ' ', $purchase->status))) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.purchase.show', $purchase->id) }}" class="btn btn-sm btn-white" title="{{ translate('View') }}">
                                                <i class="tio-visible"></i>
                                            </a>
                                            @if(in_array($purchase->status, ['draft', 'ordered']))
                                                <a href="{{ route('admin.purchase.edit', $purchase->id) }}" class="btn btn-sm btn-white" title="{{ translate('Edit') }}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">{{ translate('No purchases found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {!! $purchases->links() !!}
            </div>
        </div>
    </div>
@endsection
