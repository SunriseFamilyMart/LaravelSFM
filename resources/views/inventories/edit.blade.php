@extends('layouts.admin.app')

@section('title', translate('Edit Inventory Staff'))

@section('content')
    <div class="container py-5">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">{{ translate('Edit Inventory Staff') }}</h2>
                <p class="text-muted mb-0">{{ translate('Update details of your inventory staff member below.') }}</p>
            </div>
            <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">
                <i class="tio-arrow-back-up"></i> {{ translate('Back to List') }}
            </a>
        </div>

        <!-- Edit Form Card -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4 mx-auto col-6">

                <form action="{{ route('admin.inventories.update', $inventory) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @include('inventories.form')

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="tio-save"></i> {{ translate('Update') }}
                        </button>
                        <a href="{{ route('admin.inventories.index') }}" class="btn btn-light border px-4">
                            <i class="tio-cancel"></i> {{ translate('Cancel') }}
                        </a>
                    </div>
                </form>

            </div>
        </div>

    </div>
@endsection
