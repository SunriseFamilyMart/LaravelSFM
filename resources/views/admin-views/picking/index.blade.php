@extends('layouts.admin.app')

@section('title', translate('Pick List'))

@push('css_or_js')
    <style>
        table {
            width: 100%;
        }
        .min-h-45px {
            min-height: 45px;
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
                <span>
                    {{ translate('Pick List') }}
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $orders->total() }}</span>
                </span>
            </h1>
        </div>

        <div class="card">
            <div class="card-header shadow flex-wrap p-20px border-0">
                <h5 class="form-bold w-100 mb-3">{{ translate('Filter Pick List') }}</h5>
                <form class="w-100" method="GET" action="{{ route('admin.picking.index') }}">
                    <div class="row g-3 g-sm-4 g-md-3 g-lg-4">

                        <!-- Branch Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <label class="input-label">{{ translate('Branch') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="branch_id">
                                <option value="all" {{ $branchId == 'all' ? 'selected' : '' }}>{{ translate('All Branches') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch['id'] }}" {{ $branchId == $branch['id'] ? 'selected' : '' }}>
                                        {{ $branch['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Route Filter -->
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <label class="input-label">{{ translate('Route') }}</label>
                            <select class="custom-select custom-select-sm text-capitalize min-h-45px" name="route">
                                <option value="all" {{ $route == 'all' ? 'selected' : '' }}>{{ translate('All Routes') }}</option>
                                @foreach ($routes as $routeName)
                                    <option value="{{ $routeName }}" {{ $route == $routeName ? 'selected' : '' }}>
                                        {{ $routeName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <label class="input-label" for="start_date">{{ translate('Start Date') }}</label>
                            <input type="text" id="start_date" name="start_date" value="{{ $startDate ?? '' }}"
                                class="js-flatpickr form-control flatpickr-custom min-h-45px" placeholder="yy-mm-dd"
                                data-hs-flatpickr-options='{ "dateFormat": "Y-m-d"}'>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <label class="input-label" for="end_date">{{ translate('End Date') }}</label>
                            <input type="text" id="end_date" name="end_date" value="{{ $endDate ?? '' }}"
                                class="js-flatpickr form-control flatpickr-custom min-h-45px" placeholder="yy-mm-dd"
                                data-hs-flatpickr-options='{ "dateFormat": "Y-m-d"}'>
                        </div>

                        <!-- Time Range (Optional) -->
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <label class="input-label" for="start_time">{{ translate('Start Time') }}</label>
                            <input type="time" id="start_time" name="start_time" value="{{ $startTime ?? '' }}"
                                class="form-control min-h-45px">
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-2">
                            <label class="input-label" for="end_time">{{ translate('End Time') }}</label>
                            <input type="time" id="end_time" name="end_time" value="{{ $endTime ?? '' }}"
                                class="form-control min-h-45px">
                        </div>

                        <!-- Buttons -->
                        <div class="col-12">
                            <div class="d-flex gap-3">
                                <a href="{{ route('admin.picking.index') }}" class="btn btn--reset min-h-45px">{{ translate('Clear') }}</a>
                                <button type="submit" class="btn btn--primary min-h-45px">{{ translate('Show Data') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- PDF Download Button -->
            <div class="card-body pt-0">
                <div class="d-flex justify-content-end mb-3">
                    <form method="GET" action="{{ route('admin.picking.export-pdf') }}">
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <input type="hidden" name="route" value="{{ $route }}">
                        <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                        <input type="hidden" name="start_time" value="{{ $startTime ?? '' }}">
                        <input type="hidden" name="end_time" value="{{ $endTime ?? '' }}">
                        <button type="submit" class="btn btn-success">
                            <i class="tio-download"></i> {{ translate('Download PDF') }}
                        </button>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive datatable-custom">
                    <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Order ID') }}</th>
                                <th>{{ translate('Order Date') }}</th>
                                <th>{{ translate('Store') }}</th>
                                <th>{{ translate('Route') }}</th>
                                <th>{{ translate('Branch') }}</th>
                                <th>{{ translate('Items') }}</th>
                                <th>{{ translate('Total Weight') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.picking.show', [$order['id']]) }}">{{ $order['id'] }}</a>
                                    </td>
                                    <td>
                                        {{ date('Y-m-d H:i', strtotime($order['created_at'])) }}
                                    </td>
                                    <td>
                                        {{ $order->store->store_name ?? translate('N/A') }}
                                    </td>
                                    <td>
                                        {{ $order->store->route_name ?? translate('N/A') }}
                                    </td>
                                    <td>
                                        {{ $order->branch->name ?? translate('N/A') }}
                                    </td>
                                    <td>
                                        {{ $order->details->count() }}
                                    </td>
                                    <td>
                                        @php
                                            $totalWeight = 0;
                                            foreach ($order->details as $detail) {
                                                if ($detail->product && isset($detail->product->weight)) {
                                                    $totalWeight += $detail->product->weight * $detail->quantity;
                                                }
                                            }
                                        @endphp
                                        {{ number_format($totalWeight, 2) }} kg
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-{{ $order['order_status'] == 'pending' ? 'warning' : 'info' }}">
                                            {{ translate($order['order_status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.picking.show', [$order['id']]) }}">
                                            <i class="tio-visible"></i> {{ translate('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">{{ translate('No orders found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    {!! $orders->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).ready(function() {
            $('.js-flatpickr').each(function() {
                $.HSCore.components.HSFlatpickr.init($(this));
            });
        });
    </script>
@endpush
