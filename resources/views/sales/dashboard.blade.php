@extends('sales.layout')

@section('content')

    @php
        // Sales manager ID from session
        $salesPersonId = session('sales_user_id');

        // Fetch all stores assigned to this sales person
        $stores = \App\Models\Store::where('sales_person_id', $salesPersonId)->get();

        // Store IDs
        $storeIds = $stores->pluck('id');

        // Fetch orders related to these stores
        $orders = \App\Models\Order::whereIn('store_id', $storeIds)->get();

        // Stats
        $totalStores = $stores->count();
        $totalOrders = $orders->count();
        $totalAmount = $orders->sum('order_amount');

        // Sales People Total (modify if relationship exists)
        $totalSalesPeople = \App\Models\SalesPerson::count();
    @endphp


    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <h4 class="fw-bold mb-2">Dashboard Overview</h4>
            <p class="text-muted">Real-time statistics for your assigned sales area.</p>

            <div class="row mt-4">

                <!-- Total Stores -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient1">
                        <h6 class="fw-semibold">Stores</h6>
                        <h2 class="fw-bold">{{ $totalStores }}</h2>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient2">
                        <h6 class="fw-semibold">Orders</h6>
                        <h2 class="fw-bold">{{ $totalOrders }}</h2>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient3">
                        <h6 class="fw-semibold">Sales Amount</h6>
                        <h2 class="fw-bold">₹{{ number_format($totalAmount, 2) }}</h2>
                    </div>
                </div>

                <!-- Total Sales People -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient4">
                        <h6 class="fw-semibold">Sales People</h6>
                        <h2 class="fw-bold">{{ $totalSalesPeople }}</h2>
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- Chart Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-bold mb-3">Sales Performance Chart</h5>

            <canvas id="salesChart" height="120"></canvas>

        </div>
    </div>
    {{-- =========================== STYLES =========================== --}}
    <style>
        /* Gradient Hover Cards */
        .stat-card {
            transition: all 0.35s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
            opacity: 0.95;
        }

        .gradient1 {
            background: linear-gradient(135deg, #5b6cff, #20c997);
        }

        .gradient2 {
            background: linear-gradient(135deg, #ff7f50, #ff416c);
        }

        .gradient3 {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
        }

        .gradient4 {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
        }
    </style>

    {{-- =========================== CHART.JS =========================== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');

        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Stores', 'Orders', 'Sales Amount', 'Sales People'],
                datasets: [{
                        label: 'Counts',
                        data: [
                            {{ $totalStores }},
                            {{ $totalOrders }},
                            {{ $totalSalesPeople }},
                        ],
                        backgroundColor: [
                            'rgba(91,108,255,0.8)',
                            'rgba(255,99,132,0.8)',
                            'rgba(255,154,158,0.8)'
                        ],
                        borderRadius: 10,
                        yAxisID: 'y',
                    },

                    {
                        label: 'Sales Amount (₹)',
                        data: [
                            null,
                            null,
                            {{ $totalAmount }},
                            null
                        ],
                        backgroundColor: 'rgba(106,17,203,0.8)',
                        borderRadius: 10,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Counts'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Sales Amount (₹)'
                        }
                    }
                }
            }
        });
    </script>
@endsection

@section('title', 'Dashboard')
