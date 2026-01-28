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

                                        <!-- ✅ Trip actions -->
                                        <td>
                                            <div class="btn-group" role="group" aria-label="Trip actions">
                                                <a href="{{ route('admin.delivery_trips.create', ['download' => 1, 'trip_number' => $trip->trip_number]) }}"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>

                                                @if ($trip->status !== 'completed')
                                                    <button type="button" class="btn btn-sm btn-outline-primary js-trip-reassign"
                                                        data-trip-id="{{ $trip->id }}"
                                                        data-trip-number="{{ $trip->trip_number }}"
                                                        data-current-dm-name="{{ $trip->deliveryMan->f_name }} {{ $trip->deliveryMan->l_name }}">
                                                        <i class="bi bi-arrow-repeat"></i> Reassign
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="bi bi-arrow-repeat"></i> Reassign
                                                    </button>
                                                @endif

                                                <button type="button" class="btn btn-sm btn-outline-dark js-trip-history"
                                                    data-trip-id="{{ $trip->id }}"
                                                    data-trip-number="{{ $trip->trip_number }}">
                                                    <i class="bi bi-clock-history"></i> History
                                                    @if (($trip->reassignments_count ?? 0) > 0)
                                                        <span class="badge badge-dark ml-1">{{ $trip->reassignments_count }}</span>
                                                    @endif
                                                </button>
                                            </div>
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



    <!-- Reassign Trip Modal -->
    <div class="modal fade" id="tripReassignModal" tabindex="-1" role="dialog" aria-labelledby="tripReassignModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tripReassignModalLabel">Reassign Trip</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.delivery_trips.reassign') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="trip_id" id="reassignTripId" value="">

                        <div class="mb-2">
                            <div class="small text-muted">Trip</div>
                            <div class="fw-bold" id="reassignTripNumber">-</div>
                            <div class="small text-muted">Current delivery man: <span id="reassignCurrentDm">-</span></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Delivery Man</label>
                            <select name="delivery_man_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach ($deliveryMen as $dm)
                                    <option value="{{ $dm->id }}">{{ $dm->f_name }} {{ $dm->l_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold">Reason (optional)</label>
                            <input type="text" name="reason" class="form-control" maxlength="255" placeholder="e.g., Rider unavailable / route optimization">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reassign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Trip Reassignment History Modal -->
    <div class="modal fade" id="tripHistoryModal" tabindex="-1" role="dialog" aria-labelledby="tripHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tripHistoryModalLabel">Trip History</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="tripHistoryLoading" class="text-center p-3" style="display:none;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <span class="ml-2">Loading...</span>
                    </div>

                    <div id="tripHistoryEmpty" class="text-center text-muted p-3" style="display:none;">
                        No reassignment history.
                    </div>

                    <div id="tripHistoryContent" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>By</th>
                                        <th>Reason</th>
                                        <th>At</th>
                                    </tr>
                                </thead>
                                <tbody id="tripHistoryRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="tripHistoryError" class="alert alert-danger" style="display:none;"></div>
                </div>
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



    <script>
    // NOTE: layout loads vendor.min.js (jQuery) AFTER @yield('content'),
    // so this page script must wait until window load to access $ / bootstrap modal.
    window.addEventListener('load', function () {
        if (typeof window.jQuery === 'undefined') {
            console.error('jQuery not available yet. Check vendor.min.js include.');
            return;
        }

        (function ($) {
            function esc(str) {
                return (str || '').toString().replace(/[&<>"']/g, function (m) {
                    return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m]);
                });
            }

            // Reassign modal open
            $(document).on('click', '.js-trip-reassign', function () {
                var tripId = $(this).data('trip-id');
                var tripNumber = $(this).data('trip-number');
                var currentDm = $(this).data('current-dm-name');

                $('#reassignTripId').val(tripId);
                $('#reassignTripNumber').text(tripNumber);
                $('#reassignCurrentDm').text(currentDm);
                $('#tripReassignModal').appendTo('body').modal('show');
            });

            // History modal open
            $(document).on('click', '.js-trip-history', function () {
                var tripId = $(this).data('trip-id');
                var tripNumber = $(this).data('trip-number');

                $('#tripHistoryModalLabel').text('Trip History — ' + tripNumber);
                $('#tripHistoryError').hide().text('');
                $('#tripHistoryContent').hide();
                $('#tripHistoryEmpty').hide();
                $('#tripHistoryRows').html('');
                $('#tripHistoryLoading').show();

                $('#tripHistoryModal').appendTo('body').modal('show');

                var url = "{{ route('admin.delivery_trips.history', ['trip_id' => 'TRIP_ID']) }}".replace('TRIP_ID', tripId);

                $.get(url)
                    .done(function (res) {
                        $('#tripHistoryLoading').hide();
                        var items = res.items || [];
                        if (!items.length) {
                            $('#tripHistoryEmpty').show();
                            return;
                        }

                        var rows = '';
                        items.forEach(function (it, idx) {
                            var from = it.from || '-';
                            var to = it.to || '-';
                            var by = it.by || '-';
                            var reason = it.reason || '-';
                            var at = it.created_at || '-';

                            rows += '<tr>'
                                + '<td>' + (idx + 1) + '</td>'
                                + '<td>' + esc(from) + (it.from_status ? '<div class="small text-muted">' + esc(it.from_status) + '</div>' : '') + '</td>'
                                + '<td>' + esc(to) + (it.to_status ? '<div class="small text-muted">' + esc(it.to_status) + '</div>' : '') + '</td>'
                                + '<td>' + esc(by) + '</td>'
                                + '<td>' + esc(reason) + '</td>'
                                + '<td>' + esc(at) + '</td>'
                                + '</tr>';
                        });

                        $('#tripHistoryRows').html(rows);
                        $('#tripHistoryContent').show();
                    })
                    .fail(function (xhr) {
                        $('#tripHistoryLoading').hide();
                        var msg = 'Failed to load trip history';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        $('#tripHistoryError').show().text(msg);
                    });
            });
        })(window.jQuery);
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
