@extends('layouts.admin.app')

@section('title', translate('Order List'))

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
                    @if ($status == 'processing')
                        {{ translate(ucwords(str_replace('_', ' ', 'Packaging'))) }} {{ translate('Orders') }}
                    @elseif($status == 'failed')
                        {{ translate(ucwords(str_replace('_', ' ', 'Failed to Deliver'))) }} {{ translate('Orders') }}
                    @else
                        {{ translate(ucwords(str_replace('_', ' ', $status))) }} {{ translate('Orders') }}
                    @endif
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $orders->total() }}</span>
                </span>

            </h1>
        </div>

        <div class="card">
            <div class="card-header shadow flex-wrap p-20px border-0">
                <h5 class="form-bold w-100 mb-3">{{ translate('Select Date Range') }}</h5>
            <form class="w-100" method="GET" action="{{ route('admin.orders.list', [$status ?? 'all']) }}">
                <div class="row g-3 g-sm-4 g-md-3 g-lg-4">

                    <!-- Branch Filter -->
                    <div class="col-sm-6 col-md-4 col-lg-2">
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

                    <!-- Delivery Man Filter -->
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="delivery_man_id">
                            <option disabled selected>--- {{ translate('select') }} {{ translate('delivery_man') }} ---</option>
                            <option value="all" {{ ($deliveryManId ?? '') == 'all' ? 'selected' : '' }}>All Delivery Man</option>
                            @foreach ($deliveryMen as $dm)
                                <option value="{{ $dm->id }}" {{ ($deliveryManId ?? '') == $dm->id ? 'selected' : '' }}>
                                    {{ $dm->f_name }} {{ $dm->l_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="payment_method">
                            <option disabled selected>--- {{ translate('select') }} {{ translate('payment_method') }} ---</option>
                            <option value="all" {{ ($paymentMethod ?? '') == 'all' ? 'selected' : '' }}>All</option>
                            <option value="cash" {{ ($paymentMethod ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="upi" {{ ($paymentMethod ?? '') == 'upi' ? 'selected' : '' }}>UPI</option>
                            <option value="credit_sale" {{ ($paymentMethod ?? '') == 'credit_sale' ? 'selected' : '' }}>Credit Sale</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="input-date-group">
                            <label class="input-label" for="start_date">{{ translate('Start Date') }}</label>
                            <input type="text" id="start_date" name="start_date" value="{{ $startDate ?? '' }}"
                                class="js-flatpickr form-control flatpickr-custom min-h-45px" placeholder="yy-mm-dd"
                                data-hs-flatpickr-options='{ "dateFormat": "Y-m-d"}'>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="input-date-group">
                            <label class="input-label" for="end_date">{{ translate('End Date') }}</label>
                            <input type="text" id="end_date" name="end_date" value="{{ $endDate ?? '' }}"
                                class="js-flatpickr form-control flatpickr-custom min-h-45px" placeholder="yy-mm-dd"
                                data-hs-flatpickr-options='{ "dateFormat": "Y-m-d"}'>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="col-sm-6 col-md-12 col-lg-4 __btn-row">
                        <a href="{{ route('admin.orders.list', ['all']) }}" class="btn w-100 btn--reset min-h-45px">{{ translate('clear') }}</a>
                        <button type="submit" class="btn w-100 btn--primary min-h-45px">{{ translate('show data') }}</button>
                    </div>

                </div>
            </form>

            </div>

            @if ($status == 'all')
                <div class="p-20px pb-0 mt-4">
<div class="row g-3 g-sm-4 g-md-3 g-lg-4">

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['pending']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/pending.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('pending') }}</span>
                                    </h6>
                                    <span class="card-title text-0661CB">
                                        {{ $countData['pending'] }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['confirmed']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/confirmed.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('confirmed') }}</span>
                                    </h6>
                                    <span class="card-title text-107980">
                                        {{ $countData['confirmed'] }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['processing']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/processing.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('packaging') }}</span>
                                    </h6>
                                    <span class="card-title text-danger">
                                        {{ $countData['processing'] }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['out_for_delivery']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('/public/assets/admin/img/delivery/out-for-delivery.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('out_for_delivery') }}</span>
                                    </h6>
                                    <span class="card-title text-00B2BE">
                                        {{ $countData['out_for_delivery'] }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['delivered']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/1.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('delivered') }}</span>
                                    </h6>
                                    <span class="card-title text-success">
                                        {{ $countData['delivered'] }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['all']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/2.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('Canceled') }}</span>
                                    </h6>
                                    <span class="card-title text-danger">
                                        {{ $countData['canceled'] }}
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['returned']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/3.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('returned') }}</span>
                                    </h6>
                                    <span class="card-title text-warning">
                                        {{ $countData['returned'] }}
                                    </span>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <a class="order--card h-100" href="{{ route('admin.orders.list', ['failed']) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                        <img src="{{ asset('public/assets/admin/img/delivery/4.png') }}"
                                            alt="{{ translate('dashboard') }}" class="oder--card-icon">
                                        <span>{{ translate('failed_to_deliver') }}</span>
                                    </h6>
                                    <span class="card-title text-danger">
                                        {{ $countData['failed'] }}
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if($status=='returned')
                @php
                    $rt = $returnType ?? request('return_type','all');
                @endphp
                <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ request()->fullUrlWithQuery(['return_type'=>'all','page'=>1]) }}"
                       class="btn btn-sm {{ $rt=='all' ? 'btn--primary' : 'btn-outline-primary-2' }}">
                        {{ translate('Returned') }} ({{ $returnedTypeCounts['all'] ?? 0 }})
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['return_type'=>'partial','page'=>1]) }}"
                       class="btn btn-sm {{ $rt=='partial' ? 'btn--primary' : 'btn-outline-primary-2' }}">
                        {{ translate('Partial Return') }} ({{ $returnedTypeCounts['partial'] ?? 0 }})
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['return_type'=>'full','page'=>1]) }}"
                       class="btn btn-sm {{ $rt=='full' ? 'btn--primary' : 'btn-outline-primary-2' }}">
                        {{ translate('Full Return') }} ({{ $returnedTypeCounts['full'] ?? 0 }})
                    </a>
                    <span class="text-muted small ml-2">{{ translate('Based on order edit logs') }}</span>
                </div>
            @endif


            <div class="card-body p-20px">
                <div class="order-top">
                    <div class="card--header">
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group">
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="{{ translate('Ex : Search by ID, order or payment status') }}"
                                    aria-label="Search" value="{{ $search }}" required autocomplete="off">
                                <div class="input-group-append">
                                    <button type="submit" class="input-group-text">
                                        {{ translate('Search') }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Bulk Assign Button -->
                        <a class="btn btn-primary shadow-sm fw-semibold me-2"
                            href="{{ route('admin.delivery_trips.create') }}">
                            {{ translate('Assign Bulk Orders') }}
                        </a>
                        <div class="hs-unfold mr-2">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-outline-primary-2 dropdown-toggle min-height-40"
                                href="javascript:;"
                                data-hs-unfold-options='{
                                        "target": "#usersExportDropdown",
                                        "type": "css-animation"
                                    }'>
                                <i class="tio-download-to mr-1"></i> {{ translate('export') }}
                            </a>

                            <div id="usersExportDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                <span class="dropdown-header">{{ translate('download') }}
                                    {{ translate('options') }}</span>
                                <a id="export-excel" class="dropdown-item"
                                    href="{{ route('admin.orders.export', [$status, 'branch_id' => Request::get('branch_id'), 'start_date' => Request::get('start_date'), 'end_date' => Request::get('end_date'), 'search' => Request::get('search')]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                        alt="{{ translate('Image Description') }}">
                                    {{ translate('excel') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <table
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>
                                    {{ translate('#') }}
                                </th>
                                <th class="table-column-pl-0">{{ translate('order ID') }}</th>
                                <th>{{ translate('Delivery') }} {{ translate('date') }}</th>
                                <th>{{ translate('Deliveryman') }}</th>
                                
                                <th>{{ translate('Time Slot') }}</th>
                                <th>{{ translate('customer') }}</th>
                                <th>{{ translate('branch') }}</th>
                                <th>{{ translate('Order amount') }}</th>
                               <th>{{ translate('Paid Amount') }}</th>
                                           
                                <th>
                                    <div class="text-center">
                                        {{ translate('order') }} {{ translate('status') }}
                                    </div>
                                </th>
                                                                @if($status=='returned')
                                <th>
                                    <div class="text-center">
                                        {{ translate('Return') }}
                                    </div>
                                </th>
                                @endif

<th>
                                    <div class="text-center">
                                        {{ translate('order') }} {{ translate('type') }}
                                    </div>
                                </th>
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
                                    <td class="">
                                        {{ $orders->firstItem() + $key }}
                                    </td>
                                    <td class="table-column-pl-0">
                                        <a
                                            href="{{ route('admin.orders.details', ['id' => $order['id']]) }}">{{ $order['id'] }}</a>
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
                                            <span class="badge badge-danger">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span>{{ $order->time_slot ? date(config('time_format'), strtotime($order->time_slot['start_time'])) . ' - ' . date(config('time_format'), strtotime($order->time_slot['end_time'])) : translate('No Time Slot') }}</span>
                                    </td>
                                    <td>
                                        @if ($order->is_guest == 0)
                                            @if (isset($order->customer))
                                                <div>
                                                    <a class="text-body text-capitalize font-medium"
                                                        href="{{ route('admin.customer.view', [$order['user_id']]) }}">{{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}</a>
                                                </div>
                                                <div class="text-sm">
                                                    <a
                                                        href="Tel:{{ $order->customer['phone'] }}">{{ $order->customer['phone'] }}</a>
                                                </div>
                                            @elseif($order->user_id != null && !isset($order->customer))
                                                <label class="text-danger">{{ translate('Customer_not_available') }}
                                                </label>
                                            @else
                                                <label class="text-success">{{ translate('Walking Customer') }}
                                                </label>
                                            @endif
                                        @else
                                            <label class="text-success">{{ translate('Guest Customer') }}
                                            </label>
                                        @endif

                                    </td>
                                    <td>
                                        <label class="badge badge-soft-primary">
                                            {{ optional($order->branch)->name ?? 'Branch deleted!' }}
                                        </label>
                                    </td>


                                    <td>
                                        <div class="mw-90">
                                            <div>
                                                <?php
                                                $firstDetail = optional($order->details->first());
                                                $vatStatus = $firstDetail->vat_status ?? '';
                                                
                                                $orderAmount = $vatStatus === 'included' ? $order->order_amount - $order->total_tax_amount : $order->order_amount;
                                                ?>

                                                {{ Helpers::set_symbol($orderAmount) }}

                                            </div>
                                            @if ($order->payment_status == 'paid')
                                                <span class="text-success">
                                                    {{ translate('paid') }}
                                                </span>
                                            @else
                                                <span class="text-danger">
                                                    {{ translate($order['payment_status']) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
    @php
        $completedPayments = $order->payments
            ->where('payment_status', 'complete');
    @endphp

    @if($completedPayments->isEmpty())
        -
    @else
        <ul class="mb-0">
            @foreach($completedPayments as $payment)
                <li>
                    {{ ucfirst($payment->payment_method) }}: ₹{{ number_format($payment->amount, 2) }}
                    
                </li>
            @endforeach
        </ul>
    @endif
</td>

                                    <td class="text-capitalize text-center">
                                        @if ($order['order_status'] == 'pending')
                                            <span class="badge badge-soft-info">
                                                {{ translate('pending') }}
                                            </span>
                                        @elseif($order['order_status'] == 'confirmed')
                                            <span class="badge badge-soft-info">
                                                {{ translate('confirmed') }}
                                            </span>
                                        @elseif($order['order_status'] == 'processing')
                                            <span class="badge badge-soft-warning">
                                                {{ translate('packaging') }}
                                            </span>
                                        @elseif($order['order_status'] == 'out_for_delivery')
                                            <span class="badge badge-soft-warning">
                                                {{ translate('out_for_delivery') }}
                                            </span>
                                        @elseif($order['order_status'] == 'delivered')
                                            <span class="badge badge-soft-success">
                                                {{ translate('delivered') }}
                                            </span>
                                        @else
                                            <span class="badge badge-soft-danger">
                                                {{ translate(str_replace('_', ' ', $order['order_status'])) }}
                                            </span>
                                        @endif
                                    </td>
                                                                        @if($status=='returned')
                                    @php
                                        $meta = $returnMeta[$order['id']] ?? null;
                                        $rtype = $meta['type'] ?? 'full';
                                        $badgeClass = $rtype=='partial' ? 'badge-soft-warning' : 'badge-soft-success';
                                    @endphp
                                    <td class="text-center">
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $rtype=='partial' ? translate('Partial') : translate('Full') }}
                                        </span>
                                        <div class="small text-muted mt-1">
                                            {{ (int)($meta['items_count'] ?? 0) }} {{ translate('items') }} •
                                            {{ (int)($meta['total_return_qty'] ?? 0) }} {{ translate('qty') }}
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary-2 mt-2 js-return-view"
                                                data-order-id="{{ $order['id'] }}">
                                            {{ translate('View') }}
                                        </button>
                                    </td>
                                    @endif

<td class="text-capitalize text-center">
                                        @if ($order['order_type'] == 'take_away')
                                            <span class="badge badge-soft-info">
                                                {{ translate('take_away') }}
                                            </span>
                                        @elseif($order['order_type'] == 'pos')
                                            <span class="badge badge-soft-info">
                                                {{ translate('POS') }}
                                            </span>
                                        @else
                                            <span class="badge badge-soft-success">
                                                {{ translate($order['order_type']) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="action-btn btn--primary btn-outline-primary"
                                                href="{{ route('admin.orders.details', ['id' => $order['id']]) }}"><i
                                                    class="tio-invisible"></i></a>
                                            <a class="action-btn btn-outline-primary-2" target="_blank"
                                                href="{{ route('admin.orders.generate-invoice', [$order['id']]) }}">
                                                <i class="tio-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($orders) == 0)
                    <div class="text-center p-4">
                        <img class="w-120px mb-3" src="{{ asset('public/assets/admin') }}/svg/illustrations/sorry.svg"
                            alt="Image Description">
                        <p class="mb-0">{{ translate('No_data_to_show') }}</p>
                    </div>
                @endif
            </div>
            <div class="card-footer border-0">
                <div class="d-flex justify-content-center justify-content-sm-end">
                    {!! $orders->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection


@if($status=='returned')
    <!-- Professional Return Logs Modal -->
    <div class="modal fade" id="returnLogModal" tabindex="-1" role="dialog" aria-labelledby="returnLogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1" id="returnLogModalLabel">
                            <i class="tio-replay text-primary mr-2"></i>
                            {{ translate('Return Details') }}
                        </h5>
                        <p class="text-muted mb-0 small" id="returnLogOrderInfo"></p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-2">
                    <div id="returnLogLoading" class="text-center p-4" style="display:none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">{{ translate('Loading') }}...</span>
                        </div>
                        <p class="text-muted mt-2">{{ translate('Loading return details') }}...</p>
                    </div>

                    <div id="returnLogContent" style="display:none;">
                        <!-- Summary Card -->
                        <div class="card mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-white py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <span class="badge text-uppercase px-3 py-2" id="returnLogTypeBadge" style="background: rgba(255,255,255,0.2);"></span>
                                    </div>
                                    <div class="text-right" id="returnLogSummary"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 35%;">{{ translate('Item') }}</th>
                                        <th class="text-center" style="width: 15%;">{{ translate('Original Qty') }}</th>
                                        <th class="text-center" style="width: 15%;">{{ translate('Current Qty') }}</th>
                                        <th class="text-center" style="width: 15%;">{{ translate('Returned') }}</th>
                                        <th style="width: 20%;">{{ translate('Reason') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="returnLogRows"></tbody>
                            </table>
                        </div>

                        <!-- Price Summary -->
                        <div class="card bg-light mt-3" id="returnLogPriceSummary" style="display:none;">
                            <div class="card-body py-3">
                                <h6 class="mb-2">
                                    <i class="tio-money mr-1 text-primary"></i>
                                    {{ translate('Price Impact') }}
                                </h6>
                                <div id="returnLogPriceContent"></div>
                            </div>
                        </div>
                    </div>

                    <div id="returnLogError" class="alert alert-danger" style="display:none;"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #returnLogModal .badge-partial { background: #fff3cd; color: #856404; }
        #returnLogModal .badge-full { background: #f8d7da; color: #721c24; }
        #returnLogModal .qty-badge { padding: 4px 12px; border-radius: 20px; font-weight: 600; }
        #returnLogModal .qty-original { background: #e9ecef; color: #495057; }
        #returnLogModal .qty-current { background: #d4edda; color: #155724; }
        #returnLogModal .qty-returned { background: #f8d7da; color: #721c24; }
        #returnLogModal .reason-chip { 
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            background: #fff8e1; 
            border-left: 3px solid #ffc107; 
            padding: 6px 12px; 
            border-radius: 0 8px 8px 0;
            font-size: 13px;
        }
        #returnLogModal .photo-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #667eea;
            font-weight: 500;
        }
        #returnLogModal .photo-link:hover { text-decoration: underline; }
    </style>
@endif

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/flatpicker.js') }}"></script>
@if($status=='returned')
<script>
    (function(){
        function esc(str){ return (str||'').toString().replace(/[&<>"']/g, function(m){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]);}); }
        function formatPrice(val){ return '₹' + parseFloat(val || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'); }
        
        $(document).on('click', '.js-return-view', function(){
            var orderId = $(this).data('order-id');
            $('#returnLogError').hide().text('');
            $('#returnLogContent').hide();
            $('#returnLogRows').html('');
            $('#returnLogPriceSummary').hide();
            $('#returnLogOrderInfo').text('Order #' + orderId);
            $('#returnLogLoading').show();
            $('#returnLogModal').modal('show');

            var url = "{{ route('admin.orders.returned-logs', ['order_id' => 'ORDER_ID']) }}".replace('ORDER_ID', orderId);
            $.get(url)
                .done(function(res){
                    $('#returnLogLoading').hide();
                    var type = (res.type || 'full');
                    var typeLabel = type === 'partial' ? 'PARTIAL RETURN' : 'FULL RETURN';
                    var badgeClass = type === 'partial' ? 'badge-partial' : 'badge-full';
                    
                    $('#returnLogTypeBadge')
                        .removeClass('badge-partial badge-full')
                        .addClass(badgeClass)
                        .html('<i class="' + (type === 'partial' ? 'tio-replay' : 'tio-clear-circle') + ' mr-1"></i>' + typeLabel);
                    
                    var itemsCount = res.summary?.items_count || 0;
                    var totalQty = res.summary?.total_return_qty || 0;
                    $('#returnLogSummary').html(
                        '<span class="h3 mb-0">' + itemsCount + '</span> <small>items</small> &nbsp;&bull;&nbsp; ' +
                        '<span class="h3 mb-0">' + totalQty + '</span> <small>qty returned</small>'
                    );
                    
                    var rows = '';
                    var totalOldPrice = 0;
                    var totalNewPrice = 0;
                    
                    (res.items || []).forEach(function(it){
                        var photoHtml = '';
                        if(it.photo){
                            var href = "{{ asset('storage') }}" + '/' + it.photo;
                            photoHtml = '<a href="' + href + '" target="_blank" class="photo-link ml-2">' +
                                        '<i class="tio-image"></i> View</a>';
                        }
                        
                        var oldQty = parseInt(it.old_quantity) || 0;
                        var newQty = parseInt(it.new_quantity) || 0;
                        var returnedQty = parseInt(it.returned_qty) || 0;
                        
                        rows += '<tr>' +
                            '<td>' +
                                '<div class="font-weight-bold">' + esc(it.product_name) + '</div>' +
                                '<small class="text-muted">#' + esc(it.order_detail_id) + '</small>' +
                            '</td>' +
                            '<td class="text-center"><span class="qty-badge qty-original">' + oldQty + '</span></td>' +
                            '<td class="text-center"><span class="qty-badge qty-current">' + newQty + '</span></td>' +
                            '<td class="text-center"><span class="qty-badge qty-returned">-' + returnedQty + '</span></td>' +
                            '<td>' +
                                '<div class="reason-chip">' +
                                    '<i class="tio-info-outlined"></i>' + esc(it.reason || 'N/A') +
                                '</div>' +
                                photoHtml +
                            '</td>' +
                        '</tr>';
                        
                        // Calculate price totals if history available
                        if(it.history && it.history.length > 0) {
                            var first = it.history[0];
                            var last = it.history[it.history.length - 1];
                            totalOldPrice += parseFloat(first.old_price) || 0;
                            totalNewPrice += parseFloat(last.new_price) || 0;
                        }
                    });
                    
                    $('#returnLogRows').html(rows);
                    
                    // Show price summary if we have price data
                    if(totalOldPrice > 0 || totalNewPrice > 0) {
                        var priceDiff = totalOldPrice - totalNewPrice;
                        var priceHtml = '<div class="d-flex justify-content-between mb-1">' +
                            '<span>Original Total:</span>' +
                            '<span class="text-muted">' + formatPrice(totalOldPrice) + '</span>' +
                        '</div>' +
                        '<div class="d-flex justify-content-between mb-1">' +
                            '<span>Current Total:</span>' +
                            '<span class="font-weight-bold">' + formatPrice(totalNewPrice) + '</span>' +
                        '</div>' +
                        '<hr class="my-2">' +
                        '<div class="d-flex justify-content-between">' +
                            '<span class="font-weight-bold">Reduction:</span>' +
                            '<span class="text-danger font-weight-bold">-' + formatPrice(priceDiff) + '</span>' +
                        '</div>';
                        
                        $('#returnLogPriceContent').html(priceHtml);
                        $('#returnLogPriceSummary').show();
                    }
                    
                    $('#returnLogContent').show();
                })
                .fail(function(xhr){
                    $('#returnLogLoading').hide();
                    $('#returnLogError').show().text('{{ translate("Failed to load return logs. Please try again.") }}');
                });
        });
    })();
</script>
@endif

@endpush
