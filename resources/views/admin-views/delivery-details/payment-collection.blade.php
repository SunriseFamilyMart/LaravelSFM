@extends('layouts.admin.app')

@section('title', translate('Payment Collection'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <i class="tio-money nav-icon"></i>
                </span>
                <span>{{ translate('Payment Collection') }}</span>
            </h1>
        </div>

        <!-- Branch-level Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">{{ translate('Total Orders') }}</h6>
                        <h3 class="mb-0">{{ $branchTotals['total_orders'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">{{ translate('Total Amount') }}</h6>
                        <h3 class="mb-0">₹{{ number_format($branchTotals['total_amount'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">{{ translate('Paid Amount') }}</h6>
                        <h3 class="mb-0 text-success">₹{{ number_format($branchTotals['paid_amount'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">{{ translate('Due/Arrear') }}</h6>
                        <h3 class="mb-0 text-danger">₹{{ number_format($branchTotals['due_amount'], 2) }}</h3>
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
                <form method="GET" action="{{ route('admin.delivery-details.payment-collection') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label>{{ translate('Branch') }}</label>
                            <select name="branch_id" class="form-control">
                                <option value="all" {{ $branchId == 'all' ? 'selected' : '' }}>{{ translate('All Branches') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>{{ translate('Store') }}</label>
                            <select name="store_id" class="form-control">
                                <option value="all" {{ $storeId == 'all' ? 'selected' : '' }}>{{ translate('All Stores') }}</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name ?? $store->customer_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('Payment Status') }}</label>
                            <select name="payment_status" class="form-control">
                                <option value="all" {{ $paymentStatus == 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                <option value="paid" {{ $paymentStatus == 'paid' ? 'selected' : '' }}>{{ translate('Paid') }}</option>
                                <option value="partial" {{ $paymentStatus == 'partial' ? 'selected' : '' }}>{{ translate('Partial') }}</option>
                                <option value="unpaid" {{ $paymentStatus == 'unpaid' ? 'selected' : '' }}>{{ translate('Unpaid') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('Start Date') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-2">
                            <label>{{ translate('End Date') }}</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                            <a href="{{ route('admin.delivery-details.payment-collection') }}" class="btn btn-secondary">{{ translate('Clear') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Store Balances Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Store Outstanding Balances') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Store Name') }}</th>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Total Orders') }}</th>
                                <th>{{ translate('Total Amount') }}</th>
                                <th>{{ translate('Paid Amount') }}</th>
                                <th>{{ translate('Due/Arrear') }}</th>
                                <th>{{ translate('Last Payment Date') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($storeBalances as $store)
                                <tr>
                                    <td>{{ $store->store_name ?? $store->customer_name }}</td>
                                    <td>{{ $store->phone_number }}</td>
                                    <td>{{ $store->total_orders }}</td>
                                    <td>₹{{ number_format($store->total_amount, 2) }}</td>
                                    <td class="text-success">₹{{ number_format($store->paid_amount, 2) }}</td>
                                    <td class="text-danger">₹{{ number_format($store->due_amount, 2) }}</td>
                                    <td>{{ $store->last_payment_date ? \Carbon\Carbon::parse($store->last_payment_date)->format('Y-m-d') : 'N/A' }}</td>
                                    <td>
                                        @if ($store->due_amount > 0)
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                data-toggle="modal" 
                                                data-target="#recordPaymentModal"
                                                data-store-id="{{ $store->id }}"
                                                data-store-name="{{ $store->store_name ?? $store->customer_name }}"
                                                data-due-amount="{{ $store->due_amount }}">
                                                {{ translate('Record Payment') }}
                                            </button>
                                        @else
                                            <span class="badge badge-success">{{ translate('Fully Paid') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ translate('No stores found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.delivery-details.payment-collection.record') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Record Payment') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="store_id" id="payment_store_id">
                        
                        <div class="form-group">
                            <label>{{ translate('Store Name') }}</label>
                            <input type="text" class="form-control" id="payment_store_name" readonly>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Due Amount') }}</label>
                            <input type="text" class="form-control" id="payment_due_amount" readonly>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Payment Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">{{ translate('Cash') }}</option>
                                <option value="upi">{{ translate('UPI') }}</option>
                                <option value="bank">{{ translate('Bank Transfer') }}</option>
                                <option value="cheque">{{ translate('Cheque') }}</option>
                                <option value="other">{{ translate('Other') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>{{ translate('Transaction Reference') }}</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="UPI Ref / Cheque No / Transaction ID">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Record Payment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        $('#recordPaymentModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var storeId = button.data('store-id');
            var storeName = button.data('store-name');
            var dueAmount = button.data('due-amount');

            var modal = $(this);
            modal.find('#payment_store_id').val(storeId);
            modal.find('#payment_store_name').val(storeName);
            modal.find('#payment_due_amount').val('₹' + parseFloat(dueAmount).toFixed(2));
        });
    </script>
    @endpush
@endsection
