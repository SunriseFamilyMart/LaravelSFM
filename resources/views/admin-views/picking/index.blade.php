@extends('layouts.admin.app')

@section('title', translate('Picking Orders'))

@push('css_or_js')
    <style>
        table {
            width: 100%;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/all_orders.png') }}" class="w--20" alt="">
                </span>
                <span class="">
                    {{ translate('Picking Orders') }}
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $orders->total() }}</span>
                </span>
            </h1>
        </div>

        <div class="card">
            <div class="card-header shadow flex-wrap p-20px border-0">
                <h5 class="form-bold w-100 mb-3">{{ translate('Filter Orders') }}</h5>
                <form class="w-100" method="GET" action="{{ route('admin.picking.index') }}">
                    <div class="row g-3 g-sm-4 g-md-3 g-lg-4">

                        <!-- From Date Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('From Date') }}</label>
                            <input type="date" class="form-control" name="from" value="{{ $from ?? '' }}">
                        </div>

                        <!-- To Date Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('To Date') }}</label>
                            <input type="date" class="form-control" name="to" value="{{ $to ?? '' }}">
                        </div>

                        <!-- Branch Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="input-label">{{ translate('Branch') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="branch_id">
                                <option disabled>--- {{ translate('select') }} {{ translate('branch') }} ---</option>
                                <option value="all" {{ ($branchId ?? '') == 'all' ? 'selected' : '' }}>{{ translate('all') }} {{ translate('branch') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch['id'] }}" {{ ($branchId ?? '') == $branch['id'] ? 'selected' : '' }}>
                                        {{ $branch['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="col-sm-6 col-md-12 col-lg-3 __btn-row align-self-end">
                            <a href="{{ route('admin.picking.index') }}" class="btn w-100 btn--reset min-h-45px">{{ translate('clear') }}</a>
                            <button type="submit" class="btn w-100 btn--primary min-h-45px">{{ translate('show data') }}</button>
                        </div>

                    </div>
                </form>
            </div>

            <div class="card-body p-20px">
                <div class="order-top">
                    <div class="card--header">
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group">
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="{{ translate('Ex : Search by Order ID') }}"
                                    aria-label="Search" value="{{ $search }}" required autocomplete="off">
                                <div class="input-group-append">
                                    <button type="submit" class="input-group-text">
                                        {{ translate('Search') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <form id="bulkAssignForm" method="POST" action="{{ route('admin.picking.bulk-assign') }}">
                        @csrf
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>{{ translate('#') }}</th>
                                    <th class="table-column-pl-0">{{ translate('order ID') }}</th>
                                    <th>{{ translate('customer') }}</th>
                                    <th>{{ translate('branch') }}</th>
                                    <th>{{ translate('items count') }}</th>
                                    <th>{{ translate('total amount') }}</th>
                                    <th>{{ translate('status') }}</th>
                                    <th>
                                        <div class="text-center">
                                            {{ translate('action') }}
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach ($orders as $key => $order)
                                    <tr class="status-{{ $order['order_status'] }} class-all">
                                        <td>
                                            <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox">
                                        </td>
                                        <td class="">
                                            {{ $orders->firstItem() + $key }}
                                        </td>
                                        <td class="table-column-pl-0">
                                            <a href="{{ route('admin.picking.show', ['id' => $order['id']]) }}">{{ $order['id'] }}</a>
                                        </td>
                                        <td>
                                            @if ($order->is_guest == 0)
                                                @if (isset($order->customer))
                                                    <div>
                                                        <a class="text-body text-capitalize font-medium"
                                                            href="{{ route('admin.customer.view', [$order['user_id']]) }}">
                                                            {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                                                        </a>
                                                    </div>
                                                    <a class="d-block text-body font-size-sm"
                                                        href="tel:{{ $order->customer['phone'] }}">{{ $order->customer['phone'] }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('Customer not found') }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ translate('Guest Customer') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($order->branch)
                                                {{ $order->branch->name }}
                                            @else
                                                <span class="badge badge-danger">{{ translate('Branch deleted') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $order->details->count() }}
                                        </td>
                                        <td>
                                            {{ \App\CentralLogics\Helpers::set_symbol($order['order_amount']) }}
                                        </td>
                                        <td>
                                            @if ($order->order_status == 'confirmed')
                                                <span class="badge badge-soft-info">{{ translate('confirmed') }}</span>
                                            @elseif($order->order_status == 'picking')
                                                <span class="badge badge-soft-warning">{{ translate('picking') }}</span>
                                            @elseif($order->order_status == 'processing')
                                                <span class="badge badge-soft-primary">{{ translate('processing') }}</span>
                                            @elseif($order->order_status == 'packaging')
                                                <span class="badge badge-soft-dark">{{ translate('packaging') }}</span>
                                            @endif
                                            
                                            @if ($order->all_picked ?? false)
                                                <br><span class="badge badge-success mt-1">{{ translate('Picked') }} ✓</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                    href="{{ route('admin.picking.show', ['id' => $order['id']]) }}">
                                                    <i class="tio-invisible"></i> {{ translate('Pick') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Bulk Assign Section -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <h5>{{ translate('Bulk Assign Delivery Man') }}</h5>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <select class="custom-select" name="delivery_man_id" id="delivery_man_id" required>
                                            <option value="">{{ translate('Select Delivery Man') }}</option>
                                            @foreach ($deliveryMen as $dm)
                                                <option value="{{ $dm->id }}">{{ $dm->f_name }} {{ $dm->l_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-primary" id="bulkAssignBtn">
                                            {{ translate('Assign Selected Orders') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-footer">
                    {!! $orders->links('pagination::bootstrap-4') !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        // Select all checkbox
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk assign form validation
        document.getElementById('bulkAssignForm').addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            const deliveryManId = document.getElementById('delivery_man_id').value;

            if (checkedBoxes.length === 0) {
                e.preventDefault();
                toastr.error('{{ translate("Please select at least one order") }}');
                return false;
            }

            if (!deliveryManId) {
                e.preventDefault();
                toastr.error('{{ translate("Please select a delivery man") }}');
                return false;
            }
        });
    </script>
@endpush
