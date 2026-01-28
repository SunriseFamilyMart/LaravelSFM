@extends('inventory.layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">📦 Supplier Management</h2>
            <a href="{{ route('inventory.suppliers.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Supplier
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Alternate</th>
                                <th>GST</th>
                                <th>Address</th>
                                <th width="130">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td class="text-center">
                                        @if ($supplier->image)
                                            <img src="{{ asset('storage/' . $supplier->image) }}" width="50"
                                                height="50" class="rounded-circle border" alt="Supplier">
                                        @else
                                            <div class="bg-light text-muted small rounded-circle d-flex align-items-center justify-content-center border"
                                                style="width:50px; height:50px;">
                                                N/A
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $supplier->name }}</td>
                                    <td>{{ $supplier->email }}</td>
                                    <td>{{ $supplier->phone }}</td>
                                    <td>{{ $supplier->alternate_number ?? '-' }}</td>
                                    <td>{{ $supplier->gst_number ?? '-' }}</td>
                                    <td>{{ $supplier->address ?? '-' }}</td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('inventory.suppliers.edit', $supplier->id) }}"
                                            class="btn btn-sm btn-outline-warning me-1">
                                            Edit
                                        </a>
                                        <form action="{{ route('inventory.suppliers.destroy', $supplier->id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-box-seam"></i> No suppliers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($suppliers->hasPages())
                <div class="card-footer bg-white d-flex justify-content-center">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
