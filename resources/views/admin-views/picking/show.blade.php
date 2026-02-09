@extends('layouts.admin.app')

@section('title', translate('Pick Order Items'))

@push('css_or_js')
    <style>
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .status-badge {
            font-size: 12px;
            padding: 5px 10px;
        }

        .picking-table td,
        .picking-table th {
            vertical-align: middle;
        }

        .missing-reason-dropdown {
            display: none;
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
                    {{ translate('Pick Order Items') }} - {{ translate('Order') }} #{{ $order->id }}
                </span>
            </h1>
        </div>

        <!-- Order Info Card -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6>{{ translate('Order ID') }}</h6>
                        <p>{{ $order->id }}</p>
                    </div>
                    <div class="col-md-3">
                        <h6>{{ translate('Customer') }}</h6>
                        <p>
                            @if ($order->is_guest == 0 && $order->customer)
                                {{ $order->customer->f_name }} {{ $order->customer->l_name }}
                            @else
                                {{ translate('Guest Customer') }}
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <h6>{{ translate('Branch') }}</h6>
                        <p>{{ $order->branch ? $order->branch->name : translate('N/A') }}</p>
                    </div>
                    <div class="col-md-3">
                        <h6>{{ translate('Order Date') }}</h6>
                        <p>{{ $order->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Picking Items Card -->
        <div class="card">
            <div class="card-header">
                <h5>{{ translate('Items to Pick') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless picking-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Image') }}</th>
                                <th>{{ translate('Product Name') }}</th>
                                <th>{{ translate('Ordered Qty') }}</th>
                                <th>{{ translate('Pick Qty') }}</th>
                                <th>{{ translate('Missing Qty') }}</th>
                                <th>{{ translate('Missing Reason') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th class="text-center">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->pickingItems as $item)
                                <tr data-picking-item-id="{{ $item->id }}">
                                    <td>
                                        @if ($item->product && $item->product->image)
                                            <img src="{{ asset('storage/app/public/product/' . json_decode($item->product->image)[0]) }}" 
                                                 alt="{{ $item->product->name }}" 
                                                 class="product-image"
                                                 onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'">
                                        @else
                                            <img src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" 
                                                 alt="Product" 
                                                 class="product-image">
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->product)
                                            {{ $item->product->name }}
                                        @else
                                            {{ translate('Product not found') }}
                                        @endif
                                    </td>
                                    <td class="ordered-qty">{{ $item->ordered_qty }}</td>
                                    <td>
                                        <input type="number" 
                                               class="form-control pick-qty-input" 
                                               min="0" 
                                               max="{{ $item->ordered_qty }}" 
                                               value="{{ $item->picked_qty }}" 
                                               {{ $item->status != 'pending' ? 'disabled' : '' }}
                                               style="width: 100px;">
                                    </td>
                                    <td class="missing-qty">{{ $item->missing_qty }}</td>
                                    <td>
                                        <select class="form-control missing-reason-dropdown" style="width: 150px;">
                                            <option value="">{{ translate('Select Reason') }}</option>
                                            <option value="out_of_stock" {{ $item->missing_reason == 'out_of_stock' ? 'selected' : '' }}>
                                                {{ translate('Out of Stock') }}
                                            </option>
                                            <option value="damaged" {{ $item->missing_reason == 'damaged' ? 'selected' : '' }}>
                                                {{ translate('Damaged') }}
                                            </option>
                                            <option value="expired" {{ $item->missing_reason == 'expired' ? 'selected' : '' }}>
                                                {{ translate('Expired') }}
                                            </option>
                                            <option value="not_found" {{ $item->missing_reason == 'not_found' ? 'selected' : '' }}>
                                                {{ translate('Not Found') }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="status-cell">
                                        @if ($item->status == 'pending')
                                            <span class="badge badge-warning status-badge">{{ translate('Pending') }}</span>
                                        @elseif ($item->status == 'picked')
                                            <span class="badge badge-success status-badge">{{ translate('Picked') }}</span>
                                        @elseif ($item->status == 'partial')
                                            <span class="badge badge-info status-badge">{{ translate('Partial') }}</span>
                                        @elseif ($item->status == 'missing')
                                            <span class="badge badge-danger status-badge">{{ translate('Missing') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-primary confirm-pick-btn"
                                                {{ $item->status != 'pending' ? 'disabled' : '' }}>
                                            {{ translate('Confirm Pick') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-right">
                    <form method="POST" action="{{ route('admin.picking.complete', ['order_id' => $order->id]) }}" id="completePickingForm">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg" id="completePickingBtn" disabled>
                            {{ translate('Complete Picking') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).ready(function() {
            // Update missing qty when pick qty changes
            $('.pick-qty-input').on('input', function() {
                const row = $(this).closest('tr');
                const orderedQty = parseInt(row.find('.ordered-qty').text());
                const pickQty = parseInt($(this).val()) || 0;
                const missingQty = orderedQty - pickQty;
                
                row.find('.missing-qty').text(missingQty);
                
                // Show/hide missing reason dropdown
                const dropdown = row.find('.missing-reason-dropdown');
                if (missingQty > 0) {
                    dropdown.show();
                    dropdown.prop('required', true);
                } else {
                    dropdown.hide();
                    dropdown.prop('required', false);
                    dropdown.val('');
                }
            });

            // Initialize missing reason dropdowns based on current missing qty
            $('.pick-qty-input').each(function() {
                const row = $(this).closest('tr');
                const missingQty = parseInt(row.find('.missing-qty').text());
                const dropdown = row.find('.missing-reason-dropdown');
                
                if (missingQty > 0) {
                    dropdown.show();
                }
            });

            // Confirm pick button click
            $('.confirm-pick-btn').on('click', function() {
                const btn = $(this);
                const row = btn.closest('tr');
                const pickingItemId = row.data('picking-item-id');
                const pickedQty = parseInt(row.find('.pick-qty-input').val()) || 0;
                const missingQty = parseInt(row.find('.missing-qty').text());
                const missingReason = row.find('.missing-reason-dropdown').val();

                // Validate
                if (missingQty > 0 && !missingReason) {
                    toastr.error('{{ translate("Please select a missing reason") }}');
                    return;
                }

                // Disable button and show loading
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                // AJAX call
                $.ajax({
                    url: '{{ route("admin.picking.pick-item") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        picking_item_id: pickingItemId,
                        picked_qty: pickedQty,
                        missing_reason: missingReason
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || '{{ translate("Item picked successfully") }}');
                            
                            // Update status badge
                            const statusCell = row.find('.status-cell');
                            let badgeClass = '';
                            let statusText = '';
                            
                            if (response.data.status === 'picked') {
                                badgeClass = 'badge-success';
                                statusText = '{{ translate("Picked") }}';
                            } else if (response.data.status === 'partial') {
                                badgeClass = 'badge-info';
                                statusText = '{{ translate("Partial") }}';
                            } else if (response.data.status === 'missing') {
                                badgeClass = 'badge-danger';
                                statusText = '{{ translate("Missing") }}';
                            }
                            
                            statusCell.html('<span class="badge ' + badgeClass + ' status-badge">' + statusText + '</span>');
                            
                            // Disable inputs
                            row.find('.pick-qty-input').prop('disabled', true);
                            row.find('.missing-reason-dropdown').prop('disabled', true);
                            
                            // Check if all items are picked
                            checkAllItemsPicked();
                        } else {
                            toastr.error(response.message || '{{ translate("Failed to pick item") }}');
                            btn.prop('disabled', false).html('{{ translate("Confirm Pick") }}');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || '{{ translate("An error occurred") }}';
                        toastr.error(message);
                        btn.prop('disabled', false).html('{{ translate("Confirm Pick") }}');
                    }
                });
            });

            // Check if all items are picked
            function checkAllItemsPicked() {
                const pendingCount = $('.status-cell .badge-warning').length;
                const completeBtn = $('#completePickingBtn');
                
                if (pendingCount === 0) {
                    completeBtn.prop('disabled', false);
                } else {
                    completeBtn.prop('disabled', true);
                }
            }

            // Initial check
            checkAllItemsPicked();

            // Complete picking form submit
            $('#completePickingForm').on('submit', function(e) {
                if (!confirm('{{ translate("Are you sure you want to complete picking? This will update the order amount based on picked quantities.") }}')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
