@extends('inventory.layouts.app')

@section('title', 'Add New Product')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush

@section('content')
    <div class="container py-4">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Product</h4>
            </div>

            <div class="card-body">
                {{-- Success Message --}}
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> Please fix the following errors:
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('inventory.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        {{-- Product Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control"
                                placeholder="Enter product name" required>
                        </div>
                        {{-- Description --}}
                        {{-- Description --}}
                        <div class="col-md-12 mt-3">
                            <label class="form-label fw-bold">Product Description</label>
                            <textarea name="description" id="summernote" class="form-control" rows="5">{{ old('description') }}</textarea>
                        </div>

                        <!-- Summernote CSS -->
                        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css"
                            rel="stylesheet">

                        <!-- jQuery (required by Summernote) -->
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                        <!-- Bootstrap JS (if not already included) -->
                        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

                        <!-- Summernote JS -->
                        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

                        <script>
                            $(document).ready(function() {
                                $('#summernote').summernote({
                                    placeholder: 'Write a detailed description...',
                                    tabsize: 2,
                                    height: 200,
                                    toolbar: [
                                        ['style', ['bold', 'italic', 'underline', 'clear']],
                                        ['font', ['fontsize', 'color']],
                                        ['para', ['ul', 'ol', 'paragraph']],
                                        ['insert', ['link', 'picture']],
                                        ['view', ['fullscreen', 'codeview']]
                                    ]
                                });
                            });
                        </script>
                        {{-- Price --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="price" value="{{ old('price') }}" class="form-control"
                                step="0.01" required>
                        </div>
                        {{-- Tax --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tax</label>
                            <div class="input-group">
                                <input type="number" name="tax" value="{{ old('tax', 0) }}" class="form-control"
                                    step="0.01" placeholder="Enter tax value">
                                <select name="tax_type" class="form-select" style="max-width: 120px;">
                                    <option value="percent" {{ old('tax_type') == 'percent' ? 'selected' : '' }}>%</option>
                                    <option value="amount" {{ old('tax_type') == 'amount' ? 'selected' : '' }}>₹</option>
                                </select>
                            </div>
                        </div>

                        {{-- Discount --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Discount</label>
                            <div class="input-group">
                                <input type="number" name="discount" value="{{ old('discount', 0) }}" class="form-control"
                                    step="0.01" placeholder="Enter discount value">
                                <select name="discount_type" class="form-select" style="max-width: 120px;">
                                    <option value="percent" {{ old('discount_type') == 'percent' ? 'selected' : '' }}>%
                                    </option>
                                    <option value="amount" {{ old('discount_type') == 'amount' ? 'selected' : '' }}>₹
                                    </option>
                                </select>
                            </div>
                        </div>


                        {{-- Unit --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Unit</label>
                            <select name="unit" class="form-select">
                                @php
                                    $units = ['pcs', 'ml', 'ltr', 'gm', 'kg'];
                                @endphp
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}"
                                        {{ old('unit', 'pcs') == $unit ? 'selected' : '' }}>
                                        {{ strtoupper($unit) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        {{-- Capacity --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Capacity</label>
                            <input type="number" name="capacity" value="{{ old('capacity', 1) }}" class="form-control"
                                step="0.01" placeholder="e.g. 500">
                        </div>

                        {{-- Category --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Subcategory --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Subcategory</label>
                            <select name="subcategory_id" id="subcategory" class="form-select">
                                <option value="">-- Select Subcategory --</option>
                            </select>
                        </div>
                        {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
                        <script>
                            $(document).ready(function() {
                                $('#category').on('change', function() {
                                    var categoryId = $(this).val();
                                    $('#subcategory').html('<option value="">-- Loading... --</option>');

                                    if (categoryId) {
                                        $.ajax({
                                            url: "{{ url('/inventory/categories') }}/" + categoryId + "/subcategories",
                                            type: "GET",
                                            success: function(data) {
                                                let options = '<option value="">-- Select Subcategory --</option>';
                                                data.forEach(function(sub) {
                                                    options +=
                                                        `<option value="${sub.id}">${sub.name}</option>`;
                                                });
                                                $('#subcategory').html(options);
                                            },
                                            error: function() {
                                                $('#subcategory').html(
                                                    '<option value="">-- No Subcategories Found --</option>');
                                            }
                                        });
                                    } else {
                                        $('#subcategory').html('<option value="">-- Select Subcategory --</option>');
                                    }
                                });
                            });
                        </script>

                        {{-- Total Stock --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Total Stock <span class="text-danger">*</span></label>
                            <input type="number" name="total_stock" value="{{ old('total_stock', 0) }}"
                                class="form-control" min="0" required>
                        </div>
                        {{-- Product Tags --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Product Tags</label>
                            <input type="text" name="tags" id="tags" class="form-control"
                                value="{{ old('tags') }}" placeholder="Type and press Enter">
                            <small class="text-muted">Add multiple tags separated by commas or Enter.</small>
                        </div>
                        {{-- Tags Input CSS --}}
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.css"
                            rel="stylesheet">

                        {{-- Tags Input JS --}}
                        <script src="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.min.js"></script>

                        <style>
                            .bootstrap-tagsinput {
                                width: 100%;
                                min-height: 38px;
                                padding: 6px;
                                line-height: 22px;
                            }

                            .bootstrap-tagsinput .tag {
                                background: #0d6efd;
                                border-radius: 4px;
                                padding: 3px 8px;
                                color: #fff;
                                margin-right: 5px;
                            }
                        </style>

                        <script>
                            $(document).ready(function() {
                                $('#tags').tagsinput({
                                    trimValue: true
                                });
                            });
                        </script>
                        {{-- Image --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Product Image</label>
                            <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">

                            <div class="mt-3" id="imagePreviewContainer" style="display:none;">
                                <div class="position-relative d-inline-block">
                                    <img id="imagePreview" src="#" alt="Image Preview" class="img-thumbnail"
                                        style="max-width: 200px; max-height: 200px;">
                                    <button type="button" id="removeImageBtn"
                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle"
                                        style="transform: translate(30%, -30%);">
                                        &times;
                                    </button>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">Accepted formats: JPG, JPEG, PNG (Max: 2MB)</small>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const imageInput = document.getElementById('imageInput');
                                const imagePreviewContainer = document.getElementById('imagePreviewContainer');
                                const imagePreview = document.getElementById('imagePreview');
                                const removeImageBtn = document.getElementById('removeImageBtn');

                                // When a new image is selected
                                imageInput.addEventListener('change', function(event) {
                                    const file = event.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            imagePreview.src = e.target.result;
                                            imagePreviewContainer.style.display = 'block';
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });

                                // Remove the image preview
                                removeImageBtn.addEventListener('click', function() {
                                    imageInput.value = ''; // Clear file input
                                    imagePreview.src = '#';
                                    imagePreviewContainer.style.display = 'none';
                                });
                            });
                        </script>
                    </div>

                    {{-- Submit --}}
                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="bi bi-save"></i> Save Product
                        </button>
                        <a href="{{ route('inventory.dashboard') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
