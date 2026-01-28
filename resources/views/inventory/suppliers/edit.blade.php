@extends('inventory.layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Edit Supplier</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('inventory.suppliers.update', $supplier->id) }}" method="POST" enctype="multipart/form-data"
            class="border p-4 rounded shadow-sm bg-white">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Supplier Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name) }}"
                    required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}"
                    required>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}"
                    required>
            </div>

            <div class="mb-3">
                <label for="alternate_number" class="form-label">Alternate Number</label>
                <input type="text" name="alternate_number" class="form-control"
                    value="{{ old('alternate_number', $supplier->alternate_number) }}">
            </div>

            <div class="mb-3">
                <label for="gst_number" class="form-label">GST Number</label>
                <input type="text" name="gst_number" class="form-control"
                    value="{{ old('gst_number', $supplier->gst_number) }}">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $supplier->address) }}</textarea>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Supplier Image</label><br>
                @if ($supplier->image)
                    <img src="{{ asset('storage/' . $supplier->image) }}" alt="Supplier Image" width="100"
                        class="mb-2 rounded">
                    <br>
                @endif
                <input type="file" name="image" class="form-control">
                <small class="text-muted">Leave empty if you don’t want to change the image.</small>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('inventory.suppliers.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">Update Supplier</button>
            </div>
        </form>
    </div>
@endsection
