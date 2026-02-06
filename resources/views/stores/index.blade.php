@extends('layouts.admin.app')

@section('title', translate('Stores List'))

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><i class="bi bi-shop me-2"></i> Stores</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.stores.pendingSelf') }}" class="btn btn-outline-warning shadow-sm">
                    <i class="bi bi-shield-exclamation me-1"></i> Pending Approvals
                </a>
                <a href="{{ route('admin.stores.create') }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Add Store
                </a>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Search Form --}}
        <form method="GET" action="{{ route('admin.stores.index') }}" class="mb-4">
            <div class="input-group shadow-sm">
                <input type="text" name="search" class="form-control" placeholder="Search stores..."
                    value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Search</button>
                @if (request('search'))
                    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        {{-- Stores Table --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-primary text-dark">
                            <tr>
                                <th>Store Name</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Photo</th>
                                <th class="text-center" style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stores as $store)
                                <tr>
                                    <td class="fw-semibold">{{ $store->store_name }}</td>
                                    <td>{{ $store->customer_name }}</td>

                                    {{-- Phone --}}
                                    <td>
                                        @if ($store->phone_number)
                                            <a href="tel:{{ $store->phone_number }}" class="text-decoration-none">
                                                <i class="bi bi-telephone me-1 text-primary"></i> {{ $store->phone_number }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <td>
                                        @php
                                            $source = $store->registration_source ?? 'sales_person';
                                            $status = $store->approval_status ?? 'approved';
                                        @endphp
                                        @if($source === 'self')
                                            @if($status === 'pending')
                                                <span class="badge bg-warning text-dark">Self • Pending</span>
                                            @elseif($status === 'rejected')
                                                <span class="badge bg-danger">Self • Rejected</span>
                                            @else
                                                <span class="badge bg-success">Self • Approved</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Sales Added</span>
                                        @endif
                                    </td>

                                    {{-- Photo --}}
                                    <td>
                                        @if ($store->store_photo)
                                            <img src="{{ asset('storage/' . $store->store_photo) }}" alt="store photo"
                                                class="img-thumbnail rounded shadow-sm" width="60">
                                        @else
                                            <span class="text-muted">No photo</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('admin.stores.show', $store->id) }}"
                                            class="btn btn-sm btn-info me-1">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="{{ route('admin.stores.edit', $store->id) }}"
                                            class="btn btn-sm btn-warning me-1">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.stores.destroy', $store->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this store?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-exclamation-circle me-1"></i> No stores found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 d-flex justify-content-center">
            {{ $stores->links() }}
        </div>
    </div>
@endsection
