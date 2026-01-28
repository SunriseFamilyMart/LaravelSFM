@extends('layouts.admin.app')

@section('title', translate('Create Inventory Staff'))

@section('content')
    <div class="content container-fluid py-4">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-header-title mb-1">{{ translate('Add Inventory Staff') }}</h2>
                <p class="text-muted mb-0">
                    {{ translate('Fill in the details below to create a new inventory staff member.') }}</p>
            </div>
            <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">
                <i class="tio-arrow-backward"></i> {{ translate('Back') }}
            </a>
        </div>
        <!-- End Page Header -->

        <!-- Form Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body mx-auto col-6">
                <form action="{{ route('admin.inventories.store') }}" method="POST" enctype="multipart/form-data"
                    class="needs-validation" novalidate>
                    @csrf

                    {{-- Form Fields --}}
                    @include('inventories.form')

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="tio-save"></i> {{ translate('Save') }}
                        </button>
                        <a href="{{ route('admin.inventories.index') }}" class="btn btn-secondary">
                            <i class="tio-cancel"></i> {{ translate('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Form Card -->

    </div>
@endsection
