@extends('layouts.branch.app')

@section('title', translate('Picklist Generator'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .filter-sidebar {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .route-total-row {
            background: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <i class="tio-receipt-outlined"></i>
                </span>
                <span>
                    {{ translate('Picklist Generator') }}
                    <span class="badge badge-pill badge-soft-secondary ml-2">{{ $picklistData->count() }}</span>
                </span>
            </h1>
        </div>

        <div class="row">
            <div class="col-lg-3">
                <div class="filter-sidebar mb-3">
                    <h5 class="mb-3">{{ translate('Filters') }}</h5>
                    <form action="{{ route('branch.picklist.index') }}" method="GET">
                        <div class="form-group">
                            <label class="input-label">{{ translate('Start Date') }}</label>
                            <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('End Date') }}</label>
                            <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Store') }}</label>
                            <select name="store_id" class="form-control">
                                <option value="">{{ translate('All Stores') }}</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Route') }}</label>
                            <select name="route_name" class="form-control">
                                <option value="">{{ translate('All Routes') }}</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route }}" {{ $routeName == $route ? 'selected' : '' }}>
                                        {{ $route }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ translate('Picking Status') }}</label>
                            <select name="picking_status" class="form-control">
                                <option value="">{{ translate('All') }}</option>
                                <option value="picked" {{ $pickingStatus == 'picked' ? 'selected' : '' }}>
                                    {{ translate('Picked') }}
                                </option>
                                <option value="non_picked" {{ $pickingStatus == 'non_picked' ? 'selected' : '' }}>
                                    {{ translate('Non-Picked') }}
                                </option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="tio-filter-outlined"></i> {{ translate('Apply Filters') }}
                            </button>
                            <a href="{{ route('branch.picklist.index') }}" class="btn btn-secondary">
                                <i class="tio-clear"></i>
                            </a>
                        </div>
                    </form>

                    <hr class="my-3">

                    <h5 class="mb-3">{{ translate('Export') }}</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('branch.picklist.export-pdf', request()->all()) }}" 
                           class="btn btn-danger btn-block">
                            <i class="tio-download"></i> {{ translate('Download PDF') }}
                        </a>
                        <a href="{{ route('branch.picklist.export-excel', request()->all()) }}" 
                           class="btn btn-success btn-block">
                            <i class="tio-download"></i> {{ translate('Download Excel') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            {{ translate('Picklist Data') }}
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($picklistData->isEmpty())
                            <div class="text-center p-5">
                                <img src="{{asset('public/assets/admin/img/empty-state.png')}}" 
                                     alt="{{ translate('No data found') }}" 
                                     class="mb-3" style="width: 150px;">
                                <p class="text-muted">{{ translate('No data found with current filters') }}</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{ translate('Route') }}</th>
                                            <th>{{ translate('Product Name') }}</th>
                                            <th>{{ translate('Unit Weight (kg)') }}</th>
                                            <th>{{ translate('Quantity') }}</th>
                                            <th>{{ translate('Total Weight (kg)') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $currentRoute = null;
                                        @endphp
                                        @foreach($picklistData as $item)
                                            @if($currentRoute !== $item->route_name && $currentRoute !== null)
                                                <tr class="route-total-row">
                                                    <td colspan="4" class="text-right">
                                                        <strong>{{ translate('TOTAL FOR ROUTE') }}: {{ $currentRoute }}</strong>
                                                    </td>
                                                    <td>
                                                        <strong>{{ number_format($routeTotals[$currentRoute]['total_weight'] ?? 0, 2) }} kg</strong>
                                                    </td>
                                                </tr>
                                            @endif
                                            @php
                                                $currentRoute = $item->route_name;
                                            @endphp
                                            <tr>
                                                <td>{{ $item->route_name ?? translate('N/A') }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ number_format($item->weight ?? 0, 2) }}</td>
                                                <td>{{ $item->total_quantity }}</td>
                                                <td>{{ number_format($item->total_weight, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        @if($currentRoute !== null)
                                            <tr class="route-total-row">
                                                <td colspan="4" class="text-right">
                                                    <strong>{{ translate('TOTAL FOR ROUTE') }}: {{ $currentRoute }}</strong>
                                                </td>
                                                <td>
                                                    <strong>{{ number_format($routeTotals[$currentRoute]['total_weight'] ?? 0, 2) }} kg</strong>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
@endpush
