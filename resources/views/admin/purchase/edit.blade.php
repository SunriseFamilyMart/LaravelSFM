@extends('layouts.admin.app')

@section('content')
<div class="container">
     <br/> <br/>
    <h4 class="mb-3">Edit Purchase</h4>

    <form method="POST" action="{{ route('admin.purchase.update', $purchase->id) }}">
        @csrf

        <div class="row">

            {{-- Invoice Number --}}
            <div class="col-md-3 mb-3">
                <label>Invoice Number</label>
                <input type="text"
                       name="invoice_number"
                       value="{{ $purchase->invoice_number }}"
                       class="form-control"
                       required>
            </div>

            {{-- Expected Delivery Date --}}
            <div class="col-md-3 mb-3">
                <label>Expected Delivery Date</label>
                <input type="date"
                       name="expected_delivery_date"
                       value="{{ $purchase->expected_delivery_date }}"
                       class="form-control"
                       required>
            </div>

            {{-- Paid Amount --}}
            <div class="col-md-3 mb-3">
                <label>Paid Amount</label>
                <input type="number"
                       step="0.01"
                       name="paid_amount"
                       value="{{ $purchase->paid_amount }}"
                       class="form-control"
                       required>
            </div>

            {{-- Status --}}
            <div class="col-md-3 mb-3">
                <label>Status</label>
                <select name="status" class="form-control" required>
                    @foreach(['Pending','In Progress','Delivered','Delayed'] as $s)
                        <option value="{{ $s }}"
                            {{ $purchase->status === $s ? 'selected' : '' }}>
                            {{ $s }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Mode --}}
            <div class="col-md-3 mb-3">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-control" required>
                    <option value="">-- Select --</option>
                    @foreach(['Cash','UPI','Bank Transfer'] as $mode)
                        <option value="{{ $mode }}"
                            {{ $purchase->payment_mode === $mode ? 'selected' : '' }}>
                            {{ $mode }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        <button class="btn btn-success">Update</button>
        <a href="{{ route('admin.purchase.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
