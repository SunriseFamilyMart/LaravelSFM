@extends('layouts.admin.app')

@section('title', translate("Store Details - {$store->store_name}"))

@section('content')
    <div class="container-fluid py-3">
        <h2 class="mb-4">Store Details</h2>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Error Message --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Validation Error:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Store Details Card --}}
        <div class="card shadow-sm p-4">
            <div class="row g-4">

                {{-- Left Column: Store Info --}}
                <div class="col-md-6">
                    <p><strong>Store Name:</strong> {{ $store->store_name }}</p>
                    <p><strong>Customer:</strong> {{ $store->customer_name }}</p>
                    <p><strong>Address:</strong> {{ $store->address ?? '-' }}</p>
                    <p><strong>GST Number:</strong> {{ $store->gst_number ?? '-' }}</p>

                    {{-- Phone --}}
                    <p>
                        <strong>Phone:</strong>
                        @if ($store->phone_number)
                            <a href="tel:+91{{ $store->phone_number }}" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i> {{ $store->phone_number }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </p>

                    {{-- Alternate Phone --}}
                    <p>
                        <strong>Alternate Number:</strong>
                        @if ($store->alternate_number)
                            <a href="tel:+91{{ $store->alternate_number }}" class="text-decoration-none">
                                <i class="bi bi-telephone-outbound me-1"></i> {{ $store->alternate_number }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </p>

                    <p><strong>Landmark:</strong> {{ $store->landmark ?? '-' }}</p>

                    {{-- Update Sales Person --}}
                    <div class="mt-3">
                        <form action="{{ route('admin.stores.updateSalesPerson', $store->id) }}" method="POST"
                            class="border p-3 rounded bg-light">
                            @csrf
                            @method('PATCH')

                            <label for="sales_person_id" class="form-label"><strong>Assign Sales Person</strong></label>

                            <select name="sales_person_id" id="sales_person_id" class="form-select">
                                <option value="">Select Sales Person</option>
                                @foreach ($salesPeople as $person)
                                    <option value="{{ $person->id }}"
                                        {{ $store->sales_person_id == $person->id ? 'selected' : '' }}>
                                        {{ $person->name }}
                                    </option>
                                @endforeach
                            </select>

                            <button class="btn btn-primary mt-3 w-100">
                                <i class="bi bi-check-lg"></i> Update Sales Person
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Right Column: Location & Photo --}}
                <div class="col-md-6">
                    <p><strong>Latitude:</strong> {{ $store->latitude ?? '-' }}</p>
                    <p><strong>Longitude:</strong> {{ $store->longitude ?? '-' }}</p>

                    {{-- Store Photo --}}
                    <p><strong>Photo:</strong></p>
                    @if ($store->store_photo)
                        <img src="{{ asset('storage/' . $store->store_photo) }}" alt="store photo"
                            class="img-fluid rounded shadow-sm" style="max-width: 260px;">
                    @else
                        <span class="text-muted">No photo</span>
                    @endif
                </div>

            </div>
        </div>

        {{-- Back Button --}}
        <div class="mt-4">
            <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Stores
            </a>
        </div>

    </div>
@endsection
