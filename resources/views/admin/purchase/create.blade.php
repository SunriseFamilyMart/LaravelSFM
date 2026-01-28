@extends('layouts.admin.app')

@section('content')
 <br/> <br/>
<div class="container">
    <h4>Create Purchase</h4>

    <form method="POST" action="{{ route('admin.purchase.store') }}">
        @csrf

        <div class="row">

            {{-- SUPPLIER --}}
            <div class="col-md-4 mb-3">
                <label>Supplier</label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">-- Select --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- PRODUCT --}}
       {{-- PRODUCT --}}
<div class="col-md-4 mb-3">
    <label>Product</label>
    <select name="product_id"
            id="productSelect"
            class="form-control"
            onchange="setProductDetails()"
            required>
        <option value="">-- Select --</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}"
                    data-price="{{ $product->price }}"
                    data-tax="{{ $product->tax }}">
                {{ $product->name }}
            </option>
        @endforeach
    </select>
</div>


            {{-- PURCHASED BY --}}
            <div class="col-md-4 mb-3">
                <label>Purchased By</label>
                <input type="text" name="purchased_by" class="form-control" required>
            </div>

            {{-- DATES --}}
            <div class="col-md-4 mb-3">
                <label>Purchase Date</label>
                <input type="date" name="purchase_date" class="form-control" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Expected Delivery</label>
                <input type="date" name="expected_delivery_date" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label>Actual Delivery</label>
                <input type="date" name="actual_delivery_date" class="form-control">
            </div>

            {{-- INVOICE --}}
            <div class="col-md-4 mb-3">
                <label>Invoice Number</label>
                <input type="text" name="invoice_number" class="form-control">
            </div>

            {{-- STATUS --}}
            <div class="col-md-4 mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option>Pending</option>
                    <option>In Progress</option>
                    <option>Delivered</option>
                    <option>Delayed</option>
                </select>
            </div>

            {{-- PRICE --}}
            <div class="col-md-4 mb-3">
                <label>Purchase Price</label>
              <input type="number" step="0.01"
       id="price"
       name="purchase_price"
       class="form-control"
       onkeyup="calculate()"
       required>

            </div>

            {{-- QTY --}}
            <div class="col-md-4 mb-3">
                <label>Quantity</label>
                <input type="number" id="qty" name="quantity"
                       class="form-control" onkeyup="calculate()" required>
            </div>

            {{-- MRP --}}
            <div class="col-md-4 mb-3">
                <label>MRP (Price + Tax)</label>
                <input type="number" step="0.01" id="mrp" class="form-control" readonly>
            </div>

            {{-- TOTAL --}}
            <div class="col-md-4 mb-3">
                <label>Total Amount</label>
                <input type="number" step="0.01" id="total" class="form-control" readonly>
            </div>

            {{-- PAID --}}
            <div class="col-md-4 mb-3">
                <label>Paid Amount</label>
                <input type="number" step="0.01" name="paid_amount" class="form-control">
            </div>

            {{-- PAYMENT MODE --}}
            <div class="col-md-4 mb-3">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-control">
                    <option value="">-- Select --</option>
                    <option>Cash</option>
                    <option>UPI</option>
                    <option>Bank Transfer</option>
                </select>
            </div>

            {{-- COMMENTS --}}
            <div class="col-md-12 mb-3">
                <label>Comments</label>
                <textarea name="comments" class="form-control"></textarea>
            </div>
        </div>

        <button class="btn btn-success">Save Purchase</button>
    </form>
</div>
@endsection
<script>
let tax = 0;

function setProductDetails() {
    const productSelect = document.getElementById('productSelect');
    const selected      = productSelect.options[productSelect.selectedIndex];

    const price = selected.getAttribute('data-price') || 0;
    tax         = selected.getAttribute('data-tax') || 0;

    // SET PRICE
    document.getElementById('price').value = price;

    calculate();
}

function calculate() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const qty   = parseFloat(document.getElementById('qty').value) || 0;

    const mrp   = price + (price * tax / 100);
    const total = price * qty;

    document.getElementById('mrp').value   = mrp.toFixed(2);
    document.getElementById('total').value = total.toFixed(2);
}
</script>
