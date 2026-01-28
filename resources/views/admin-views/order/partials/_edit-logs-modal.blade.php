{{-- Professional Order Edit Logs Modal --}}
<style>
    .edit-logs-modal .modal-dialog {
        max-width: 1100px;
    }
    .edit-log-card {
        border-radius: 10px;
        border: 1px solid #e7e7e7;
        margin-bottom: 15px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .edit-log-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .edit-log-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 12px 15px;
        border-bottom: 1px solid #e7e7e7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .edit-log-body {
        padding: 15px;
        background: #fff;
    }
    .log-action-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .log-action-badge.increase { background: #d4edda; color: #155724; }
    .log-action-badge.decrease { background: #fff3cd; color: #856404; }
    .log-action-badge.partial-return { background: #cce5ff; color: #004085; }
    .log-action-badge.full-return { background: #f8d7da; color: #721c24; }
    .log-action-badge.adjustment { background: #e2e3e5; color: #383d41; }
    
    .qty-change-box {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    .qty-change-box .old-val {
        background: #f8d7da;
        color: #721c24;
        padding: 4px 10px;
        border-radius: 4px;
        font-weight: 600;
        text-decoration: line-through;
    }
    .qty-change-box .new-val {
        background: #d4edda;
        color: #155724;
        padding: 4px 10px;
        border-radius: 4px;
        font-weight: 600;
    }
    .qty-change-box .arrow {
        color: #6c757d;
    }
    
    .price-change-box {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    .price-change-box .old-price {
        color: #dc3545;
        text-decoration: line-through;
        opacity: 0.7;
    }
    .price-change-box .new-price {
        color: #28a745;
        font-weight: 600;
    }
    .price-change-box .diff {
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    .price-change-box .diff.positive { background: #d4edda; color: #155724; }
    .price-change-box .diff.negative { background: #f8d7da; color: #721c24; }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .info-item .label {
        font-size: 11px;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .info-item .value {
        font-size: 14px;
        color: #343a40;
        font-weight: 500;
    }
    
    .log-photo-thumb {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .log-photo-thumb:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .reason-box {
        background: #fff8e1;
        border-left: 4px solid #ffc107;
        padding: 10px 15px;
        border-radius: 0 8px 8px 0;
        margin-top: 10px;
    }
    .reason-box .reason-label {
        font-size: 11px;
        color: #856404;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .reason-box .reason-text {
        font-size: 14px;
        color: #333;
    }
    
    .summary-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .summary-card .summary-title {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .summary-stats {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }
    .summary-stat {
        text-align: center;
    }
    .summary-stat .stat-value {
        font-size: 28px;
        font-weight: 700;
    }
    .summary-stat .stat-label {
        font-size: 12px;
        opacity: 0.8;
    }
    
    .editor-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        background: #e9ecef;
        padding: 4px 10px;
        border-radius: 15px;
    }
    .editor-badge i {
        font-size: 14px;
    }
    
    .timeline-date {
        font-size: 12px;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-state img {
        width: 120px;
        opacity: 0.6;
        margin-bottom: 15px;
    }
    .empty-state p {
        color: #6c757d;
        font-size: 16px;
    }

    .order-amount-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }
    .amount-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed #dee2e6;
    }
    .amount-row:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 16px;
        padding-top: 12px;
        margin-top: 8px;
        border-top: 2px solid #dee2e6;
    }
</style>

<button type="button" class="btn btn-outline-info btn-sm d-flex align-items-center gap-2" 
        data-bs-toggle="modal" data-bs-target="#editLogsModal">
    <i class="tio-history"></i>
    {{ translate('View Edit Logs') }}
    @if($order->editLogs->count() > 0)
        <span class="badge badge-soft-danger ml-1">{{ $order->editLogs->count() }}</span>
    @endif
</button>

<div class="modal fade edit-logs-modal" id="editLogsModal" tabindex="-1" aria-labelledby="editLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title mb-1" id="editLogsModalLabel">
                        <i class="tio-history text-primary mr-2"></i>
                        {{ translate('Order Edit History') }}
                    </h5>
                    <p class="text-muted mb-0 small">Order #{{ $order->id }} — {{ translate('All modifications and returns') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                @if ($order->editLogs->count() > 0)
                    @php
                        $logs = $order->editLogs->sortByDesc('created_at');
                        $totalReturned = $logs->sum(function($log) {
                            return max(0, $log->old_quantity - $log->new_quantity);
                        });
                        $totalPriceReduction = $logs->sum(function($log) {
                            return $log->old_price - $log->new_price;
                        });
                        $hasPartial = $logs->contains(fn($l) => $l->new_quantity > 0 && $l->new_quantity < $l->old_quantity);
                        $hasFull = $logs->contains(fn($l) => $l->new_quantity == 0);
                        $hasIncrease = $logs->contains(fn($l) => $l->new_quantity > $l->old_quantity);
                    @endphp

                    {{-- Summary Card --}}
                    <div class="summary-card">
                        <div class="summary-title">{{ translate('Edit Summary') }}</div>
                        <div class="summary-stats">
                            <div class="summary-stat">
                                <div class="stat-value">{{ $logs->count() }}</div>
                                <div class="stat-label">{{ translate('Total Edits') }}</div>
                            </div>
                            <div class="summary-stat">
                                <div class="stat-value">{{ $totalReturned }}</div>
                                <div class="stat-label">{{ translate('Items Returned') }}</div>
                            </div>
                            <div class="summary-stat">
                                <div class="stat-value">₹{{ number_format(abs($totalPriceReduction), 2) }}</div>
                                <div class="stat-label">{{ $totalPriceReduction >= 0 ? translate('Price Reduction') : translate('Price Increase') }}</div>
                            </div>
                            <div class="summary-stat">
                                <div class="stat-value">
                                    @if($hasFull && !$hasPartial)
                                        <span class="badge badge-danger">{{ translate('Full') }}</span>
                                    @elseif($hasPartial)
                                        <span class="badge badge-warning">{{ translate('Partial') }}</span>
                                    @elseif($hasIncrease)
                                        <span class="badge badge-success">{{ translate('Increase') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ translate('Mixed') }}</span>
                                    @endif
                                </div>
                                <div class="stat-label">{{ translate('Return Type') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Logs List --}}
                    @foreach ($logs as $log)
                        @php
                            $qtyDiff = $log->new_quantity - $log->old_quantity;
                            $priceDiff = $log->new_price - $log->old_price;
                            $isIncrease = $qtyDiff > 0;
                            $isDecrease = $qtyDiff < 0;
                            $isFullReturn = $log->new_quantity == 0;
                            $isPartialReturn = $isDecrease && !$isFullReturn;
                            
                            // Determine action type
                            if ($isFullReturn) {
                                $actionClass = 'full-return';
                                $actionLabel = translate('Full Return');
                            } elseif ($isPartialReturn) {
                                $actionClass = 'partial-return';
                                $actionLabel = translate('Partial Return');
                            } elseif ($isIncrease) {
                                $actionClass = 'increase';
                                $actionLabel = translate('Qty Increased');
                            } elseif ($isDecrease) {
                                $actionClass = 'decrease';
                                $actionLabel = translate('Qty Decreased');
                            } else {
                                $actionClass = 'adjustment';
                                $actionLabel = translate('Price Adjustment');
                            }
                            
                            // Use model's action if available
                            if ($log->action) {
                                $actionLabel = $log->action_label ?? $actionLabel;
                            }
                        @endphp
                        
                        <div class="edit-log-card">
                            <div class="edit-log-header">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <span class="log-action-badge {{ $actionClass }}">
                                        @if($isIncrease)
                                            <i class="tio-trending-up mr-1"></i>
                                        @elseif($isFullReturn)
                                            <i class="tio-clear-circle mr-1"></i>
                                        @elseif($isPartialReturn || $isDecrease)
                                            <i class="tio-trending-down mr-1"></i>
                                        @else
                                            <i class="tio-edit mr-1"></i>
                                        @endif
                                        {{ $actionLabel }}
                                    </span>
                                    <span class="timeline-date">
                                        <i class="tio-time"></i>
                                        {{ $log->created_at->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                                <div class="editor-badge">
                                    <i class="tio-user-outlined"></i>
                                    @if($log->deliveryMan)
                                        {{ $log->deliveryMan->f_name }} {{ $log->deliveryMan->l_name }}
                                        <small class="text-muted">({{ translate('Delivery') }})</small>
                                    @elseif($log->edited_by_type && $log->edited_by_id)
                                        {{ $log->editor_name ?? translate('Admin') }}
                                        <small class="text-muted">({{ $log->editor_type_label ?? $log->edited_by_type }})</small>
                                    @else
                                        {{ translate('System') }}
                                    @endif
                                </div>
                            </div>
                            
                            <div class="edit-log-body">
                                <div class="info-grid">
                                    {{-- Product Info --}}
                                    <div class="info-item">
                                        <span class="label">{{ translate('Product') }}</span>
                                        <span class="value">
                                            @if($log->orderDetail && $log->orderDetail->product)
                                                {{ $log->orderDetail->product->name }}
                                            @else
                                                {{ translate('Product') }} #{{ $log->order_detail_id }}
                                            @endif
                                        </span>
                                    </div>
                                    
                                    {{-- Quantity Change --}}
                                    <div class="info-item">
                                        <span class="label">{{ translate('Quantity Change') }}</span>
                                        <span class="value">
                                            <div class="qty-change-box">
                                                <span class="old-val">{{ $log->old_quantity }}</span>
                                                <span class="arrow">→</span>
                                                <span class="new-val">{{ $log->new_quantity }}</span>
                                                @if($qtyDiff != 0)
                                                    <span class="badge {{ $qtyDiff > 0 ? 'badge-soft-success' : 'badge-soft-danger' }} ml-2">
                                                        {{ $qtyDiff > 0 ? '+' : '' }}{{ $qtyDiff }}
                                                    </span>
                                                @endif
                                            </div>
                                        </span>
                                    </div>
                                    
                                    {{-- Price Change --}}
                                    <div class="info-item">
                                        <span class="label">{{ translate('Price Change') }}</span>
                                        <span class="value">
                                            <div class="price-change-box">
                                                <span class="old-price">₹{{ number_format($log->old_price, 2) }}</span>
                                                <span class="arrow">→</span>
                                                <span class="new-price">₹{{ number_format($log->new_price, 2) }}</span>
                                                @if($priceDiff != 0)
                                                    <span class="diff {{ $priceDiff > 0 ? 'positive' : 'negative' }}">
                                                        {{ $priceDiff > 0 ? '+' : '-' }}₹{{ number_format(abs($priceDiff), 2) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </span>
                                    </div>
                                    
                                    {{-- Unit Price (if available) --}}
                                    @if($log->unit_price > 0)
                                        <div class="info-item">
                                            <span class="label">{{ translate('Unit Price') }}</span>
                                            <span class="value">₹{{ number_format($log->unit_price, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    {{-- Photo --}}
                                    @if($log->photo)
                                        <div class="info-item">
                                            <span class="label">{{ translate('Evidence Photo') }}</span>
                                            <span class="value">
                                                <a href="{{ asset('storage/' . $log->photo) }}" 
                                                   target="_blank" 
                                                   data-lightbox="log-{{ $log->id }}"
                                                   data-title="{{ $log->reason }}">
                                                    <img src="{{ asset('storage/' . $log->photo) }}" 
                                                         class="log-photo-thumb"
                                                         alt="{{ translate('Evidence') }}">
                                                </a>
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Reason --}}
                                @if($log->reason)
                                    <div class="reason-box">
                                        <div class="reason-label">
                                            <i class="tio-info-outlined mr-1"></i>{{ translate('Reason') }}
                                        </div>
                                        <div class="reason-text">{{ $log->reason }}</div>
                                    </div>
                                @endif
                                
                                {{-- Notes (if available) --}}
                                @if($log->notes)
                                    <div class="mt-2 small text-muted">
                                        <i class="tio-document-text mr-1"></i>
                                        {{ $log->notes }}
                                    </div>
                                @endif
                                
                                {{-- Order Amount Changes (if available) --}}
                                @if($log->order_amount_before || $log->order_amount_after)
                                    <div class="order-amount-summary mt-3">
                                        <div class="small text-muted mb-2">{{ translate('Order Amount Impact') }}</div>
                                        <div class="d-flex justify-content-between">
                                            <span>{{ translate('Before') }}: <strong>₹{{ number_format($log->order_amount_before ?? 0, 2) }}</strong></span>
                                            <span class="text-muted">→</span>
                                            <span>{{ translate('After') }}: <strong>₹{{ number_format($log->order_amount_after ?? 0, 2) }}</strong></span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Final Order Amount Summary --}}
                    @if($logs->count() > 0)
                        <div class="order-amount-summary">
                            <h6 class="mb-3">
                                <i class="tio-chart-pie-1 mr-2 text-primary"></i>
                                {{ translate('Final Impact on Order') }}
                            </h6>
                            @php
                                $firstLog = $logs->sortBy('created_at')->first();
                                $lastLog = $logs->sortByDesc('created_at')->first();
                                $initialOrderAmount = $firstLog->order_amount_before ?? $order->order_amount;
                                $finalOrderAmount = $lastLog->order_amount_after ?? ($order->order_amount - $totalPriceReduction);
                            @endphp
                            <div class="amount-row">
                                <span>{{ translate('Original Items Price') }}</span>
                                <span>₹{{ number_format($logs->sum('old_price'), 2) }}</span>
                            </div>
                            <div class="amount-row">
                                <span>{{ translate('Current Items Price') }}</span>
                                <span>₹{{ number_format($logs->sum('new_price'), 2) }}</span>
                            </div>
                            <div class="amount-row">
                                <span>{{ translate('Total Adjustment') }}</span>
                                <span class="{{ $totalPriceReduction >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $totalPriceReduction >= 0 ? '-' : '+' }}₹{{ number_format(abs($totalPriceReduction), 2) }}
                                </span>
                            </div>
                        </div>
                    @endif

                @else
                    <div class="empty-state">
                        <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                        <p>{{ translate('No edit logs found for this order') }}</p>
                        <small class="text-muted">{{ translate('Any quantity changes, returns, or adjustments will appear here') }}</small>
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 pt-0">
                @if($order->editLogs->count() > 0)
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="tio-print mr-1"></i>{{ translate('Print Logs') }}
                    </button>
                @endif
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">
                    {{ translate('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
