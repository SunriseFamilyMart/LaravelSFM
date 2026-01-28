@extends('sales.layout')

@section('title', 'Performance')

@section('content')

    @php
        $salesPersonId = session('sales_user_id');

        // Stores
        $stores = \App\Models\Store::where('sales_person_id', $salesPersonId)->get();
        $totalStores = $stores->count();
        $storeIds = $stores->pluck('id');

        // Orders
        $orders = \App\Models\Order::whereIn('store_id', $storeIds)->get();
        $totalOrders = $orders->count();
        $totalAmount = $orders->sum('order_amount');

        // Store Visits
        $visits = \App\Models\StoreVisit::where('sales_person_id', $salesPersonId)->get();
        $totalVisits = $visits->count();
        $lastVisit = $visits->max('created_at');

        // Monthly Orders for Chart
        $monthlyOrders = \App\Models\Order::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereIn('store_id', $storeIds)
            ->groupBy('month')
            ->pluck('total', 'month');

        // Convert to JS arrays
        $months = json_encode($monthlyOrders->keys());
        $monthData = json_encode($monthlyOrders->values());
    @endphp

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <h4 class="fw-bold mb-2">Performance Overview</h4>
            <p class="text-muted">Here is your performance breakdown based on stores, orders, and visits.</p>

            <div class="row mt-4">

                <!-- Stores -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient1">
                        <h6 class="fw-semibold">Stores</h6>
                        <h2 class="fw-bold">{{ $totalStores }}</h2>
                    </div>
                </div>

                <!-- Orders -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient2">
                        <h6 class="fw-semibold">Total Orders</h6>
                        <h2 class="fw-bold">{{ $totalOrders }}</h2>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient3">
                        <h6 class="fw-semibold">Sales Amount</h6>
                        <h2 class="fw-bold">₹{{ number_format($totalAmount, 2) }}</h2>
                    </div>
                </div>

                <!-- Visits -->
                <div class="col-md-3">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient4">
                        <h6 class="fw-semibold">Store Visits</h6>
                        <h2 class="fw-bold">{{ $totalVisits }}</h2>
                    </div>
                </div>

            </div>

            <!-- Last Visit -->
            <div class="mt-4">
                <h6 class="text-muted">
                    Last Visit:
                    <strong>{{ $lastVisit ? \Carbon\Carbon::parse($lastVisit)->format('d M, Y') : 'No Visits Yet' }}</strong>
                </h6>
            </div>

        </div>
    </div>


    {{-- Chart Section --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-bold mb-3">Monthly Orders Trend</h5>

            <canvas id="ordersChart" height="130"></canvas>

        </div>
    </div>
    {{-- =================== Styles =================== --}}
    <style>
        .stat-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            opacity: .95;
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
            background: linear-gradient(135deg, #f7971e, #ffd200);
        }
    </style>


    {{-- =================== Chart.js =================== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('ordersChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! $months !!},
                datasets: [{
                    label: 'Orders',
                    data: {!! $monthData !!},
                    borderColor: 'rgba(91,108,255,1)',
                    backgroundColor: 'rgba(91,108,255,0.22)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

@endsection
