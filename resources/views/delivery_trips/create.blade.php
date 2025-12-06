@extends('layouts.admin.app')

@section('title', translate('Bulk Assign to Delivery Man'))

@section('content')
    <div class="container-fluid py-4">

        <!-- Assign Orders Card -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assign Orders to Delivery Man</h5>
                <a href="{{ route('admin.order.list', ['status' => 'all']) }}" class="btn btn-light btn-sm shadow-sm">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <!-- Assign Orders Form -->
                <form action="{{ route('admin.delivery_trips.store') }}" method="POST">
                    @csrf

                    <!-- Select Delivery Man -->
                    <div class="mb-4">
                        <label for="delivery_man_id" class="form-label fw-semibold">Select Delivery Man</label>
                        <select name="delivery_man_id" id="delivery_man_id" class="form-select shadow-sm" required>
                            <option value="">-- Select --</option>
                            @foreach ($deliveryMen as $dm)
                                <option value="{{ $dm->id }}">{{ $dm->f_name }} {{ $dm->l_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Select Orders -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Orders</label>
                        <div class="row g-3">
                            @foreach ($orders as $order)
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded hover-shadow bg-white">
                                        <input class="form-check-input" type="checkbox" name="order_ids[]"
                                            value="{{ $order->id }}" id="order-{{ $order->id }}">
                                        <label class="form-check-label fw-medium" for="order-{{ $order->id }}">
                                            Order #{{ $order->id }} - ₹{{ number_format($order->order_amount, 2) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success shadow-sm px-4 py-2 fw-semibold">Assign Orders</button>
                </form>

                <!-- ✅ Bulk Invoice Download (for selected orders) -->
                <form id="bulkDownloadForm" action="{{ route('admin.delivery_trips.create') }}" method="GET"
                    class="mt-3">
                    <input type="hidden" name="download" value="1">
                    <button type="submit" class="btn btn-outline-primary shadow-sm px-4 py-2 fw-semibold">
                        <i class="bi bi-download"></i> Download Invoices (Selected Orders)
                    </button>
                </form>
            </div>
        </div>

        <!-- Assigned Trips -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-gradient-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Assigned Delivery Trips</h5>

                <!-- 🔍 Search Form -->
                <form action="{{ route('admin.delivery_trips.create') }}" method="GET" class="d-flex">
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control form-control-sm me-2" placeholder="Search Trip Number...">
                    <button type="submit" class="btn btn-sm btn-light">Search</button>
                </form>
            </div>

            <div class="card-body">
                @if ($trips->isEmpty())
                    <p class="text-center text-muted mb-0">No trips assigned yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Trip Number</th>
                                    <th>Delivery Man</th>
                                    <th>Orders</th>
                                    <th>Status</th>
                                    <th>Assigned At</th>
                                    <th>Actions</th> <!-- ✅ Added -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trips as $trip)
                                    <tr>
                                        <td class="fw-bold">{{ $trip->trip_number }}</td>
                                        <td>{{ $trip->deliveryMan->f_name }} {{ $trip->deliveryMan->l_name }}</td>
                                        <td>
                                            @foreach ($trip->order_ids as $orderId)
                                                <span
                                                    class="badge bg-gradient-primary text-white mb-1">#{{ $orderId }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if ($trip->status == 'pending')
                                                <span class="badge bg-gradient-warning text-dark">Pending</span>
                                            @elseif($trip->status == 'on_route')
                                                <span class="badge bg-gradient-info text-dark">On Route</span>
                                            @else
                                                <span class="badge bg-gradient-success text-white">Completed</span>
                                            @endif
                                        </td>
                                        <td>{{ $trip->created_at->format('d M Y H:i') }}</td>

                                        <!-- ✅ Trip-wise invoice download -->
                                        <td>
                                            <a href="{{ route('admin.delivery_trips.create', ['download' => 1, 'trip_number' => $trip->trip_number]) }}"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-file-earmark-pdf"></i> PDF
                                            </a>
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

    <!-- JS: Pass selected checkboxes to bulk download form -->
    <script>
        const bulkForm = document.getElementById('bulkDownloadForm');

        bulkForm.addEventListener('submit', function() {
            document.querySelectorAll('.clone-order-input').forEach(el => el.remove());

            document.querySelectorAll('input[name="order_ids[]"]:checked').forEach(chk => {
                let hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.classList.add('clone-order-input');
                hidden.name = 'order_ids[]';
                hidden.value = chk.value;
                bulkForm.appendChild(hidden);
            });
        });
    </script>

    <!-- Custom Styles -->
    <style>
        .hover-shadow:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff, #00c6ff);
        }

        .bg-gradient-dark {
            background: linear-gradient(135deg, #343a40, #495057);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107, #ffcd39);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8, #3bc9db);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745, #51cf66);
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
@endsection
