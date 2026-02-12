@extends('layouts.admin.app')

@section('title', translate('Edit Purchase'))

@push('css_or_js')
    <style>
        .item-row {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <i class="tio-edit"></i>
                </span>
                <span>{{ translate('Edit Purchase') }} - {{ $purchase->pr_number }}</span>
            </h1>
        </div>

        <form action="{{ route('admin.purchase.update', $purchase->id) }}" method="POST" id="purchaseForm">
            @csrf
            @method('PUT')
            
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">{{ translate('Purchase Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Supplier') }} <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-control" required>
                                    <option value="">{{ translate('Select Supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ $purchase->supplier_id == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Purchased By') }} <span class="text-danger">*</span></label>
                                <input type="text" name="purchased_by" class="form-control" value="{{ $purchase->purchased_by }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Purchase Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="purchase_date" class="form-control" value="{{ $purchase->purchase_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Expected Delivery Date') }}</label>
                                <input type="date" name="expected_delivery_date" class="form-control" value="{{ $purchase->expected_delivery_date ? $purchase->expected_delivery_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Invoice Number') }}</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ $purchase->invoice_number }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ translate('Notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2">{{ $purchase->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">{{ translate('Purchase Items') }}</h5>
                    <button type="button" class="btn btn-sm btn-success float-right" onclick="addItem()">
                        <i class="tio-add"></i> {{ translate('Add Item') }}
                    </button>
                </div>
                <div class="card-body">
                    <div id="items-container">
                        <!-- Items will be added here -->
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <table class="table">
                                <tr>
                                    <th>{{ translate('Subtotal') }}</th>
                                    <td class="text-right">₹<span id="subtotal">0.00</span></td>
                                </tr>
                                <tr>
                                    <th>{{ translate('GST Amount') }}</th>
                                    <td class="text-right">₹<span id="gst_total">0.00</span></td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <th>{{ translate('Grand Total') }}</th>
                                    <td class="text-right">₹<span id="grand_total">0.00</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-save"></i> {{ translate('Update Purchase') }}
                    </button>
                    <a href="{{ route('admin.purchase.show', $purchase->id) }}" class="btn btn-secondary">
                        {{ translate('Cancel') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script')
<script>
    let itemCount = 0;
    const products = @json($products);
    const existingItems = @json($purchase->items);

    function addItem(itemData = null) {
        itemCount++;
        const productId = itemData ? itemData.product_id : '';
        const quantity = itemData ? itemData.quantity : 1;
        const unitPrice = itemData ? itemData.unit_price : '';
        const gstPercent = itemData ? itemData.gst_percent : 0;
        
        const html = `
            <div class="item-row" id="item-${itemCount}">
                <div class="row">
                    <div class="col-md-4">
                        <label>${translate('Product')} <span class="text-danger">*</span></label>
                        <select name="items[${itemCount}][product_id]" class="form-control product-select" data-index="${itemCount}" required onchange="updatePrice(${itemCount})">
                            <option value="">{{ translate('Select Product') }}</option>
                            ${products.map(p => `<option value="${p.id}" data-price="${p.price || 0}" ${p.id == productId ? 'selected' : ''}>${p.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>${translate('Quantity')} <span class="text-danger">*</span></label>
                        <input type="number" name="items[${itemCount}][quantity]" class="form-control" min="1" value="${quantity}" required onchange="calculateLineTotal(${itemCount})">
                    </div>
                    <div class="col-md-2">
                        <label>${translate('Unit Price')} <span class="text-danger">*</span></label>
                        <input type="number" name="items[${itemCount}][unit_price]" id="unit_price_${itemCount}" class="form-control" step="0.01" min="0" value="${unitPrice}" required onchange="calculateLineTotal(${itemCount})">
                    </div>
                    <div class="col-md-2">
                        <label>${translate('GST %')}</label>
                        <input type="number" name="items[${itemCount}][gst_percent]" class="form-control" step="0.01" min="0" max="100" value="${gstPercent}" onchange="calculateLineTotal(${itemCount})">
                    </div>
                    <div class="col-md-1">
                        <label>${translate('Total')}</label>
                        <input type="text" id="line_total_${itemCount}" class="form-control" readonly value="0.00">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-block" onclick="removeItem(${itemCount})">
                            <i class="tio-delete"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('items-container').insertAdjacentHTML('beforeend', html);
        
        if (itemData) {
            calculateLineTotal(itemCount);
        }
    }

    function removeItem(index) {
        document.getElementById(`item-${index}`).remove();
        calculateGrandTotal();
    }

    function updatePrice(index) {
        const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        document.getElementById(`unit_price_${index}`).value = price;
        calculateLineTotal(index);
    }

    function calculateLineTotal(index) {
        const quantity = parseFloat(document.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
        const unitPrice = parseFloat(document.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
        const gstPercent = parseFloat(document.querySelector(`input[name="items[${index}][gst_percent]"]`).value) || 0;
        
        const subtotal = quantity * unitPrice;
        const gstAmount = (subtotal * gstPercent) / 100;
        const total = subtotal + gstAmount;
        
        document.getElementById(`line_total_${index}`).value = total.toFixed(2);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        let gstTotal = 0;
        
        for (let i = 1; i <= itemCount; i++) {
            const itemDiv = document.getElementById(`item-${i}`);
            if (itemDiv) {
                const quantity = parseFloat(document.querySelector(`input[name="items[${i}][quantity]"]`)?.value) || 0;
                const unitPrice = parseFloat(document.querySelector(`input[name="items[${i}][unit_price]"]`)?.value) || 0;
                const gstPercent = parseFloat(document.querySelector(`input[name="items[${i}][gst_percent]"]`)?.value) || 0;
                
                const lineSubtotal = quantity * unitPrice;
                const lineGst = (lineSubtotal * gstPercent) / 100;
                
                subtotal += lineSubtotal;
                gstTotal += lineGst;
            }
        }
        
        const grandTotal = subtotal + gstTotal;
        
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('gst_total').textContent = gstTotal.toFixed(2);
        document.getElementById('grand_total').textContent = grandTotal.toFixed(2);
    }

    function translate(key) {
        return key; // In real implementation, this would translate
    }

    // Load existing items on page load
    document.addEventListener('DOMContentLoaded', function() {
        existingItems.forEach(item => {
            addItem(item);
        });
    });

    // Form validation
    document.getElementById('purchaseForm').addEventListener('submit', function(e) {
        if (itemCount === 0 || document.querySelectorAll('.item-row').length === 0) {
            e.preventDefault();
            alert('{{ translate("Please add at least one item") }}');
            return false;
        }
    });
</script>
@endpush
