@extends('inventory.layouts.app')

@section('content')
    <div class="container-fluid py-4 dashboard-container">

        <!-- Welcome Section -->
        <div class="text-center mb-5">
            <h2 class="fw-bold text-gradient-primary">
                {{ translate('Welcome') }}, {{ Auth::guard('inventory')->user()->name }}
            </h2>
            <p class="text-muted mb-0">{{ translate('You are logged in as an inventory user.') }}</p>
        </div>

        <!-- Stats Grid -->
        <div class="row g-4 justify-content-center">
            @php
                $stats = [
                    [
                        'icon' => 'bi-cart-check-fill',
                        'color' => 'primary',
                        'label' => 'Total Orders',
                        'value' => $totalOrders,
                    ],
                    [
                        'icon' => 'bi-currency-rupee',
                        'color' => 'success',
                        'label' => 'Total Sale Amount',
                        'value' => '₹' . number_format($totalSalesAmount, 2),
                    ],
                    [
                        'icon' => 'bi-box-seam-fill',
                        'color' => 'warning',
                        'label' => 'Total Products',
                        'value' => $totalProducts,
                    ],
                    [
                        'icon' => 'bi-grid-fill',
                        'color' => 'info',
                        'label' => 'Total Categories',
                        'value' => $totalCategories,
                    ],
                    [
                        'icon' => 'bi-calendar-check-fill',
                        'color' => 'danger',
                        'label' => 'Today Orders',
                        'value' => $todayOrders,
                    ],
                    [
                        'icon' => 'bi-cash-stack',
                        'color' => 'success',
                        'label' => 'Today\'s Sales',
                        'value' => '₹' . number_format($todaySalesAmount, 2),
                    ],
                    [
                        'icon' => 'bi-bag-plus-fill',
                        'color' => 'secondary',
                        'label' => 'Total Purchases',
                        'value' => $totalPurchases,
                    ],
                    [
                        'icon' => 'bi-receipt-cutoff',
                        'color' => 'success',
                        'label' => 'Total Purchase Amount',
                        'value' => '₹' . number_format($totalPurchaseAmount, 2),
                    ],
                    [
                        'icon' => 'bi-box2-heart-fill',
                        'color' => 'dark',
                        'label' => 'Total Inventory Value',
                        'value' => '₹' . number_format($totalInventoryValue, 2),
                    ],
                ];
            @endphp

            @foreach ($stats as $item)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card stat-card border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-{{ $item['color'] }} bg-opacity-10 text-{{ $item['color'] }} mb-3">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </div>
                            <h6 class="text-muted small text-uppercase">{{ translate($item['label']) }}</h6>
                            <h4 class="fw-bold mb-0">{{ $item['value'] }}</h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Info + Low Stock -->
        <div class="row mt-5 g-4">
            <!-- Most Sold Product -->
            <div class="col-md-6">
                <div class="card info-card shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <div class="icon-badge text-warning bg-warning bg-opacity-10 mb-3">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <h6 class="text-muted">{{ translate('Most Sold Product') }}</h6>
                        @if ($mostSoldProduct)
                            <h5 class="fw-bold mb-1">{{ $mostSoldProduct->product->name ?? 'N/A' }}</h5>
                            <p class="text-muted small mb-0">{{ $mostSoldProduct->total_sold }}
                                {{ translate('units sold') }}</p>
                        @else
                            <p class="text-muted mb-0">{{ translate('No product sales yet') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Most Sold Category -->
            <div class="col-md-6">
                <div class="card info-card shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <div class="icon-badge text-primary bg-primary bg-opacity-10 mb-3">
                            <i class="bi bi-tags-fill"></i>
                        </div>
                        <h6 class="text-muted">{{ translate('Most Sold Category') }}</h6>
                        @if ($mostSoldCategoryName)
                            <h5 class="fw-bold mb-1">{{ $mostSoldCategoryName }}</h5>
                        @else
                            <p class="text-muted mb-0">{{ translate('No category sales yet') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="col-md-12">
                <div class="card border-0 shadow-sm h-100 low-stock-card">
                    <div
                        class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center rounded-top py-2 px-3">
                        <h6 class="mb-0">{{ translate('Low Stock Products') }}</h6>
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div class="card-body p-3">
                        @if ($lowStockProducts->isEmpty())
                            <p class="text-center text-muted mb-0 small">{{ translate('No low stock products') }}</p>
                        @else
                            <div class="table-responsive" style="max-height: 260px; overflow-y:auto;">
                                <table class="table align-middle table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ translate('Product Name') }}</th>
                                            <th class="text-center">{{ translate('Stock') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lowStockProducts as $product)
                                            <tr>
                                                <td>{{ $product->name }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 rounded-pill">
                                                        {{ $product->total_stock }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Styles -->
    <style>
        /* Gradient helpers */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff, #00c6ff);
        }

        .text-gradient-primary {
            background: linear-gradient(90deg, #007bff, #00c6ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Dashboard Layout */
        .dashboard-container {
            background: linear-gradient(180deg, #f8fbff, #eef3f9);
            min-height: 100vh;
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 20px;
            background: linear-gradient(145deg, #ffffff, #f4f6fa);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            background: linear-gradient(145deg, #f9fbff, #ffffff);
        }

        .icon-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            font-size: 28px;
            transition: all 0.3s ease;
        }

        .stat-card:hover .icon-circle {
            transform: scale(1.1);
        }

        /* Info Cards */
        .info-card {
            border-radius: 20px;
            background: linear-gradient(145deg, #ffffff, #f5f7fa);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-6px);
            background: linear-gradient(145deg, #f9fbff, #ffffff);
        }

        .icon-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }

        /* Low Stock */
        .low-stock-card {
            border-radius: 20px;
            overflow: hidden;
        }

        table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .bg-danger-subtle {
            background-color: rgba(220, 53, 69, 0.1);
        }

        /* Scrollbar */
        .table-responsive::-webkit-scrollbar {
            width: 6px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #cfd6df;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a5b0bb;
        }
    </style>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
@endsection
