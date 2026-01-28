@extends('inventory.layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Add New Supplier</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('inventory.suppliers.store') }}" method="POST" enctype="multipart/form-data"
            class="border p-4 rounded shadow-sm bg-white">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Supplier Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter supplier name"
                    value="{{ old('name') }}" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address"
                    value="{{ old('email') }}" required>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="Enter phone number"
                    value="{{ old('phone') }}" required>
            </div>

            <div class="mb-3">
                <label for="alternate_number" class="form-label">Alternate Number</label>
                <input type="text" name="alternate_number" class="form-control" placeholder="Enter alternate number"
                    value="{{ old('alternate_number') }}">
            </div>

            <div class="mb-3">
                <label for="gst_number" class="form-label">GST Number</label>
                <input type="text" name="gst_number" class="form-control" placeholder="Enter GST number"
                    value="{{ old('gst_number') }}">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Enter address">{{ old('address') }}</textarea>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Supplier Image</label>
                <input type="file" name="image" class="form-control">
                <small class="text-muted">Optional. JPG, PNG, GIF up to 2MB.</small>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('inventory.suppliers.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
@endsection
