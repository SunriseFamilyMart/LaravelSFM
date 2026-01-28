@extends('inventory.layouts.app')

@section('content')
    <div class="container py-5">

        <!-- Header + Search -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0">🛍️ Product List</h2>

            <div class="d-flex gap-2 align-items-center">

                <!-- Search Form -->
                <form action="{{ route('inventory.products.index') }}" method="GET" class="d-flex align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search product..."
                        value="{{ request('search') }}" style="min-width: 200px;">

                    <button class="btn btn-sm btn-secondary ms-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                </form>

                <a href="{{ route('inventory.products.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Product
                </a>

                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                    Back
                </a>
            </div>
        </div>


        @if ($products->count() > 0)
            <div class="row g-4">
                @foreach ($products as $product)
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 product-card">

                            @php
                                $image = json_decode($product->image, true);
                                $imagePath = isset($image[0])
                                    ? asset('storage/product/' . $image[0])
                                    : asset('default.png');
                            @endphp

                            <div class="ratio ratio-1x1 overflow-hidden rounded-top-4">
                                <img src="{{ $imagePath }}" class="w-100 h-100 object-fit-cover"
                                    alt="{{ $product->name }}">
                            </div>

                            <div class="card-body d-flex flex-column">
                                <h6 class="fw-semibold text-dark mb-2 text-truncate">{{ $product->name }}</h6>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-primary">₹{{ number_format($product->price, 2) }}</span>
                                    <span class="text-muted small">{{ $product->unit ?? 'unit' }}</span>
                                </div>

                                <div class="mt-auto">
                                    <a href="{{ route('inventory.products.show', $product->id) }}"
                                        class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <img src="{{ asset('images/empty-box.svg') }}" alt="No products" width="120" class="mb-3">
                <h5 class="text-muted">No products found</h5>
            </div>
        @endif
    </div>

    <style>
        .product-card {
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .object-fit-cover {
            object-fit: cover;
        }

        .rounded-top-4 {
            border-top-left-radius: 1rem !important;
            border-top-right-radius: 1rem !important;
        }
    </style>
@endsection


product.index
