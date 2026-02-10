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

        .missing-fields {
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

        @php
            $allPicked = $order->pickingItems->isNotEmpty() && 
                         $order->pickingItems->where('status', 'pending')->count() == 0;
        @endphp

        <!-- Picking Items Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5>{{ translate('Items to Pick') }}</h5>
            </div>
            <div class="card-body">
                @if ($allPicked)
                    <div class="alert alert-success">
                        {{ translate('Picking completed for this order!') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.picking.complete', ['order_id' => $order->id]) }}" id="completePickingForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless picking-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Image') }}</th>
                                    <th>{{ translate('Product Name') }}</th>
                                    <th>{{ translate('Ordered Qty') }}</th>
                                    <th>{{ translate('Mark as Missing') }}</th>
                                    @if ($allPicked)
                                        <th>{{ translate('Status') }}</th>
                                    @endif
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
                                            @if ($allPicked)
                                                @if ($item->missing_qty > 0)
                                                    <span class="badge badge-warning">{{ translate('Missing') }}: {{ $item->missing_qty }}</span>
                                                    @if ($item->missing_reason)
                                                        <br><small class="text-muted">{{ translate(ucfirst(str_replace('_', ' ', $item->missing_reason))) }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-success">{{ translate('Fully Picked') }}</span>
                                                @endif
                                            @else
                                                <div>
                                                    <input type="checkbox" 
                                                           class="mark-missing-checkbox" 
                                                           name="missing_items[]" 
                                                           value="{{ $item->id }}"
                                                           id="missing_{{ $item->id }}">
                                                    <label for="missing_{{ $item->id }}">{{ translate('Missing') }}</label>
                                                    
                                                    <div class="missing-fields mt-2" id="missing_fields_{{ $item->id }}">
                                                        <div class="form-group">
                                                            <label>{{ translate('Missing Qty') }}</label>
                                                            <input type="number" 
                                                                   class="form-control missing-qty-input" 
                                                                   name="missing_qty[{{ $item->id }}]"
                                                                   min="1" 
                                                                   max="{{ $item->ordered_qty }}" 
                                                                   value="1"
                                                                   style="width: 120px;">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ translate('Missing Reason') }}</label>
                                                            <select class="form-control missing-reason-select" 
                                                                    name="missing_reason[{{ $item->id }}]"
                                                                    style="width: 200px;"
                                                                    required>
                                                                <option value="">{{ translate('Select Reason') }}</option>
                                                                <option value="out_of_stock">{{ translate('Out of Stock') }}</option>
                                                                <option value="damaged">{{ translate('Damaged') }}</option>
                                                                <option value="expired">{{ translate('Expired') }}</option>
                                                                <option value="not_found">{{ translate('Not Found') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        @if ($allPicked)
                                            <td>
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
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (!$allPicked)
                        <div class="mt-4 text-right">
                            <button type="submit" class="btn btn-success btn-lg">
                                {{ translate('Complete Picking') }}
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Delivery Man Assignment Card -->
        @if ($order->order_status == 'processing' || $allPicked)
            <div class="card">
                <div class="card-header">
                    <h5>{{ translate('Assign Delivery Man') }}</h5>
                </div>
                <div class="card-body">
                    @if ($order->delivery_man_id)
                        <div class="alert alert-info">
                            {{ translate('Delivery man already assigned') }}: 
                            @php
                                $assignedDM = $deliveryMen->firstWhere('id', $order->delivery_man_id);
                            @endphp
                            @if ($assignedDM)
                                {{ $assignedDM->f_name }} {{ $assignedDM->l_name }}
                            @endif
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('admin.picking.bulk-assign') }}">
                        @csrf
                        <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Select Delivery Man') }}</label>
                                    <select class="custom-select" name="delivery_man_id" required>
                                        <option value="">{{ translate('Select Delivery Man') }}</option>
                                        @foreach ($deliveryMen as $dm)
                                            <option value="{{ $dm->id }}" {{ $order->delivery_man_id == $dm->id ? 'selected' : '' }}>
                                                {{ $dm->f_name }} {{ $dm->l_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    {{ translate('Assign Delivery Man') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script_2')
    <script>
        $(document).ready(function() {
            // Show/hide missing fields when checkbox is toggled
            $('.mark-missing-checkbox').on('change', function() {
                const itemId = $(this).val();
                const fieldsDiv = $('#missing_fields_' + itemId);
                const missingQtyInput = fieldsDiv.find('.missing-qty-input');
                const missingReasonSelect = fieldsDiv.find('.missing-reason-select');
                
                if ($(this).is(':checked')) {
                    fieldsDiv.show();
                    missingQtyInput.prop('required', true);
                    missingReasonSelect.prop('required', true);
                } else {
                    fieldsDiv.hide();
                    missingQtyInput.prop('required', false);
                    missingReasonSelect.prop('required', false);
                }
            });

            // Complete picking form submit confirmation
            $('#completePickingForm').on('submit', function(e) {
                if (!confirm('{{ translate("Are you sure you want to complete picking? This will update the order amount based on picked quantities.") }}')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
