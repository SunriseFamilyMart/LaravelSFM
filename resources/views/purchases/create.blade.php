@extends('inventory.layouts.app')

@section('content')
    <div class="container my-5">
        <div class="card rounded shadow-sm">
            <div class="card-body p-4">

                <h4 class="mb-4 fw-bold">➕ Add New Purchase</h4>

                {{-- Success / Errors --}}
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('inventory.purchases.store') }}" method="POST" enctype="multipart/form-data"
                    id="purchaseForm">
                    @csrf

                    {{-- Top row: Invoice + Supplier --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Invoice Number</label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number') }}"
                                class="form-control" placeholder="" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" id="supplier_id" class="form-select" required>
                                <option value="">Select supplier</option>

                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} — {{ $supplier->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Product table header --}}
                    <div class="row fw-semibold mb-2">
                        <div class="col-3">Product</div>
                        <div class="col-3">Description</div>
                        <div class="col-1">Quantity</div>
                        <div class="col-1">Rate</div>
                        <div class="col-1">GST %</div>
                        <div class="col-1">Amount</div>
                        <div class="col-2"></div>
                    </div>

                    {{-- Product rows container --}}
                    <div id="productRows">
                        {{-- default 4 rows as in design; use old() if validation failed --}}
                        @php
                            $oldProducts = old('product_id', []);
                            $rows = max(1, count($oldProducts) ?: 1);
                        @endphp

                        @for ($i = 0; $i < $rows; $i++)
                            <div class="row g-3 align-items-center product-row mb-2">
                                <div class="col-3">
                                    <select name="product_id[]" class="form-select product-select" style="width:100%;">
                                        <option value="">Select product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ old('product_id.' . $i) == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-3">
                                    <input type="text" name="description[]" value="{{ old('description.' . $i) }}"
                                        class="form-control" placeholder="">
                                </div>

                                <div class="col-1">
                                    <input type="number" min="0" name="quantity[]"
                                        value="{{ old('quantity.' . $i) }}" class="form-control quantity">
                                </div>

                                <div class="col-1">
                                    <input type="number" min="0" step="0.01" name="price[]"
                                        value="{{ old('price.' . $i) }}" class="form-control price">
                                </div>

                                <div class="col-1">
                                    <input type="number" min="0" step="0.01" name="gst[]"
                                        value="{{ old('gst.' . $i) ?? 0 }}" class="form-control gst">
                                </div>

                                <div class="col-1">
                                    <input type="text" name="amount[]" value="{{ old('amount.' . $i) }}" readonly
                                        class="form-control amount">
                                </div>

                                <div class="col-2 text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm add-row">+</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row">−</button>
                                </div>
                            </div>
                        @endfor
                    </div>

                    {{-- Add product link (left) + Total (right) --}}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <button type="button" class="btn btn-link p-0 fw-semibold" id="addProductBtn">+ Add
                                Product</button>
                        </div>

                        <div class="d-flex align-items-center">
                            <label class="me-2 fw-semibold mb-0">Total:</label>
                            <input type="text" readonly name="total" id="total" class="form-control"
                                style="width:140px;" value="{{ old('total') }}">
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Invoice upload --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invoice File (optional)</label>
                        <input type="file" name="invoice" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('inventory.purchases.index') }}" class="btn btn-secondary">⬅ Back to List</a>
                        <button type="submit" class="btn btn-dark px-4">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Select2 CSS/JS & jQuery (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {
            function initSelect2(row) {
                row.find('.product-select').select2({
                    placeholder: 'Select product',
                    allowClear: true,
                    width: 'resolve'
                });
            }

            // init all existing selects
            $('.product-select').each(function() {
                initSelect2($(this).closest('.product-row'));
            });

            // calc function for a single row
            function calcRow(row) {
                let qty = parseFloat(row.find('.quantity').val()) || 0;
                let price = parseFloat(row.find('.price').val()) || 0;
                let gst = parseFloat(row.find('.gst').val()) || 0;

                let amount = qty * price;
                let gstAmount = (amount * gst) / 100;
                let final = amount + gstAmount;

                row.find('.amount').val(final ? final.toFixed(2) : '');
            }

            // update total
            function updateTotal() {
                let total = 0;
                $('.amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#total').val(total ? total.toFixed(2) : '');
            }

            // on input change -> calc row + total
            $(document).on('input change', '.quantity, .price, .gst', function() {
                let row = $(this).closest('.product-row');
                calcRow(row);
                updateTotal();
            });

            // add new row (from Add Product button)
            $('#addProductBtn').on('click', function() {
                addRow();
            });

            // add row helper (also used by the + on each row)
            function addRow(afterRow) {
                let index = $('.product-row').length;
                let template = `
            <div class="row g-3 align-items-center product-row mb-2">
                <div class="col-3">
                    <select name="product_id[]" class="form-select product-select" style="width:100%;">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ addslashes($product->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-3">
                    <input type="text" name="description[]" class="form-control">
                </div>
                <div class="col-1">
                    <input type="number" min="0" name="quantity[]" class="form-control quantity">
                </div>
                <div class="col-1">
                    <input type="number" min="0" step="0.01" name="price[]" class="form-control price">
                </div>
                <div class="col-1">
                    <input type="number" min="0" step="0.01" name="gst[]" class="form-control gst" value="0">
                </div>
                <div class="col-1">
                    <input type="text" name="amount[]" readonly class="form-control amount">
                </div>
                <div class="col-2 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm add-row">+</button>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-row">−</button>
                </div>
            </div>
        `;
                if (afterRow) {
                    $(afterRow).after(template);
                    let newRow = $(afterRow).next();
                    initSelect2(newRow);
                } else {
                    $('#productRows').append(template);
                    initSelect2($('#productRows').children().last());
                }
            }

            // click + on row -> insert below
            $(document).on('click', '.add-row', function() {
                let row = $(this).closest('.product-row');
                addRow(row);
            });

            // remove row
            $(document).on('click', '.remove-row', function() {
                if ($('.product-row').length <= 1) return; // always keep at least one
                $(this).closest('.product-row').remove();
                updateTotal();
            });

            // initial calc for old values
            $('.product-row').each(function() {
                calcRow($(this));
            });
            updateTotal();

            // form submit: ensure supplier is selected and at least one product id present
            $('#purchaseForm').on('submit', function(e) {
                if (!$('#supplier_id').val()) {
                    alert('Please select a supplier.');
                    e.preventDefault();
                    return false;
                }
                // ensure at least one product selected & qty > 0
                let valid = false;
                $('select[name="product_id[]"]').each(function() {
                    let id = $(this).val();
                    if (id) valid = true;
                });
                if (!valid) {
                    alert('Please select at least one product.');
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endsection
