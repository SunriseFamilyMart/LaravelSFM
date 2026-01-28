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

    <form action="{{ route('inventory.suppliers.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="border p-4 rounded shadow-sm bg-white">
        @csrf

        {{-- Supplier Name (MANDATORY) --}}
        <div class="mb-3">
            <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
            <input type="text"
                   name="name"
                   class="form-control"
                   placeholder="Enter supplier name"
                   value="{{ old('name') }}"
                   required>
        </div>

        {{-- Email (OPTIONAL) --}}
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="Enter email address (optional)"
                   value="{{ old('email') }}">
        </div>

        {{-- Phone (MANDATORY) --}}
        <div class="mb-3">
            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
            <input type="text"
                   name="phone"
                   class="form-control"
                   placeholder="Enter phone number"
                   value="{{ old('phone') }}"
                   required>
        </div>

        {{-- Alternate Number (OPTIONAL) --}}
        <div class="mb-3">
            <label class="form-label">Alternate Number</label>
            <input type="text"
                   name="alternate_number"
                   class="form-control"
                   placeholder="Enter alternate number"
                   value="{{ old('alternate_number') }}">
        </div>

        {{-- GST Number (MANDATORY) --}}
        <div class="mb-3">
            <label class="form-label">GST Number <span class="text-danger">*</span></label>
            <input type="text"
                   name="gst_number"
                   class="form-control"
                   placeholder="Enter GST number"
                   value="{{ old('gst_number') }}"
                   required>
        </div>

        {{-- Address (MANDATORY) --}}
        <div class="mb-3">
            <label class="form-label">Address <span class="text-danger">*</span></label>
            <textarea name="address"
                      class="form-control"
                      rows="3"
                      placeholder="Enter address"
                      required>{{ old('address') }}</textarea>
        </div>

        {{-- Supplier Image (OPTIONAL) --}}
        <div class="mb-3">
            <label class="form-label">Supplier Image</label>
            <input type="file"
                   name="image"
                   class="form-control">
            <small class="text-muted">
                Optional. JPG, PNG, GIF up to 2MB.
            </small>
        </div>

        <div class="mt-4 d-flex justify-content-between">
            <a href="{{ route('inventory.suppliers.index') }}"
               class="btn btn-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Save Supplier
            </button>
        </div>
    </form>
</div>
@endsection
