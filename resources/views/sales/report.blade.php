@extends('sales.layout')
@section('title', 'Sales Report')
@section('content')

    @php
        use App\Models\Order;
        use App\Models\Store;
        use Maatwebsite\Excel\Facades\Excel;
        use Maatwebsite\Excel\Concerns\FromCollection;
        use Maatwebsite\Excel\Concerns\WithHeadings;

        // Get sales person ID
        $salesPersonId = session('sales_user_id');

        // Fetch stores assigned to this sales person
        $stores = Store::where('sales_person_id', $salesPersonId)->get();
        $storeIds = $stores->pluck('id');

        // Prepare orders query
        $ordersQuery = Order::whereIn('store_id', $storeIds)->orderBy('created_at', 'DESC');

        // Apply search/filter
        $search = request()->search ?? '';
        $filter = request()->filter ?? '';

        if ($search != '') {
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")->orWhere('order_amount', 'LIKE', "%$search%");
            });
        }

        if ($filter == 'today') {
            $ordersQuery->whereDate('created_at', today());
        } elseif ($filter == 'week') {
            $ordersQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter == 'month') {
            $ordersQuery->whereMonth('created_at', now()->month);
        }

        // Excel Download
        if (request()->has('download_excel')) {
            $allOrders = $ordersQuery->get(); // get all, not paginated
            return Excel::download(
                new class ($allOrders, $stores) implements FromCollection, WithHeadings {
                    protected $orders;
                    protected $stores;
                    public function __construct($orders, $stores)
                    {
                        $this->orders = $orders;
                        $this->stores = $stores;
                    }
                    public function collection()
                    {
                        return $this->orders->map(function ($order) {
                            $storeName =
                                $this->stores->where('id', $order->store_id)->first()->store_name ?? 'Unknown Store';
                            return [
                                'Order ID' => $order->id,
                                'Store Name' => $storeName,
                                'Amount' => $order->order_amount,
                                'Payment Status' => $order->payment_status,
                                'Order Status' => $order->order_status,
                                'Date' => $order->created_at->format('d M Y'),
                            ];
                        });
                    }
                    public function headings(): array
                    {
                        return ['Order ID', 'Store Name', 'Amount', 'Payment Status', 'Order Status', 'Date'];
                    }
                },
                'sales_report.xlsx',
            );
        }

        // Fetch orders for display with pagination
        $orders = $ordersQuery->paginate(10)->withQueryString();

        // Stats
        $totalStores = $stores->count();
        $totalOrders = $ordersQuery->count();
        $totalRevenue = Order::whereIn('store_id', $storeIds)->sum('order_amount');

    @endphp

    {{-- Overview Cards --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h4 class="fw-bold">Sales Report Overview</h4>
                <a href="{{ route('sales.report.download', ['search' => $search, 'filter' => $filter]) }}"
                    class="btn btn-success d-flex align-items-center">
                    <i class="bi bi-download me-2"></i> Download Excel
                </a>

            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient1">
                        <h6 class="mb-2">Total Stores</h6>
                        <h2 class="fw-bold">{{ $totalStores }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient2">
                        <h6 class="mb-2">Total Orders</h6>
                        <h2 class="fw-bold">{{ $totalOrders }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4 rounded-3 shadow-sm text-white gradient3">
                        <h6 class="mb-2">Total Revenue</h6>
                        <h2 class="fw-bold">₹{{ number_format($totalRevenue, 2) }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ $search }}" class="form-control"
                        placeholder="Search Order ID / Amount">
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-select">
                        <option value="">All</option>
                        <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ $filter == 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ $filter == 'month' ? 'selected' : '' }}>This Month</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Search</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('sales.report') }}" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Detailed Sales Report</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Store Name</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php $store = $stores->where('id', $order->store_id)->first(); @endphp
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>{{ $store->store_name ?? 'Unknown Store' }}</td>
                                <td>₹{{ number_format($order->order_amount, 2) }}</td>
                                <td class="text-capitalize">{{ $order->payment_status }}</td>
                                <td class="text-capitalize">{{ $order->order_status }}</td>
                                <td>{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

@endsection

<style>
    .stat-card {
        transition: .35s;
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
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
</style>
