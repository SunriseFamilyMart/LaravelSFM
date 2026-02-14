@extends('layouts.admin.app')

@section('title', translate('Delivery Status'))

@push('css_or_js')
    <style>
        table {
            width: 100%;
        }
        .btn-collected {
            background-color: #6c757d;
            color: white;
            cursor: not-allowed;
        }
    </style>
@endpush

@php
    // Define total column count for maintainability
    $totalColumns = 13;
@endphp

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/all_orders.png') }}" class="w--20" alt="">
                </span>
                <span class="">
                    {{ translate('Delivery Status') }}
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $orders->total() }}</span>
                </span>
            </h1>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-header-title">
                            {{ translate('Delivered Orders') }}
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('admin.delivery-status.index') }}" method="GET">
                            <div class="input-group input-group-merge input-group-flush">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="tio-search"></i>
                                    </div>
                                </div>
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="{{ translate('Search by order ID') }}" aria-label="Search orders"
                                    value="{{ $search }}" required>
                                <button type="submit" class="btn btn-primary">{{ translate('search') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <form action="{{ route('admin.delivery-status.index') }}" method="GET">
                            <select name="branch_id" class="custom-select" onchange="this.form.submit()">
                                <option value="all" {{ $branchId == 'all' ? 'selected' : '' }}>
                                    {{ translate('All Branches') }}
                                </option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            </div>

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
                            <th>{{ translate('Total Amount') }}</th>
                            <th>{{ translate('Paid Amount') }}</th>
                            <th>{{ translate('UPI Payment') }}</th>
                            <th>{{ translate('Order Status') }}</th>
                            <th>{{ translate('Order Type') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($orders as $key => $order)
                            <tr>
                                <td>{{ $orders->firstItem() + $key }}</td>
                                <td>
                                    <a href="{{ route('admin.orders.details', ['id' => $order['id']]) }}">
                                        {{ $order['id'] }}
                                    </a>
                                </td>
                                <td>
                                    @if($order['delivery_date'])
                                        {{ date('d M Y', strtotime($order['delivery_date'])) }}
                                    @else
                                        <span class="text-muted">{{ translate('No Delivery Date') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($order->delivery_man)
                                        {{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}
                                        <br>
                                        <small>{{ $order->delivery_man->phone }}</small>
                                    @else
                                        <span class="badge badge-danger">{{ translate('Not Assigned') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->time_slot)
                                        {{ date(config('time_format'), strtotime($order->time_slot['start_time'])) }} - 
                                        {{ date(config('time_format'), strtotime($order->time_slot['end_time'])) }}
                                    @else
                                        {{ translate('No Time Slot') }}
                                    @endif
                                </td>
                                <td>
                                    @if ($order->store_id && $order->store)
                                        <div>
                                            <span class="text-capitalize font-medium">
                                                <i class="tio-shop mr-1"></i>{{ $order->store->store_name ?? $order->store->customer_name }}
                                            </span>
                                        </div>
                                        @if($order->store->phone_number)
                                            <div class="text-sm">
                                                <a href="tel:{{ $order->store->phone_number }}">{{ $order->store->phone_number }}</a>
                                            </div>
                                        @endif
                                    @elseif ($order->is_guest == 0)
                                        @if (isset($order->customer))
                                            <div>
                                                <a class="text-body text-capitalize font-medium"
                                                    href="{{ route('admin.customer.view', [$order['user_id']]) }}">
                                                    {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                                                </a>
                                            </div>
                                            <div class="text-sm">
                                                <a href="Tel:{{ $order->customer['phone'] }}">{{ $order->customer['phone'] }}</a>
                                            </div>
                                        @else
                                            <label class="text-danger">{{ translate('Customer not available') }}</label>
                                        @endif
                                    @else
                                        <label class="text-success">{{ translate('Guest Customer') }}</label>
                                    @endif
                                </td>
                                <td>
                                    <label class="badge badge-soft-primary">
                                        {{ optional($order->branch)->name ?? 'Branch deleted!' }}
                                    </label>
                                </td>
                                <td>
                                    {{ Helpers::set_symbol($order->order_amount) }}
                                </td>
                                <td>
                                    {{ Helpers::set_symbol($order->paid_amount ?? 0) }}
                                </td>
                                <td>
                                    @if($order->upi_payment)
                                        <span class="badge badge-soft-success">
                                            ₹ {{ number_format($order->upi_payment->amount, 2) }} | {{ $order->upi_payment->transaction_ref }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-success">
                                        {{ translate($order->order_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info">
                                        {{ translate($order->order_type) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($order->is_collected)
                                        <button class="btn btn-sm btn-collected" disabled>
                                            <i class="tio-checkmark-circle"></i> {{ translate('Collected') }}
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-primary mark-collected-btn" 
                                                data-order-id="{{ $order->id }}">
                                            <i class="tio-checkmark-circle"></i> {{ translate('Mark as Collected') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalColumns }}" class="text-center">
                                    <div class="py-5">
                                        <img src="{{ asset('public/assets/admin/img/no-data.png') }}" 
                                             alt="{{ translate('No orders found') }}" class="w--100">
                                        <p class="mt-3">{{ translate('No delivered orders found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {!! $orders->links() !!}
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    $(document).ready(function() {
        // Mark as Collected button click handler
        $('.mark-collected-btn').on('click', function() {
            let button = $(this);
            let orderId = button.data('order-id');
            
            // Disable button immediately to prevent double-clicks
            button.prop('disabled', true);
            
            $.ajax({
                url: '{{ url('admin/delivery-status/mark-collected') }}/' + orderId,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Update button appearance
                        button.removeClass('btn-primary')
                              .addClass('btn-collected')
                              .html('<i class="tio-checkmark-circle"></i> {{ translate('Collected') }}');
                        
                        // Show success message
                        toastr.success(response.message);
                    } else {
                        // Re-enable button on error
                        button.prop('disabled', false);
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    // Re-enable button on error
                    button.prop('disabled', false);
                    
                    let errorMessage = '{{ translate('An error occurred') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        });
    });
</script>
@endpush
