@extends('layouts.admin.app')

@section('title', translate('Delivery Status'))

@push('css_or_js')
    <style>
        table {
            width: 100%;
        }
        .summary-card {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-card h6 {
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        .summary-card h3 {
            margin: 0;
            font-weight: bold;
        }
        .card-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .card-green { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .card-orange { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .card-purple { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <i class="tio-shopping-cart-outlined"></i>
                </span>
                <span>
                    {{ translate('Delivery Status') }}
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $orders->total() }}</span>
                </span>
            </h1>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card card-blue">
                    <h6>{{ translate('Total Orders') }}</h6>
                    <h3>{{ $totals['total_orders'] }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card card-green">
                    <h6>{{ translate('Total Amount') }}</h6>
                    <h3>₹ {{ number_format($totals['total_amount'], 2) }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card card-orange">
                    <h6>{{ translate('Total Paid') }}</h6>
                    <h3>₹ {{ number_format($totals['total_paid'], 2) }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="summary-card card-purple">
                    <h6>{{ translate('Total UPI Paid') }}</h6>
                    <h3>₹ {{ number_format($totals['total_upi_paid'], 2) }}</h3>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header shadow flex-wrap p-20px border-0">
                <h5 class="form-bold w-100 mb-3">{{ translate('Filter Orders') }}</h5>
                <form class="w-100" method="GET" action="{{ route('admin.delivery-status.index') }}" id="filterForm">
                    <div class="row g-3 g-sm-4 g-md-3 g-lg-4">

                        <!-- Start Date Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Start Date') }}</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $startDate ?? '' }}">
                        </div>

                        <!-- End Date Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('End Date') }}</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $endDate ?? '' }}">
                        </div>

                        <!-- Branch Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Branch') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="branch_id">
                                <option value="all" {{ ($branchId ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('All Branches') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch['id'] }}" {{ ($branchId ?? '') == $branch['id'] ? 'selected' : '' }}>
                                        {{ $branch['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Deliveryman Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Deliveryman') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="delivery_man_id">
                                <option value="all" {{ ($deliveryManId ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('All Deliverymen') }}</option>
                                @foreach ($deliveryMen as $dm)
                                    <option value="{{ $dm['id'] }}" {{ ($deliveryManId ?? '') == $dm['id'] ? 'selected' : '' }}>
                                        {{ $dm['f_name'] }} {{ $dm['l_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Route Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Route') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="route">
                                <option value="all" {{ ($routeFilter ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('All Routes') }}</option>
                                @foreach ($routes as $routeName)
                                    <option value="{{ $routeName }}" {{ ($routeFilter ?? '') == $routeName ? 'selected' : '' }}>
                                        {{ $routeName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Status Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Payment Status') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="payment_status">
                                <option value="all" {{ ($paymentStatus ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                <option value="unpaid" {{ ($paymentStatus ?? '') == 'unpaid' ? 'selected' : '' }}>{{ translate('Unpaid') }}</option>
                                <option value="partial" {{ ($paymentStatus ?? '') == 'partial' ? 'selected' : '' }}>{{ translate('Partial') }}</option>
                                <option value="paid" {{ ($paymentStatus ?? '') == 'paid' ? 'selected' : '' }}>{{ translate('Paid') }}</option>
                            </select>
                        </div>

                        <!-- Collection Status Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Collection Status') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="collection_status">
                                <option value="all" {{ ($collectionStatus ?? 'all') == 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                <option value="not_collected" {{ ($collectionStatus ?? '') == 'not_collected' ? 'selected' : '' }}>{{ translate('Not Collected') }}</option>
                                <option value="collected" {{ ($collectionStatus ?? '') == 'collected' ? 'selected' : '' }}>{{ translate('Collected') }}</option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Search') }}</label>
                            <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}" placeholder="{{ translate('Order ID, Customer name, Phone') }}">
                        </div>

                        <!-- Buttons -->
                        <div class="col-sm-6 col-md-12 col-lg-3 __btn-row align-self-end">
                            <a href="{{ route('admin.delivery-status.index') }}" class="btn w-100 btn--reset min-h-45px">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn w-100 btn--primary min-h-45px">{{ translate('Apply') }}</button>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="card-body p-20px">
                <div class="table-responsive datatable-custom">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('#') }}</th>
                                <th>{{ translate('Order ID') }}</th>
                                <th>{{ translate('Delivery Date') }}</th>
                                <th>{{ translate('Deliveryman') }}</th>
                                <th>{{ translate('Time Slot') }}</th>
                                <th>{{ translate('Customer') }}</th>
                                <th>{{ translate('Branch') }}</th>
                                <th>{{ translate('Route') }}</th>
                                <th>{{ translate('Total Amount') }}</th>
                                <th>{{ translate('Paid Amount') }}</th>
                                <th>{{ translate('Payment Status') }}</th>
                                <th>{{ translate('UPI Payment') }}</th>
                                <th>{{ translate('Collection Status') }}</th>
                                <th>{{ translate('Order Status') }}</th>
                                <th>{{ translate('Order Type') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $key => $order)
                                <tr>
                                    <td>{{ $orders->firstItem() + $key }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.details', ['id' => $order['id']]) }}">
                                            {{ $order['id'] }}
                                        </a>
                                    </td>
                                    <td>{{ $order->delivery_date ? $order->delivery_date->format('d M Y') : '-' }}</td>
                                    <td>
                                        @if($order->delivery_man)
                                            {{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}<br>
                                            <small>{{ $order->delivery_man->phone }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->time_slot)
                                            {{ date('h:i A', strtotime($order->time_slot->start_time)) }} - {{ date('h:i A', strtotime($order->time_slot->end_time)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->customer)
                                            {{ $order->customer->f_name }} {{ $order->customer->l_name }}<br>
                                            <small>{{ $order->customer->phone }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $order->branch ? $order->branch->name : '-' }}</td>
                                    <td>{{ $order->store && $order->store->route_name ? $order->store->route_name : '-' }}</td>
                                    <td>₹ {{ number_format($order->order_amount, 2) }}</td>
                                    <td>₹ {{ number_format($order->paid_amount ?? 0, 2) }}</td>
                                    <td>
                                        @if($order->payment_status == 'paid')
                                            <span class="badge badge-soft-success">{{ translate('Paid') }}</span>
                                        @elseif($order->payment_status == 'partial')
                                            <span class="badge badge-soft-warning">{{ translate('Partial') }}</span>
                                        @else
                                            <span class="badge badge-soft-danger">{{ translate('Unpaid') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->upi_amount > 0)
                                            ₹ {{ number_format($order->upi_amount, 2) }}<br>
                                            <small>{{ $order->upi_transaction_id }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span id="collection-status-{{ $order->id }}" class="badge {{ $order->is_collected ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                            {{ $order->is_collected ? translate('Collected') : translate('Not Collected') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-info text-capitalize">{{ translate($order->order_status) }}</span>
                                    </td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $order->order_type) }}</td>
                                    <td>
                                        @if(!$order->is_collected)
                                            <button type="button" class="btn btn-sm btn-success" onclick="markAsCollected({{ $order->id }}, event)">
                                                {{ translate('Mark as Collected') }}
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                {{ translate('Collected') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {!! $orders->appends(request()->all())->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        function markAsCollected(orderId, event) {
            let button = event.target;
            
            if (button.disabled) return;
            
            button.disabled = true;
            button.classList.add('disabled');
            
            fetch(`/admin/delivery-status/mark-collected/${orderId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    toastr.success('{{ translate("Order marked as collected") }}');
                    // Update collection status badge
                    let badge = document.getElementById(`collection-status-${orderId}`);
                    badge.textContent = '{{ translate("Collected") }}';
                    badge.classList.remove('badge-soft-warning');
                    badge.classList.add('badge-soft-success');
                    
                    // Update button
                    button.textContent = '{{ translate("Collected") }}';
                    button.classList.remove('btn-success');
                    button.classList.add('btn-secondary');
                } else {
                    toastr.error(data.message);
                    button.disabled = false;
                    button.classList.remove('disabled');
                }
            })
            .catch(err => {
                toastr.error('{{ translate("Error marking order as collected") }}');
                button.disabled = false;
                button.classList.remove('disabled');
            });
        }
    </script>
@endpush
