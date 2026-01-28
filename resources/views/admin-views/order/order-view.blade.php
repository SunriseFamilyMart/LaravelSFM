@extends('layouts.admin.app')

@section('title', translate('Order Details'))

@push('css_or_js')
<style>
    .order-card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-radius: 10px; margin-bottom: 20px; overflow: hidden; }
    .order-card .card-header { padding: 15px 20px; border: none; }
    .order-card .card-header.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .order-card .card-header.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
    .order-card .card-header.warning { background: linear-gradient(135deg, #f5af19 0%, #f12711 100%); color: white; }
    .order-card .card-header.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    
    .items-table { width: 100%; }
    .items-table th { background: #f8f9fa; padding: 12px 15px; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 2px solid #dee2e6; }
    .items-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #eee; }
    .items-table tr:hover { background: #fafafa; }
    
    .product-cell { display: flex; align-items: center; gap: 12px; }
    .product-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
    .product-name { font-weight: 600; color: #333; margin-bottom: 3px; }
    .product-meta { font-size: 12px; color: #888; }
    
    .qty-change { display: flex; align-items: center; gap: 5px; }
    .qty-old { color: #999; text-decoration: line-through; font-size: 13px; }
    .qty-arrow { color: #666; }
    .qty-new { font-weight: 600; color: #28a745; }
    .qty-returned { background: #dc3545; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 5px; }
    
    .price-strike { color: #999; text-decoration: line-through; font-size: 12px; display: block; }
    .price-final { font-weight: 600; color: #333; }
    .price-negative { color: #dc3545; }
    .price-positive { color: #28a745; }
    
    .returned-row { background: #fff5f5 !important; }
    .returned-badge { background: #dc3545; color: white; font-size: 10px; padding: 2px 8px; border-radius: 10px; }
    .partial-badge { background: #ffc107; color: #333; font-size: 10px; padding: 2px 8px; border-radius: 10px; }
    
    .summary-box { background: #f8f9fa; border-radius: 10px; padding: 20px; }
    .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
    .summary-row:last-child { border-bottom: none; }
    .summary-row.total { font-size: 18px; font-weight: 700; border-top: 2px solid #333; margin-top: 10px; padding-top: 15px; }
    .summary-row .label { color: #666; }
    .summary-row .value { font-weight: 600; }
    .text-success { color: #28a745 !important; }
    .text-danger { color: #dc3545 !important; }
    
    .adjustment-timeline { padding-left: 25px; border-left: 3px solid #ffc107; margin: 15px 0; }
    .adjustment-item { padding: 15px 0; border-bottom: 1px dashed #eee; position: relative; }
    .adjustment-item::before { content: ''; position: absolute; left: -32px; top: 20px; width: 12px; height: 12px; border-radius: 50%; background: #ffc107; border: 2px solid white; }
    .adjustment-item.decrease::before { background: #dc3545; }
    .adjustment-item.increase::before { background: #28a745; }
    
    @media print { .no-print { display: none !important; } }
</style>
<link rel="stylesheet" href="{{ asset('/public/assets/admin/css/lightbox.min.css') }}">
@endpush

@section('content')
@php
    use App\CentralLogics\Helpers;
    
    // Get edit logs
    $editLogs = $order->editLogs ?? collect();
    $logsByDetail = $editLogs->groupBy('order_detail_id');
    
    // Process all items and calculate totals
    $orderItems = [];
    $calculatedSubtotal = 0;
    $calculatedOriginalTotal = 0;
    $totalReturnedAmount = 0;
    $totalDiscountAmount = 0;
    $totalTaxAmount = 0;
    
    foreach($order->details as $detail) {
        $product = is_array($detail->product_details) ? $detail->product_details : json_decode($detail->product_details, true);
        $productModel = $detail->product;
        
        // Get edit logs for this item
        $itemLogs = $logsByDetail->get($detail->id, collect())->sortBy('created_at');
        $firstLog = $itemLogs->first();
        $lastLog = $itemLogs->last();
        
        // CRITICAL: Calculate Unit Price from multiple sources
        $unitPrice = 0;
        
        // 1. First try from order_details.price
        if ($detail->price > 0) {
            $unitPrice = $detail->price;
        }
        // 2. Try from edit log (old_price / old_quantity gives unit price)
        elseif ($firstLog && $firstLog->old_quantity > 0 && $firstLog->old_price > 0) {
            $unitPrice = $firstLog->old_price / $firstLog->old_quantity;
        }
        // 3. Try from product_details JSON
        elseif (isset($product['price']) && $product['price'] > 0) {
            $unitPrice = $product['price'];
        }
        // 4. Fallback to products table
        elseif ($productModel && $productModel->price > 0) {
            $unitPrice = $productModel->price;
        }
        
        // Get quantities
        $originalQty = $detail->quantity;
        $currentQty = $detail->quantity;
        $returnedQty = 0;
        
        if ($firstLog) {
            $originalQty = $firstLog->old_quantity;
        }
        if ($lastLog) {
            $currentQty = $lastLog->new_quantity;
        }
        $returnedQty = max(0, $originalQty - $currentQty);
        
        // Get discount per unit
        $discountPerUnit = $detail->discount_on_product ?? 0;
        $discountType = $detail->discount_type ?? 'amount';
        
        // Calculate actual discount
        if ($discountType == 'percent') {
            $discountAmount = ($unitPrice * $discountPerUnit / 100);
        } else {
            $discountAmount = $discountPerUnit;
        }
        
        // Calculate net price per unit
        $netPricePerUnit = $unitPrice - $discountAmount;
        
        // Calculate totals for this item
        $originalLineTotal = $netPricePerUnit * $originalQty;
        $currentLineTotal = $netPricePerUnit * $currentQty;
        $returnedAmount = $netPricePerUnit * $returnedQty;
        
        // Tax per unit
        $taxPerUnit = $detail->tax_amount ?? 0;
        $currentTax = $taxPerUnit * $currentQty;
        
        // Add to order totals
        $calculatedOriginalTotal += $originalLineTotal;
        $calculatedSubtotal += $currentLineTotal;
        $totalReturnedAmount += $returnedAmount;
        $totalDiscountAmount += $discountAmount * $currentQty;
        $totalTaxAmount += $currentTax;
        
        $orderItems[] = [
            'detail' => $detail,
            'product' => $product,
            'productModel' => $productModel,
            'unitPrice' => $unitPrice,
            'discountPerUnit' => $discountAmount,
            'discountType' => $discountType,
            'discountPercent' => $discountType == 'percent' ? $discountPerUnit : 0,
            'netPricePerUnit' => $netPricePerUnit,
            'taxPerUnit' => $taxPerUnit,
            'originalQty' => $originalQty,
            'currentQty' => $currentQty,
            'returnedQty' => $returnedQty,
            'originalLineTotal' => $originalLineTotal,
            'currentLineTotal' => $currentLineTotal,
            'returnedAmount' => $returnedAmount,
            'currentTax' => $currentTax,
            'logs' => $itemLogs,
            'isFullyReturned' => $currentQty == 0,
            'isPartiallyReturned' => $returnedQty > 0 && $currentQty > 0,
            'hasChanges' => $itemLogs->count() > 0,
        ];
    }
    
    // Calculate final payable
    $deliveryCharge = $order->delivery_charge ?? 0;
    $couponDiscount = $order->coupon_discount_amount ?? 0;
    $extraDiscount = $order->extra_discount ?? 0;
    
    // Final calculation
    $subtotalAfterItemDiscount = $calculatedSubtotal;
    $subtotalWithTax = $subtotalAfterItemDiscount + $totalTaxAmount;
    $subtotalWithDelivery = $subtotalWithTax + $deliveryCharge;
    $totalAfterCoupon = $subtotalWithDelivery - $couponDiscount - $extraDiscount;
    
    $finalPayable = max(0, $totalAfterCoupon);
    
    // Payment info
    $totalPaid = $order->payments ? $order->payments->where('payment_status', 'complete')->sum('amount') : 0;
    $dueAmount = $finalPayable - $totalPaid;
    
    $hasAdjustments = $editLogs->count() > 0;
@endphp

<div class="content container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h1 class="h3 mb-1"><i class="tio-receipt mr-2"></i>{{ translate('Order') }} #{{ $order->id }}</h1>
            <p class="text-muted mb-0">{{ $order->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div>
            <a href="{{ route('admin.orders.generate-invoice', $order->id) }}" class="btn btn-primary">
                <i class="tio-print mr-1"></i> {{ translate('Print Invoice') }}
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-secondary ml-2">
                <i class="tio-chevron-left mr-1"></i> {{ translate('Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            
            <!-- Order Status -->
            <div class="order-card">
                <div class="card-header {{ $order->order_status == 'delivered' ? 'success' : ($order->order_status == 'returned' ? 'warning' : 'primary') }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ translate('Status') }}: {{ ucfirst(str_replace('_', ' ', $order->order_status)) }}</h5>
                            <small class="opacity-75">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }} | {{ ucfirst($order->payment_status) }}</small>
                        </div>
                        @if($hasAdjustments)
                            <span class="badge badge-warning">{{ $editLogs->count() }} {{ translate('Adjustments') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-card">
                <div class="card-header info">
                    <h5 class="mb-0"><i class="tio-shopping-cart mr-2"></i>{{ translate('Order Items') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>{{ translate('Product') }}</th>
                                    <th class="text-center">{{ translate('Unit Price') }}</th>
                                    <th class="text-center">{{ translate('Qty') }}</th>
                                    <th class="text-center">{{ translate('Discount') }}</th>
                                    <th class="text-right">{{ translate('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($orderItems as $item)
                                <tr class="{{ $item['isFullyReturned'] ? 'returned-row' : '' }}">
                                    <td>
                                        <div class="product-cell">
                                            @if($item['productModel'] && isset($item['productModel']->image))
                                                <img src="{{ $item['productModel']->identityImageFullPath[0] ?? asset('public/assets/admin/img/160x160/2.png') }}" class="product-img">
                                            @else
                                                <img src="{{ asset('public/assets/admin/img/160x160/2.png') }}" class="product-img">
                                            @endif
                                            <div>
                                                <div class="product-name">
                                                    {{ $item['product']['name'] ?? ($item['productModel']->name ?? 'N/A') }}
                                                    @if($item['isFullyReturned'])
                                                        <span class="returned-badge">{{ translate('RETURNED') }}</span>
                                                    @elseif($item['isPartiallyReturned'])
                                                        <span class="partial-badge">{{ $item['returnedQty'] }} {{ translate('Returned') }}</span>
                                                    @endif
                                                </div>
                                                <div class="product-meta">{{ $item['detail']->unit ?? 'pcs' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="price-final">{{ Helpers::set_symbol($item['unitPrice']) }}</div>
                                        @if($item['discountPerUnit'] > 0)
                                            <small class="text-danger">-{{ Helpers::set_symbol($item['discountPerUnit']) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item['hasChanges'])
                                            <div class="qty-change">
                                                <span class="qty-old">{{ $item['originalQty'] }}</span>
                                                <span class="qty-arrow">→</span>
                                                <span class="qty-new">{{ $item['currentQty'] }}</span>
                                            </div>
                                            @if($item['returnedQty'] > 0)
                                                <span class="qty-returned">-{{ $item['returnedQty'] }}</span>
                                            @endif
                                        @else
                                            {{ $item['currentQty'] }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item['discountPerUnit'] > 0)
                                            <span class="text-danger">-{{ Helpers::set_symbol($item['discountPerUnit'] * $item['currentQty']) }}</span>
                                            @if($item['discountPercent'] > 0)
                                                <br><small class="text-muted">({{ $item['discountPercent'] }}%)</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($item['hasChanges'] && $item['originalLineTotal'] != $item['currentLineTotal'])
                                            <span class="price-strike">{{ Helpers::set_symbol($item['originalLineTotal']) }}</span>
                                        @endif
                                        <span class="price-final {{ $item['isFullyReturned'] ? 'text-danger' : '' }}">
                                            {{ Helpers::set_symbol($item['currentLineTotal']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Adjustments Timeline -->
            @if($hasAdjustments)
            <div class="order-card">
                <div class="card-header warning">
                    <h5 class="mb-0"><i class="tio-history mr-2"></i>{{ translate('Adjustment History') }}</h5>
                </div>
                <div class="card-body">
                    <div class="adjustment-timeline">
                        @foreach($editLogs->sortByDesc('created_at') as $log)
                            @php
                                $logProduct = $log->orderDetail ? ($log->orderDetail->product_details ?? []) : [];
                                if (is_string($logProduct)) $logProduct = json_decode($logProduct, true) ?? [];
                                $logProductName = is_array($logProduct) ? ($logProduct['name'] ?? 'Unknown') : 'Unknown';
                                $qtyDiff = $log->new_quantity - $log->old_quantity;
                                $priceDiff = $log->new_price - $log->old_price;
                            @endphp
                            <div class="adjustment-item {{ $qtyDiff < 0 ? 'decrease' : ($qtyDiff > 0 ? 'increase' : '') }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $logProductName }}</strong>
                                        <div class="text-muted small">
                                            {{ translate('Qty') }}: {{ $log->old_quantity }} → {{ $log->new_quantity }}
                                            <span class="badge {{ $qtyDiff < 0 ? 'badge-danger' : 'badge-success' }} ml-2">
                                                {{ $qtyDiff >= 0 ? '+' : '' }}{{ $qtyDiff }}
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge badge-secondary">{{ $log->reason ?? 'No reason' }}</span>
                                        </div>
                                        <small class="text-muted">
                                            {{ $log->created_at->format('d M Y, h:i A') }}
                                            @if($log->deliveryMan)
                                                | {{ $log->deliveryMan->f_name }} {{ $log->deliveryMan->l_name }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-right">
                                        <span class="{{ $priceDiff < 0 ? 'text-danger' : 'text-success' }} font-weight-bold">
                                            {{ $priceDiff >= 0 ? '+' : '' }}{{ Helpers::set_symbol($priceDiff) }}
                                        </span>
                                        @if($log->photo)
                                            <br>
                                            <a href="{{ asset('storage/' . $log->photo) }}" data-lightbox="log-{{ $log->id }}" class="btn btn-sm btn-outline-info mt-2">
                                                <i class="tio-image"></i> {{ translate('Photo') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

        <!-- Right Column - Summary -->
        <div class="col-lg-4">
            <div class="order-card">
                <div class="card-header primary">
                    <h5 class="mb-0"><i class="tio-money mr-2"></i>{{ translate('Payment Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="summary-box">
                        
                        @if($hasAdjustments && $totalReturnedAmount > 0)
                        <div class="summary-row">
                            <span class="label">{{ translate('Original Total') }}</span>
                            <span class="value text-muted" style="text-decoration: line-through;">{{ Helpers::set_symbol($calculatedOriginalTotal) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="label text-danger">{{ translate('Returns/Adjustments') }}</span>
                            <span class="value text-danger">-{{ Helpers::set_symbol($totalReturnedAmount) }}</span>
                        </div>
                        @endif
                        
                        <div class="summary-row">
                            <span class="label">{{ translate('Items Subtotal') }}</span>
                            <span class="value">{{ Helpers::set_symbol($calculatedSubtotal) }}</span>
                        </div>
                        
                        @if($totalTaxAmount > 0)
                        <div class="summary-row">
                            <span class="label">{{ translate('Tax/VAT') }}</span>
                            <span class="value">+{{ Helpers::set_symbol($totalTaxAmount) }}</span>
                        </div>
                        @endif
                        
                        @if($deliveryCharge > 0)
                        <div class="summary-row">
                            <span class="label">{{ translate('Delivery Charge') }}</span>
                            <span class="value">+{{ Helpers::set_symbol($deliveryCharge) }}</span>
                        </div>
                        @endif
                        
                        @if($couponDiscount > 0)
                        <div class="summary-row">
                            <span class="label text-success">{{ translate('Coupon Discount') }}</span>
                            <span class="value text-success">-{{ Helpers::set_symbol($couponDiscount) }}</span>
                        </div>
                        @endif
                        
                        @if($extraDiscount > 0)
                        <div class="summary-row">
                            <span class="label text-success">{{ translate('Extra Discount') }}</span>
                            <span class="value text-success">-{{ Helpers::set_symbol($extraDiscount) }}</span>
                        </div>
                        @endif
                        
                        <div class="summary-row total">
                            <span class="label">{{ translate('Total Payable') }}</span>
                            <span class="value text-primary" style="font-size: 22px;">{{ Helpers::set_symbol($finalPayable) }}</span>
                        </div>
                        
                        @if($totalPaid > 0)
                        <div class="summary-row">
                            <span class="label text-success">{{ translate('Amount Paid') }}</span>
                            <span class="value text-success">{{ Helpers::set_symbol($totalPaid) }}</span>
                        </div>
                        @endif
                        
                        @if($dueAmount > 0)
                        <div class="summary-row" style="background: #fff3cd; margin: 10px -20px -20px; padding: 15px 20px; border-radius: 0 0 10px 10px;">
                            <span class="label text-danger font-weight-bold">{{ translate('Due Amount') }}</span>
                            <span class="value text-danger font-weight-bold" style="font-size: 20px;">{{ Helpers::set_symbol($dueAmount) }}</span>
                        </div>
                        @elseif($dueAmount < 0)
                        <div class="summary-row" style="background: #d4edda; margin: 10px -20px -20px; padding: 15px 20px; border-radius: 0 0 10px 10px;">
                            <span class="label text-info font-weight-bold">{{ translate('Refund Due') }}</span>
                            <span class="value text-info font-weight-bold">{{ Helpers::set_symbol(abs($dueAmount)) }}</span>
                        </div>
                        @else
                        <div class="summary-row" style="background: #d4edda; margin: 10px -20px -20px; padding: 15px 20px; border-radius: 0 0 10px 10px;">
                            <span class="label text-success font-weight-bold">{{ translate('Fully Paid') }}</span>
                            <span class="value text-success">✓</span>
                        </div>
                        @endif
                        
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            @if($order->customer)
            <div class="order-card">
                <div class="card-header success">
                    <h5 class="mb-0"><i class="tio-user mr-2"></i>{{ translate('Customer') }}</h5>
                </div>
                <div class="card-body">
                    <strong>{{ $order->customer->f_name }} {{ $order->customer->l_name }}</strong>
                    <p class="text-muted mb-1"><i class="tio-call mr-1"></i>{{ $order->customer->phone }}</p>
                    @if($order->delivery_address)
                        @php $addr = is_array($order->delivery_address) ? $order->delivery_address : json_decode($order->delivery_address, true); @endphp
                        <hr>
                        <p class="mb-0 small">{{ $addr['address'] ?? '' }}</p>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/public/assets/admin/js/lightbox.min.js') }}"></script>
@endpush
