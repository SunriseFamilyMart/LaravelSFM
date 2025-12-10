@extends('layouts.admin.app')

@section('title','Create Order')

@section('content')
<div class="container-fluid">

<div class="card shadow">
<div class="card-header">
    <h4>Create New Order</h4>
</div>

<div class="card-body">
<form method="POST" action="{{ route('admin.orders.orders.store') }}">

@csrf

<script>
    // Pass all products to JS
    window.allProducts = @json($products);
</script>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">Supplier</label>
        <select class="form-control" name="supplier_id" id="supplier" required>
            <option value="">-- Select Supplier --</option>
            @foreach($suppliers as $s)
            <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<hr>

<div class="table-responsive mb-3">
<table class="table table-bordered" id="productTable">
    <thead>
        <tr class="text-center">
            <th>Product</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody></tbody>
</table>

<button type="button" class="btn btn-primary" onclick="addRow()">Add Product</button>
</div>

<hr>

{{-- Order Fields --}}
<div class="row">
    <div class="col-md-3">
        <label>Order Date</label>
        <input type="date" name="order_date" class="form-control" required>
    </div>

    <div class="col-md-3">
        <label>Expected Date</label>
        <input type="date" name="expected_date" class="form-control" >
    </div>

    <div class="col-md-3">
        <label>Delivery Date</label>
        <input type="date" name="delivery_date" class="form-control">
    </div>

    <div class="col-md-3">
        <label>Invoice No</label>
        <input type="text" name="invoice_no" class="form-control" >
    </div>
</div>

<br>

<div class="row">
    <div class="col-md-3">
        <label>Status</label>
        <select class="form-control" name="order_status" required>
            <option value="delivered">Delivered</option>
            <option value="processing">In Progress</option>
            <option value="pending">Pending</option>
            <option value="failed">Delayed</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Total</label>
        <input type="number" class="form-control" id="grandTotal" name="total" readonly>
    </div>

    <div class="col-md-3">
        <label>Paid</label>
        <input type="number" class="form-control" name="paid" id="paid">
    </div>

    <div class="col-md-3">
        <label>Balance</label>
        <input type="number" class="form-control" id="balance" readonly>
    </div>
    <div class="col-md-3">
    <label>Order User</label>
    <input type="text" name="order_user" class="form-control" placeholder="Enter User Name">
</div>

</div>

<br>

<div class="row">
    <div class="col-md-4">
        <label>Payment Mode</label>
        <select name="payment_mode" class="form-control" required>
            <option value="cash">Cash</option>
            <option value="upi">UPI</option>
            <option value="credit_sale">Credit Sale</option>
            <option value="other">Other</option>
        </select>
    </div>

    <div class="col-md-8">
        <label>Comment</label>
        <input type="text" name="comment" class="form-control">
    </div>
    
</div>

<hr>

<button class="btn btn-success mt-3">Save Order</button>

</form>
</div>
</div>

</div>

<script>
let rowIndex = 0;

// Add product row
function addRow() {
    const table = document.querySelector('#productTable tbody');

    let row = `
<tr>
<td>
<select class="form-control productSelect" name="products[${rowIndex}][product_id]" required>
    <option value="">-- Select Product --</option>
    ${window.allProducts.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
</select>
</td>

<td><input type="number" class="form-control price" name="products[${rowIndex}][price]" readonly></td>

<td><input type="number" class="form-control qty" name="products[${rowIndex}][qty]" min="1" value="1"></td>

<td><input type="number" class="form-control total" readonly></td>

<td><button class="btn btn-danger" onclick="this.closest('tr').remove(); calculateTotal();">X</button></td>
</tr>`;

    table.insertAdjacentHTML('beforeend', row);
    rowIndex++;
}

// Auto load product price
document.addEventListener('change', function (e) {

    if (e.target.classList.contains('productSelect')) {

        let productId = e.target.value;

        fetch('{{ url("admin/orders/product-price") }}/' + productId)
            .then(res => res.json())
            .then(product => {

                let row = e.target.closest('tr');

                row.querySelector('.price').value = product.price;

                calculateRow(row);
                calculateTotal();
            })
            .catch(err => console.error(err));
    }
});

// Calculate row total
function calculateRow(row) {
    let price = parseFloat(row.querySelector('.price').value) || 0;
    let qty = parseFloat(row.querySelector('.qty').value) || 0;
    row.querySelector('.total').value = (price * qty).toFixed(2);
}

// Calculate full total
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('qty')) {
        let row = e.target.closest('tr');
        calculateRow(row);
        calculateTotal();
    }
});

function calculateTotal() {
    let totals = [...document.querySelectorAll('.total')];
    let sum = totals.reduce((a, b) => a + (parseFloat(b.value) || 0), 0);
    document.getElementById('grandTotal').value = sum;

    let paid = parseFloat(document.getElementById('paid').value) || 0;
    document.getElementById('balance').value = (sum - paid).toFixed(2);
}

document.getElementById('paid').addEventListener('input', calculateTotal);
</script>

@endsection
