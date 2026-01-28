@extends('inventory.layouts.app')

@section('content')
    <div class="container py-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            @php
                $images = json_decode($product->image, true);
                $imagePath = isset($images[0]) ? asset('storage/product/' . $images[0]) : asset('default.png');
                $categories = json_decode($product->category_ids, true);
            @endphp

            <div class="row g-0">
                <!-- Product Image -->
                <div class="col-md-5 bg-light d-flex align-items-center justify-content-center p-4">
                    <div class="ratio ratio-1x1 w-100 rounded-4 overflow-hidden">
                        <img src="{{ $imagePath }}" alt="{{ $product->name }}" class="w-100 h-100 object-fit-cover">
                    </div>
                </div>

                <!-- Product Details -->
                <div class="col-md-7 p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-2">{{ $product->name }}</h3>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <h4 class="text-primary fw-semibold mb-0">₹{{ number_format($product->price, 2) }}</h4>
                        @if ($product->discount > 0)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                {{ $product->discount }}{{ $product->discount_type == 'percent' ? '%' : '₹' }} Off
                            </span>
                        @endif
                    </div>

                    <div class="bg-light rounded-4 p-3 mb-4">
                        <h6 class="fw-bold text-secondary mb-3">Product Specifications</h6>
                        <div class="row small">
                            <div class="col-sm-6 mb-2"><strong>Unit:</strong> {{ $product->unit ?? '-' }}</div>
                            <div class="col-sm-6 mb-2"><strong>Capacity:</strong> {{ $product->capacity ?? '-' }}</div>
                            <div class="col-sm-6 mb-2"><strong>Weight:</strong> {{ $product->weight ?? '-' }} kg</div>
                            <div class="col-sm-6 mb-2"><strong>Stock:</strong> {{ $product->total_stock ?? 0 }}</div>
                            <div class="col-sm-6 mb-2"><strong>Tax:</strong> {{ $product->tax }}%
                                ({{ ucfirst($product->tax_type) }})</div>
                            <div class="col-sm-6 mb-2"><strong>Status:</strong>
                                @if ($product->status == 1)
                                    <span class="badge bg-success px-2 py-1">Active</span>
                                @else
                                    <span class="badge bg-danger px-2 py-1">Inactive</span>
                                @endif
                            </div>
                            <div class="col-sm-6 mb-2"><strong>Featured:</strong>
                                {{ $product->is_featured ? 'Yes' : 'No' }}</div>
                            <div class="col-sm-6 mb-2"><strong>Daily Needs:</strong>
                                {{ $product->daily_needs ? 'Yes' : 'No' }}</div>
                            <div class="col-sm-6 mb-2"><strong>Popularity:</strong> {{ $product->popularity_count }}</div>
                            <div class="col-sm-6 mb-2"><strong>View Count:</strong> {{ $product->view_count }}</div>
                            <div class="col-sm-6 mb-2"><strong>Max Order Qty:</strong>
                                {{ $product->maximum_order_quantity }}</div>
                            @if (!empty($categories))
                                <div class="col-sm-12 mt-2">
                                    <strong>Categories:</strong>
                                    @foreach ($product->categories() as $category)
                                        <span
                                            class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 rounded-pill">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach

                                </div>
                            @endif

                        </div>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h6 class="fw-semibold text-secondary mb-2">Description</h6>
                        @if (!empty($product->description))
                            <div class="text-muted mb-0">{!! $product->description !!}</div>
                        @else
                            <p class="text-muted mb-0">No description available.</p>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-4">
                        <a href="{{ route('inventory.products.index') }}"
                            class="btn btn-outline-secondary rounded-pill px-4">
                            ← Back to Products
                        </a>
                    </div>

                    <div class="text-muted mt-4 small">
                        <p class="mb-1">🕒 <strong>Created:</strong> {{ $product->created_at->format('d M Y, h:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .object-fit-cover {
            object-fit: cover;
        }

        .card {
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        hr {
            border-top: 1px solid #eee;
        }

        .bg-success-subtle {
            background-color: #e8f5e9 !important;
        }

        .bg-primary-subtle {
            background-color: #e3f2fd !important;
        }

        .border-success-subtle {
            border-color: #c8e6c9 !important;
        }

        .border-primary-subtle {
            border-color: #bbdefb !important;
        }
    </style>
@endsection
