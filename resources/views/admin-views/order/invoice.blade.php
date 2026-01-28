@extends('layouts.admin.app')

@section('title', translate('Invoice'))

@push('css_or_js')
<style>
    @media print {
        @page { size: 80mm auto; margin: 0; }
        body { margin: 0; padding: 0; }
        .no-print { display: none !important; }
        .invoice-box { width: 80mm; max-width: 80mm; margin: 0; padding: 3mm; }
    }
    
    .invoice-box { max-width: 320px; margin: 0 auto; background: white; padding: 15px; font-family: 'Courier New', monospace; font-size: 12px; }
    .invoice-header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; margin-bottom: 10px; }
    .invoice-header img { max-width: 60px; margin-bottom: 8px; }
    .invoice-header h3 { margin: 5px 0; font-size: 16px; }
    .invoice-header p { margin: 2px 0; font-size: 11px; color: #666; }
    
    .info-row { display: flex; justify-content: space-between; margin: 3px 0; font-size: 11px; }
    .divider { text-align: center; margin: 8px 0; color: #999; font-size: 10px; letter-spacing: 2px; }
    
    .items-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .items-table th { border-bottom: 1px solid #333; padding: 6px 3px; font-size: 10px; text-align: left; }
    .items-table th:last-child { text-align: right; }
    .items-table td { padding: 6px 3px; border-bottom: 1px dashed #ddd; vertical-align: top; font-size: 11px; }
    .items-table td:last-child { text-align: right; }
    
    .item-name { font-weight: 600; }
    .item-meta { font-size: 9px; color: #666; }
    .strike { text-decoration: line-through; color: #999; }
    .returned-tag { background: #dc3545; color: white; font-size: 8px; padding: 1px 4px; border-radius: 3px; }
    .partial-tag { background: #ffc107; color: #333; font-size: 8px; padding: 1px 4px; border-radius: 3px; }
    
    .summary-section { border-top: 2px dashed #333; padding-top: 10px; margin-top: 10px; }
    .summary-row { display: flex; justify-content: space-between; margin: 5px 0; font-size: 11px; }
    .summary-row.total { font-size: 14px; font-weight: bold; border-top: 1px solid #333; padding-top: 8px; margin-top: 8px; }
    .summary-row .strike { text-decoration: line-through; color: #999; }
    .text-danger { color: #dc3545; }
    .text-success { color: #28a745; }
    
    .footer { text-align: center; border-top: 2px dashed #333; padding-top: 10px; margin-top: 10px; font-size: 10px; }
    .adjustment-box { background: #fff8e1; border: 1px dashed #ffc107; padding: 8px; margin: 10px 0; font-size: 10px; }
    .adjustment-box h4 { margin: 0 0 5px; font-size: 11px; color: #856404; }
    
    .btn-area { text-align: center; margin: 20px 0; }
</style>
@endpush

@section('content')
@php
    use App\CentralLogics\Helpers;
    
    // Business settings
    $logo = \App\Model\BusinessSetting::where('key', 'logo')->first()->value ?? '';
    $phone = \App\Model\BusinessSetting::where('key', 'phone')->first()->value ?? '';
    
    // Get edit logs
    $editLogs = $order->editLogs ?? collect();
    $logsByDetail = $editLogs->groupBy('order_detail_id');
    
    // Process items
    $orderItems = [];
    $adjustments = [];
    $calculatedSubtotal = 0;
    $calculatedOriginalTotal = 0;
    $totalReturnedAmount = 0;
    $totalTaxAmount = 0;
    
    foreach($order->details as $detail) {
        $product = is_array($detail->product_details) ? $detail->product_details : json_decode($detail->product_details, true);
        $productModel = $detail->product;
        
        $itemLogs = $logsByDetail->get($detail->id, collect())->sortBy('created_at');
        $firstLog = $itemLogs->first();
        $lastLog = $itemLogs->last();
        
        // Get Unit Price
        $unitPrice = 0;
        if ($detail->price > 0) {
            $unitPrice = $detail->price;
        } elseif ($firstLog && $firstLog->old_quantity > 0 && $firstLog->old_price > 0) {
            $unitPrice = $firstLog->old_price / $firstLog->old_quantity;
        } elseif (isset($product['price']) && $product['price'] > 0) {
            $unitPrice = $product['price'];
        } elseif ($productModel && $productModel->price > 0) {
            $unitPrice = $productModel->price;
        }
        
        // Quantities
        $originalQty = $detail->quantity;
        $currentQty = $detail->quantity;
        if ($firstLog) $originalQty = $firstLog->old_quantity;
        if ($lastLog) $currentQty = $lastLog->new_quantity;
        $returnedQty = max(0, $originalQty - $currentQty);
        
        // Discount
        $discountPerUnit = $detail->discount_on_product ?? 0;
        $discountType = $detail->discount_type ?? 'amount';
        $discountAmount = ($discountType == 'percent') ? ($unitPrice * $discountPerUnit / 100) : $discountPerUnit;
        $netPricePerUnit = $unitPrice - $discountAmount;
        
        // Tax
        $taxPerUnit = $detail->tax_amount ?? 0;
        $currentTax = $taxPerUnit * $currentQty;
        
        // Totals
        $originalLineTotal = $netPricePerUnit * $originalQty;
        $currentLineTotal = $netPricePerUnit * $currentQty;
        $returnedAmount = $netPricePerUnit * $returnedQty;
        
        $calculatedOriginalTotal += $originalLineTotal;
        $calculatedSubtotal += $currentLineTotal;
        $totalReturnedAmount += $returnedAmount;
        $totalTaxAmount += $currentTax;
        
        $productName = $product['name'] ?? ($productModel->name ?? 'Item');
        
        $orderItems[] = [
            'name' => $productName,
            'unitPrice' => $unitPrice,
            'discountPerUnit' => $discountAmount,
            'netPricePerUnit' => $netPricePerUnit,
            'originalQty' => $originalQty,
            'currentQty' => $currentQty,
            'returnedQty' => $returnedQty,
            'originalLineTotal' => $originalLineTotal,
            'currentLineTotal' => $currentLineTotal,
            'unit' => $detail->unit ?? 'pcs',
            'isFullyReturned' => $currentQty == 0,
            'isPartiallyReturned' => $returnedQty > 0 && $currentQty > 0,
            'hasChanges' => $itemLogs->count() > 0,
        ];
        
        // Track adjustments
        if ($returnedQty > 0 && $lastLog) {
            $adjustments[] = [
                'name' => $productName,
                'qty' => $returnedQty,
                'amount' => $returnedAmount,
                'reason' => $lastLog->reason ?? 'Returned',
            ];
        }
    }
    
    // Final calculations
    $deliveryCharge = $order->delivery_charge ?? 0;
    $couponDiscount = $order->coupon_discount_amount ?? 0;
    $extraDiscount = $order->extra_discount ?? 0;
    
    $subtotalWithTax = $calculatedSubtotal + $totalTaxAmount;
    $subtotalWithDelivery = $subtotalWithTax + $deliveryCharge;
    $finalPayable = max(0, $subtotalWithDelivery - $couponDiscount - $extraDiscount);
    
    $totalPaid = $order->payments ? $order->payments->where('payment_status', 'complete')->sum('amount') : 0;
    $dueAmount = $finalPayable - $totalPaid;
    
    $hasAdjustments = count($adjustments) > 0;
@endphp

<div class="content container-fluid">
    
    <!-- Buttons -->
    <div class="btn-area no-print">
        <button class="btn btn-primary" onclick="printInvoice()">
            <i class="tio-print mr-1"></i> {{ translate('Print') }}
        </button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="tio-chevron-left mr-1"></i> {{ translate('Back') }}
        </a>
    </div>
    
    <!-- Invoice -->
    <div class="invoice-box" id="printableInvoice">
        
        <!-- Header -->
        <div class="invoice-header">
            @if($logo)
                <img src="{{ asset('storage/app/public/restaurant/' . $logo) }}" alt="">
            @endif
            <h3>{{ $order->branch->name ?? 'Store' }}</h3>
            <p>{{ $order->branch->address ?? '' }}</p>
            <p>Tel: {{ $phone }}</p>
            @if($order->branch && $order->branch->gst_status)
                <p>GST: {{ $order->branch->gst_code }}</p>
            @endif
        </div>
        
        <div class="divider">- - - - - - - - - - - - - - -</div>
        
        <!-- Order Info -->
        <div class="info-row"><span>Order #:</span><strong>{{ $order->id }}</strong></div>
        <div class="info-row"><span>Date:</span><span>{{ $order->created_at->format('d/m/Y h:i A') }}</span></div>
        <div class="info-row"><span>Status:</span><strong>{{ ucfirst($order->order_status) }}</strong></div>
        <div class="info-row"><span>Payment:</span><span>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span></div>
        
        @if($order->customer)
            <div class="divider">- - - - - - - - - - - - - - -</div>
            <div class="info-row"><span>Customer:</span><span>{{ $order->customer->f_name }} {{ $order->customer->l_name }}</span></div>
            <div class="info-row"><span>Phone:</span><span>{{ $order->customer->phone }}</span></div>
        @endif
        
        <div class="divider">- - - - - - - - - - - - - - -</div>
        
        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item</th>
                    <th style="width: 20%;">Qty</th>
                    <th style="width: 30%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderItems as $item)
                <tr>
                    <td>
                        <div class="item-name {{ $item['isFullyReturned'] ? 'strike' : '' }}">
                            {{ Str::limit($item['name'], 20) }}
                            @if($item['isFullyReturned'])
                                <span class="returned-tag">RET</span>
                            @elseif($item['isPartiallyReturned'])
                                <span class="partial-tag">{{ $item['returnedQty'] }}R</span>
                            @endif
                        </div>
                        <div class="item-meta">
                            @ {{ Helpers::set_symbol($item['unitPrice']) }}
                            @if($item['discountPerUnit'] > 0)
                                (-{{ Helpers::set_symbol($item['discountPerUnit']) }})
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($item['hasChanges'])
                            <span class="strike">{{ $item['originalQty'] }}</span>
                            <br>{{ $item['currentQty'] }}
                        @else
                            {{ $item['currentQty'] }}
                        @endif
                    </td>
                    <td>
                        @if($item['hasChanges'] && $item['originalLineTotal'] != $item['currentLineTotal'])
                            <span class="strike">{{ Helpers::set_symbol($item['originalLineTotal']) }}</span><br>
                        @endif
                        <strong>{{ Helpers::set_symbol($item['currentLineTotal']) }}</strong>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Adjustments -->
        @if($hasAdjustments)
        <div class="adjustment-box">
            <h4>⚠ Adjustments</h4>
            @foreach($adjustments as $adj)
                <div class="info-row">
                    <span>{{ Str::limit($adj['name'], 15) }} ({{ $adj['qty'] }} ret.)</span>
                    <span class="text-danger">-{{ Helpers::set_symbol($adj['amount']) }}</span>
                </div>
                <div style="font-size: 9px; color: #666;">Reason: {{ $adj['reason'] }}</div>
            @endforeach
        </div>
        @endif
        
        <!-- Summary -->
        <div class="summary-section">
            
            @if($hasAdjustments && $totalReturnedAmount > 0)
            <div class="summary-row">
                <span>Original Total:</span>
                <span class="strike">{{ Helpers::set_symbol($calculatedOriginalTotal) }}</span>
            </div>
            <div class="summary-row">
                <span>Returns:</span>
                <span class="text-danger">-{{ Helpers::set_symbol($totalReturnedAmount) }}</span>
            </div>
            @endif
            
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>{{ Helpers::set_symbol($calculatedSubtotal) }}</span>
            </div>
            
            @if($totalTaxAmount > 0)
            <div class="summary-row">
                <span>Tax:</span>
                <span>+{{ Helpers::set_symbol($totalTaxAmount) }}</span>
            </div>
            @endif
            
            @if($deliveryCharge > 0)
            <div class="summary-row">
                <span>Delivery:</span>
                <span>+{{ Helpers::set_symbol($deliveryCharge) }}</span>
            </div>
            @endif
            
            @if($couponDiscount > 0)
            <div class="summary-row">
                <span>Coupon:</span>
                <span class="text-success">-{{ Helpers::set_symbol($couponDiscount) }}</span>
            </div>
            @endif
            
            @if($extraDiscount > 0)
            <div class="summary-row">
                <span>Discount:</span>
                <span class="text-success">-{{ Helpers::set_symbol($extraDiscount) }}</span>
            </div>
            @endif
            
            <div class="summary-row total">
                <span>TOTAL:</span>
                <span>{{ Helpers::set_symbol($finalPayable) }}</span>
            </div>
            
            @if($totalPaid > 0)
            <div class="summary-row">
                <span>Paid:</span>
                <span class="text-success">{{ Helpers::set_symbol($totalPaid) }}</span>
            </div>
            @endif
            
            @if($dueAmount > 0)
            <div class="summary-row" style="background: #fff3cd; padding: 5px; margin: 5px -5px;">
                <strong>DUE:</strong>
                <strong class="text-danger">{{ Helpers::set_symbol($dueAmount) }}</strong>
            </div>
            @elseif($dueAmount < 0)
            <div class="summary-row" style="background: #d4edda; padding: 5px; margin: 5px -5px;">
                <strong>REFUND:</strong>
                <strong>{{ Helpers::set_symbol(abs($dueAmount)) }}</strong>
            </div>
            @else
            <div class="summary-row" style="background: #d4edda; padding: 5px; margin: 5px -5px;">
                <strong>PAID ✓</strong>
                <span></span>
            </div>
            @endif
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank You!</strong></p>
            @if($hasAdjustments)
                <p style="color: #856404;">* Order modified after placement</p>
            @endif
            <p style="color: #999;">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
        
    </div>
</div>
@endsection

@push('script')
<script>
function printInvoice() {
    var content = document.getElementById('printableInvoice').innerHTML;
    var original = document.body.innerHTML;
    document.body.innerHTML = content;
    window.print();
    document.body.innerHTML = original;
    location.reload();
}
</script>
@endpush
