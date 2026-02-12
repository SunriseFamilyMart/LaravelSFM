@extends('layouts.admin.app')

@section('title', 'GST Credit Note')

@section('content')
<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h3>GST Credit Note - {{ $creditNote->credit_note_no }}</h3>
        <a href="{{ route('admin.credit-note.pdf', $creditNote->id) }}"
           class="btn btn-primary">
            <i class="tio-download"></i> Download PDF
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Header Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Credit Note No:</strong> {{ $creditNote->credit_note_no }}</p>
                    <p><strong>Date:</strong> {{ $creditNote->created_at->format('d M Y h:i A') }}</p>
                    <p><strong>Original Order:</strong> #{{ $creditNote->order->id }}</p>
                    <p><strong>Reason:</strong> {{ ucfirst(str_replace('_', ' ', $creditNote->reason)) }}</p>
                </div>
            </div>

            <!-- Bill From / Bill To -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Bill From:</h5>
                    <p>
                        <strong>{{ $business_name ?? 'Sunrise Family Mart' }}</strong><br>
                        {{ $business_address ?? 'Bangalore, Karnataka' }}<br>
                        Phone: {{ $business_phone ?? '9999999999' }}<br>
                        Email: {{ $business_email ?? 'admin@sunrisefamilymart.com' }}<br>
                        GSTIN: {{ $business_gst ?? '29ABCDE1234F1Z5' }}
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Bill To (Store):</h5>
                    <p>
                        @if($creditNote->order->store)
                            <strong>{{ $creditNote->order->store->store_name ?? '-' }}</strong><br>
                            {{ $creditNote->order->store->address ?? '-' }}<br>
                            Phone: {{ $creditNote->order->store->phone_number ?? '-' }}<br>
                            GSTIN: {{ $creditNote->order->store->gst_number ?? '-' }}
                        @else
                            <strong>{{ $creditNote->order->customer->f_name ?? '-' }} {{ $creditNote->order->customer->l_name ?? '' }}</strong><br>
                            Branch: {{ $creditNote->order->branch->name ?? '-' }}
                        @endif
                    </p>
                </div>
            </div>

            <!-- Items Table -->
            <h5 class="mb-3">Items</h5>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Item</th>
                        <th>HSN</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Tax %</th>
                        <th>CGST</th>
                        <th>SGST</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNote->items as $item)
                        @php
                            $taxableAmount = $item->price * $item->quantity;
                            $gstAmount = $item->gst_amount ?? 0;
                            $cgst = $gstAmount / 2;
                            $sgst = $gstAmount / 2;
                            $total = $taxableAmount + $gstAmount;
                        @endphp
                        <tr>
                            <td>{{ $item->product?->name ?? 'Item' }}</td>
                            <td>{{ $item->product?->hsn_code ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format($item->price, 2) }}</td>
                            <td>{{ $item->gst_percent ?? 5 }}%</td>
                            <td>₹{{ number_format($cgst, 2) }}</td>
                            <td>₹{{ number_format($sgst, 2) }}</td>
                            <td>₹{{ number_format($total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="row">
                <div class="col-md-6 ml-auto">
                    <table class="table">
                        <tr>
                            <td class="text-right"><strong>Subtotal (Taxable):</strong></td>
                            <td class="text-right">₹{{ number_format($creditNote->taxable_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>CGST:</strong></td>
                            <td class="text-right">₹{{ number_format($creditNote->gst_amount / 2, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>SGST:</strong></td>
                            <td class="text-right">₹{{ number_format($creditNote->gst_amount / 2, 2) }}</td>
                        </tr>
                        <tr class="table-active">
                            <td class="text-right"><strong>Grand Total:</strong></td>
                            <td class="text-right"><strong>₹{{ number_format($creditNote->total_amount, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Bank Details -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Bank Details</h5>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Bank Name:</strong></td>
                            <td>{{ $business_bank_name ?? 'HDFC Bank' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Account Holder:</strong></td>
                            <td>{{ $business_name ?? 'Sunrise Family Mart' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Account No:</strong></td>
                            <td>{{ $business_bank_account ?? '50200058934605' }}</td>
                        </tr>
                        <tr>
                            <td><strong>IFSC:</strong></td>
                            <td>{{ $business_bank_ifsc ?? 'HDFC0001753' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Branch:</strong></td>
                            <td>{{ $business_bank_branch ?? 'Kanakpura Road' }}</td>
                        </tr>
                        <tr>
                            <td><strong>UPI ID:</strong></td>
                            <td>{{ $business_upi ?? 'paytmqr5jjsna@ptys' }}</td>
                        </tr>
                    </table>
                </div>
                @if (!empty($paytm_qr_code))
                <div class="col-md-6 text-center">
                    <h5 class="mb-3">Scan & Pay</h5>
                    <img src="{{ $paytm_qr_code }}" style="width:200px; height:auto; border:1px solid #ddd; padding:10px;">
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

