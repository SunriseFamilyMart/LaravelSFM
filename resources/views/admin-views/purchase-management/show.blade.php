@extends('layouts.admin.app')

@section('title', translate('Purchase Details'))

@push('css_or_js')
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-draft { background-color: #f0f0f0; color: #666; }
        .status-ordered { background-color: #e3f2fd; color: #1976d2; }
        .status-partial_delivered { background-color: #fff3e0; color: #f57c00; }
        .status-delivered { background-color: #e8f5e9; color: #388e3c; }
        .status-cancelled { background-color: #ffebee; color: #d32f2f; }
        .status-delayed { background-color: #fce4ec; color: #c2185b; }
        
        .payment-unpaid { background-color: #ffebee; color: #d32f2f; }
        .payment-partial { background-color: #fff3e0; color: #f57c00; }
        .payment-paid { background-color: #e8f5e9; color: #388e3c; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="mb-0 page-header-title">
                        <span>{{ $purchase->pr_number }}</span>
                        <span class="status-badge status-{{ $purchase->status }} ml-2">
                            {{ translate(ucfirst(str_replace('_', ' ', $purchase->status))) }}
                        </span>
                    </h1>
                </div>
                <div class="col-sm-auto">
                    @if(in_array($purchase->status, ['draft', 'ordered']))
                        <a href="{{ route('admin.purchase.edit', $purchase->id) }}" class="btn btn-primary">
                            <i class="tio-edit"></i> {{ translate('Edit') }}
                        </a>
                    @endif
                    <a href="{{ route('admin.purchase.index') }}" class="btn btn-secondary">
                        {{ translate('Back to List') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Purchase Info -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">{{ translate('Purchase Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">{{ translate('PR Number') }}</th>
                                <td>{{ $purchase->pr_number }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Supplier') }}</th>
                                <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Purchased By') }}</th>
                                <td>{{ $purchase->purchased_by }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Purchase Date') }}</th>
                                <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Expected Delivery Date') }}</th>
                                <td>{{ $purchase->expected_delivery_date ? $purchase->expected_delivery_date->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Invoice Number') }}</th>
                                <td>{{ $purchase->invoice_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Notes') }}</th>
                                <td>{{ $purchase->notes ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">{{ translate('Payment Summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th>{{ translate('Subtotal') }}</th>
                                <td class="text-right">₹{{ number_format($purchase->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('GST') }}</th>
                                <td class="text-right">₹{{ number_format($purchase->gst_amount, 2) }}</td>
                            </tr>
                            <tr class="font-weight-bold">
                                <th>{{ translate('Total') }}</th>
                                <td class="text-right">₹{{ number_format($purchase->total_amount, 2) }}</td>
                            </tr>
                            <tr class="text-success">
                                <th>{{ translate('Paid') }}</th>
                                <td class="text-right">₹{{ number_format($purchase->paid_amount, 2) }}</td>
                            </tr>
                            <tr class="text-danger">
                                <th>{{ translate('Balance') }}</th>
                                <td class="text-right">₹{{ number_format($purchase->balance_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>{{ translate('Payment Status') }}</th>
                                <td class="text-right">
                                    <span class="status-badge payment-{{ $purchase->payment_status }}">
                                        {{ translate(ucfirst($purchase->payment_status)) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Status Change -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">{{ translate('Change Status') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.purchase.update-status', $purchase->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <select name="status" class="form-control" required>
                                    <option value="draft" {{ $purchase->status == 'draft' ? 'selected' : '' }}>{{ translate('Draft') }}</option>
                                    <option value="ordered" {{ $purchase->status == 'ordered' ? 'selected' : '' }}>{{ translate('Ordered') }}</option>
                                    <option value="partial_delivered" {{ $purchase->status == 'partial_delivered' ? 'selected' : '' }}>{{ translate('Partial Delivered') }}</option>
                                    <option value="delivered" {{ $purchase->status == 'delivered' ? 'selected' : '' }}>{{ translate('Delivered') }}</option>
                                    <option value="delayed" {{ $purchase->status == 'delayed' ? 'selected' : '' }}>{{ translate('Delayed') }}</option>
                                    <option value="cancelled" {{ $purchase->status == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="reason" class="form-control" placeholder="{{ translate('Reason (optional)') }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">{{ translate('Update Status') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Purchase Items') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ translate('Product') }}</th>
                                <th>{{ translate('Qty Ordered') }}</th>
                                <th>{{ translate('Qty Received') }}</th>
                                <th>{{ translate('Qty Pending') }}</th>
                                <th>{{ translate('Unit Price') }}</th>
                                <th>{{ translate('GST %') }}</th>
                                <th>{{ translate('GST Amount') }}</th>
                                <th>{{ translate('Total') }}</th>
                                <th>{{ translate('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-success">{{ $item->received_qty }}</td>
                                    <td class="text-danger">{{ $item->pending_qty }}</td>
                                    <td>₹{{ number_format($item->unit_price, 2) }}</td>
                                    <td>{{ $item->gst_percent }}%</td>
                                    <td>₹{{ number_format($item->gst_amount, 2) }}</td>
                                    <td>₹{{ number_format($item->total, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $item->status == 'received' ? 'success' : ($item->status == 'partial' ? 'warning' : 'secondary') }}">
                                            {{ translate(ucfirst($item->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Payment History') }}</h5>
                <button type="button" class="btn btn-sm btn-success float-right" data-toggle="modal" data-target="#addPaymentModal">
                    <i class="tio-add"></i> {{ translate('Add Payment') }}
                </button>
            </div>
            <div class="card-body">
                @if($purchase->payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Amount') }}</th>
                                    <th>{{ translate('Mode') }}</th>
                                    <th>{{ translate('Reference') }}</th>
                                    <th>{{ translate('Added By') }}</th>
                                    <th>{{ translate('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td>₹{{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ translate(ucfirst(str_replace('_', ' ', $payment->payment_mode))) }}</td>
                                        <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                                        <td>{{ $payment->creator->f_name ?? 'N/A' }} {{ $payment->creator->l_name ?? '' }}</td>
                                        <td>{{ $payment->notes ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">{{ translate('No payments recorded yet') }}</p>
                @endif
            </div>
        </div>

        <!-- Delivery History -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Delivery History') }}</h5>
                <button type="button" class="btn btn-sm btn-success float-right" data-toggle="modal" data-target="#recordDeliveryModal">
                    <i class="tio-add"></i> {{ translate('Record Delivery') }}
                </button>
            </div>
            <div class="card-body">
                @if($purchase->deliveries->count() > 0)
                    @foreach($purchase->deliveries as $delivery)
                        <div class="border p-3 mb-3">
                            <h6>{{ translate('Delivery on') }} {{ $delivery->delivery_date->format('d M Y') }}</h6>
                            <p class="mb-2">
                                <strong>{{ translate('Received By') }}:</strong> {{ $delivery->received_by ?? 'N/A' }}<br>
                                <strong>{{ translate('Notes') }}:</strong> {{ $delivery->notes ?? 'N/A' }}
                            </p>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Product') }}</th>
                                        <th>{{ translate('Quantity Received') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($delivery->items as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? 'N/A' }}</td>
                                            <td>{{ $item->quantity_received }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">{{ translate('No deliveries recorded yet') }}</p>
                @endif
            </div>
        </div>

        <!-- Audit Log -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Audit Log') }}</h5>
            </div>
            <div class="card-body">
                @if($auditLogs->count() > 0)
                    <div class="timeline">
                        @foreach($auditLogs as $log)
                            <div class="mb-3">
                                <strong>{{ $log->action }}</strong> 
                                by User #{{ $log->user_id }}
                                on {{ $log->created_at->format('d M Y H:i:s') }}
                                <br>
                                <small class="text-muted">{{ $log->ip_address }}</small>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">{{ translate('No audit logs available') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.purchase.add-payment', $purchase->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Add Payment') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ translate('Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" max="{{ $purchase->balance_amount }}" required>
                            <small class="text-muted">{{ translate('Balance') }}: ₹{{ number_format($purchase->balance_amount, 2) }}</small>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Payment Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Payment Mode') }} <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="cash">{{ translate('Cash') }}</option>
                                <option value="bank_transfer">{{ translate('Bank Transfer') }}</option>
                                <option value="upi">{{ translate('UPI') }}</option>
                                <option value="cheque">{{ translate('Cheque') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Reference Number') }}</label>
                            <input type="text" name="reference_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Add Payment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record Delivery Modal -->
    <div class="modal fade" id="recordDeliveryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.purchase.record-delivery', $purchase->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Record Delivery') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ translate('Delivery Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Received By') }}</label>
                            <input type="text" name="received_by" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Items Received') }}</label>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Product') }}</th>
                                        <th>{{ translate('Pending Qty') }}</th>
                                        <th>{{ translate('Qty Received') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchase->items as $index => $item)
                                        @if($item->pending_qty > 0)
                                            <tr>
                                                <td>
                                                    {{ $item->product->name ?? 'N/A' }}
                                                    <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $item->id }}">
                                                </td>
                                                <td>{{ $item->pending_qty }}</td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][quantity_received]" 
                                                           class="form-control" min="0" max="{{ $item->pending_qty }}" value="0">
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Record Delivery') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
