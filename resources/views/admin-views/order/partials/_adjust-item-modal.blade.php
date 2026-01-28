{{-- Order Adjustment Modal --}}
<style>
    .adjust-modal .modal-dialog { max-width: 700px; }
    .adjust-form-group { margin-bottom: 20px; }
    .adjust-form-group label { font-weight: 600; color: #495057; margin-bottom: 8px; display: block; }
    .adjust-form-group .form-control { border-radius: 8px; border: 1px solid #ced4da; }
    .adjust-form-group .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
    .qty-input-group { display: flex; align-items: center; gap: 10px; }
    .qty-input-group .qty-btn { 
        width: 40px; height: 40px; border-radius: 8px; 
        border: 1px solid #dee2e6; background: #f8f9fa; 
        cursor: pointer; font-size: 18px; font-weight: 600;
        transition: all 0.2s;
    }
    .qty-input-group .qty-btn:hover { background: #e9ecef; }
    .qty-input-group input { 
        width: 80px; text-align: center; font-size: 18px; font-weight: 600;
        border-radius: 8px;
    }
    .adjust-preview { 
        background: #f8f9fa; border-radius: 12px; padding: 15px; 
        margin-top: 15px; border: 1px solid #e9ecef;
    }
    .adjust-preview .preview-row {
        display: flex; justify-content: space-between; 
        padding: 8px 0; border-bottom: 1px dashed #dee2e6;
    }
    .adjust-preview .preview-row:last-child { border-bottom: none; }
    .adjust-preview .preview-row.total { 
        font-weight: 700; font-size: 16px; 
        border-top: 2px solid #dee2e6; 
        margin-top: 8px; padding-top: 12px; 
    }
    .reason-select-group { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .reason-chip {
        padding: 6px 14px; border-radius: 20px; cursor: pointer;
        border: 1px solid #dee2e6; background: #fff; font-size: 13px;
        transition: all 0.2s;
    }
    .reason-chip:hover { background: #f8f9fa; }
    .reason-chip.active { background: #667eea; color: #fff; border-color: #667eea; }
    .photo-upload-area {
        border: 2px dashed #dee2e6; border-radius: 12px; padding: 20px;
        text-align: center; cursor: pointer; transition: all 0.2s;
        background: #f8f9fa;
    }
    .photo-upload-area:hover { border-color: #667eea; background: #f0f2ff; }
    .photo-upload-area i { font-size: 32px; color: #6c757d; margin-bottom: 10px; }
    .photo-preview { max-width: 100%; max-height: 150px; border-radius: 8px; margin-top: 10px; }
</style>

<div class="modal fade adjust-modal" id="orderAdjustModal" tabindex="-1" aria-labelledby="orderAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title mb-1" id="orderAdjustModalLabel">
                        <i class="tio-edit text-primary mr-2"></i>
                        {{ translate('Adjust Order Item') }}
                    </h5>
                    <p class="text-muted mb-0 small">{{ translate('Make quantity or price adjustments') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="orderAdjustForm" action="{{ route('admin.orders.adjust-item') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="order_id" id="adjust_order_id" value="{{ $order->id }}">
                <input type="hidden" name="order_detail_id" id="adjust_order_detail_id">
                
                <div class="modal-body pt-0">
                    {{-- Item Selection --}}
                    <div class="adjust-form-group">
                        <label>
                            <i class="tio-shopping-basket mr-1"></i>
                            {{ translate('Select Item') }}
                        </label>
                        <select class="form-control" name="selected_item" id="adjust_item_select" required>
                            <option value="">{{ translate('Choose an item to adjust') }}...</option>
                            @foreach($order->details as $detail)
                                <option value="{{ $detail->id }}" 
                                        data-qty="{{ $detail->quantity }}"
                                        data-price="{{ $detail->price }}"
                                        data-name="{{ $detail->product->name ?? 'Item #'.$detail->product_id }}">
                                    {{ $detail->product->name ?? 'Item #'.$detail->product_id }} 
                                    (Qty: {{ $detail->quantity }} × ₹{{ number_format($detail->price, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Selected Item Info --}}
                    <div id="adjustItemInfo" style="display:none;">
                        {{-- Adjustment Type --}}
                        <div class="adjust-form-group">
                            <label>
                                <i class="tio-options-vertical mr-1"></i>
                                {{ translate('Adjustment Type') }}
                            </label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="adjust_type" id="adjust_qty" value="quantity" checked>
                                <label class="btn btn-outline-primary" for="adjust_qty">
                                    <i class="tio-package mr-1"></i>{{ translate('Quantity') }}
                                </label>
                                
                                <input type="radio" class="btn-check" name="adjust_type" id="adjust_return" value="return">
                                <label class="btn btn-outline-warning" for="adjust_return">
                                    <i class="tio-replay mr-1"></i>{{ translate('Return') }}
                                </label>
                            </div>
                        </div>

                        {{-- Quantity Adjustment --}}
                        <div class="adjust-form-group" id="qtyAdjustSection">
                            <label>{{ translate('New Quantity') }}</label>
                            <div class="qty-input-group">
                                <button type="button" class="qty-btn" id="qtyMinus">−</button>
                                <input type="number" class="form-control" name="new_quantity" id="new_quantity" min="0" value="0">
                                <button type="button" class="qty-btn" id="qtyPlus">+</button>
                                <span class="text-muted ml-2">
                                    {{ translate('Current') }}: <strong id="current_qty_display">0</strong>
                                </span>
                            </div>
                        </div>

                        {{-- Return Quantity (for return type) --}}
                        <div class="adjust-form-group" id="returnQtySection" style="display:none;">
                            <label>{{ translate('Quantity to Return') }}</label>
                            <div class="qty-input-group">
                                <button type="button" class="qty-btn" id="returnQtyMinus">−</button>
                                <input type="number" class="form-control" name="return_quantity" id="return_quantity" min="1" value="1">
                                <button type="button" class="qty-btn" id="returnQtyPlus">+</button>
                                <span class="text-muted ml-2">
                                    {{ translate('Max') }}: <strong id="max_return_display">0</strong>
                                </span>
                            </div>
                        </div>

                        {{-- Reason Selection --}}
                        <div class="adjust-form-group">
                            <label>
                                <i class="tio-info-outlined mr-1"></i>
                                {{ translate('Reason') }}
                            </label>
                            <div class="reason-select-group">
                                @php
                                    $reasons = ['Customer changed mind', 'Out of stock', 'Damage', 'Shop closed', 'Payment issue', 'Wrong item', 'Quality issue', 'Other'];
                                @endphp
                                @foreach($reasons as $reason)
                                    <span class="reason-chip" data-reason="{{ $reason }}">{{ $reason }}</span>
                                @endforeach
                            </div>
                            <input type="text" class="form-control" name="reason" id="adjust_reason" 
                                   placeholder="{{ translate('Enter reason for adjustment') }}" required>
                        </div>

                        {{-- Photo Upload --}}
                        <div class="adjust-form-group">
                            <label>
                                <i class="tio-camera mr-1"></i>
                                {{ translate('Evidence Photo') }} <small class="text-muted">({{ translate('optional') }})</small>
                            </label>
                            <div class="photo-upload-area" id="photoUploadArea">
                                <i class="tio-cloud-upload"></i>
                                <p class="mb-0">{{ translate('Click to upload photo') }}</p>
                                <small class="text-muted">{{ translate('Max 2MB, JPG/PNG') }}</small>
                            </div>
                            <input type="file" name="photo" id="adjust_photo" accept="image/*" style="display:none;">
                            <img id="photoPreview" class="photo-preview" style="display:none;">
                        </div>

                        {{-- Notes --}}
                        <div class="adjust-form-group">
                            <label>
                                <i class="tio-document-text mr-1"></i>
                                {{ translate('Additional Notes') }} <small class="text-muted">({{ translate('optional') }})</small>
                            </label>
                            <textarea class="form-control" name="notes" id="adjust_notes" rows="2" 
                                      placeholder="{{ translate('Any additional notes about this adjustment') }}"></textarea>
                        </div>

                        {{-- Preview --}}
                        <div class="adjust-preview">
                            <h6 class="mb-3">
                                <i class="tio-chart-bar-4 mr-1 text-primary"></i>
                                {{ translate('Adjustment Preview') }}
                            </h6>
                            <div class="preview-row">
                                <span>{{ translate('Item') }}:</span>
                                <span id="preview_item_name">-</span>
                            </div>
                            <div class="preview-row">
                                <span>{{ translate('Original Qty') }}:</span>
                                <span id="preview_old_qty">-</span>
                            </div>
                            <div class="preview-row">
                                <span>{{ translate('New Qty') }}:</span>
                                <span id="preview_new_qty">-</span>
                            </div>
                            <div class="preview-row">
                                <span>{{ translate('Unit Price') }}:</span>
                                <span id="preview_unit_price">-</span>
                            </div>
                            <div class="preview-row">
                                <span>{{ translate('Original Amount') }}:</span>
                                <span id="preview_old_amount">-</span>
                            </div>
                            <div class="preview-row total">
                                <span>{{ translate('New Amount') }}:</span>
                                <span id="preview_new_amount" class="text-success">-</span>
                            </div>
                            <div class="preview-row">
                                <span>{{ translate('Difference') }}:</span>
                                <span id="preview_diff" class="font-weight-bold">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="submitAdjust" disabled>
                        <i class="tio-checkmark-circle mr-1"></i>
                        {{ translate('Apply Adjustment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('adjust_item_select');
    const adjustItemInfo = document.getElementById('adjustItemInfo');
    const newQtyInput = document.getElementById('new_quantity');
    const returnQtyInput = document.getElementById('return_quantity');
    const qtyAdjustSection = document.getElementById('qtyAdjustSection');
    const returnQtySection = document.getElementById('returnQtySection');
    const submitBtn = document.getElementById('submitAdjust');
    
    let currentQty = 0;
    let unitPrice = 0;
    let itemName = '';
    
    // Item selection change
    itemSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            currentQty = parseInt(selected.dataset.qty) || 0;
            unitPrice = parseFloat(selected.dataset.price) || 0;
            itemName = selected.dataset.name || '';
            
            document.getElementById('adjust_order_detail_id').value = this.value;
            document.getElementById('current_qty_display').textContent = currentQty;
            document.getElementById('max_return_display').textContent = currentQty;
            newQtyInput.value = currentQty;
            newQtyInput.max = currentQty + 100; // Allow increase
            returnQtyInput.max = currentQty;
            returnQtyInput.value = 1;
            
            adjustItemInfo.style.display = 'block';
            submitBtn.disabled = false;
            updatePreview();
        } else {
            adjustItemInfo.style.display = 'none';
            submitBtn.disabled = true;
        }
    });
    
    // Adjustment type change
    document.querySelectorAll('input[name="adjust_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'return') {
                qtyAdjustSection.style.display = 'none';
                returnQtySection.style.display = 'block';
            } else {
                qtyAdjustSection.style.display = 'block';
                returnQtySection.style.display = 'none';
            }
            updatePreview();
        });
    });
    
    // Quantity buttons
    document.getElementById('qtyMinus').addEventListener('click', function() {
        let val = parseInt(newQtyInput.value) || 0;
        if (val > 0) newQtyInput.value = val - 1;
        updatePreview();
    });
    
    document.getElementById('qtyPlus').addEventListener('click', function() {
        let val = parseInt(newQtyInput.value) || 0;
        newQtyInput.value = val + 1;
        updatePreview();
    });
    
    document.getElementById('returnQtyMinus').addEventListener('click', function() {
        let val = parseInt(returnQtyInput.value) || 1;
        if (val > 1) returnQtyInput.value = val - 1;
        updatePreview();
    });
    
    document.getElementById('returnQtyPlus').addEventListener('click', function() {
        let val = parseInt(returnQtyInput.value) || 0;
        if (val < currentQty) returnQtyInput.value = val + 1;
        updatePreview();
    });
    
    newQtyInput.addEventListener('input', updatePreview);
    returnQtyInput.addEventListener('input', updatePreview);
    
    // Reason chips
    document.querySelectorAll('.reason-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.reason-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('adjust_reason').value = this.dataset.reason;
        });
    });
    
    // Photo upload
    document.getElementById('photoUploadArea').addEventListener('click', function() {
        document.getElementById('adjust_photo').click();
    });
    
    document.getElementById('adjust_photo').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photoPreview').src = e.target.result;
                document.getElementById('photoPreview').style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    function updatePreview() {
        const adjustType = document.querySelector('input[name="adjust_type"]:checked').value;
        let newQty;
        
        if (adjustType === 'return') {
            const returnQty = parseInt(returnQtyInput.value) || 0;
            newQty = Math.max(0, currentQty - returnQty);
        } else {
            newQty = parseInt(newQtyInput.value) || 0;
        }
        
        const oldAmount = currentQty * unitPrice;
        const newAmount = newQty * unitPrice;
        const diff = newAmount - oldAmount;
        
        document.getElementById('preview_item_name').textContent = itemName;
        document.getElementById('preview_old_qty').textContent = currentQty;
        document.getElementById('preview_new_qty').textContent = newQty;
        document.getElementById('preview_unit_price').textContent = '₹' + unitPrice.toFixed(2);
        document.getElementById('preview_old_amount').textContent = '₹' + oldAmount.toFixed(2);
        document.getElementById('preview_new_amount').textContent = '₹' + newAmount.toFixed(2);
        
        const diffEl = document.getElementById('preview_diff');
        if (diff > 0) {
            diffEl.textContent = '+₹' + diff.toFixed(2);
            diffEl.className = 'font-weight-bold text-success';
        } else if (diff < 0) {
            diffEl.textContent = '-₹' + Math.abs(diff).toFixed(2);
            diffEl.className = 'font-weight-bold text-danger';
        } else {
            diffEl.textContent = '₹0.00';
            diffEl.className = 'font-weight-bold text-muted';
        }
    }
});
</script>
