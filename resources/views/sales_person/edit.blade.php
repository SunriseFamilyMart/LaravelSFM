@extends('layouts.admin.app')

@section('title', translate('Edit Sales Person'))

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ translate('Edit Sales Person') }}</h4>
                <a href="{{ route('admin.sales-person.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> {{ translate('Back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('admin.sales-person.update', $salesPerson->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $salesPerson->name) }}" required>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Phone Number') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="phone_number" class="form-control"
                                value="{{ old('phone_number', $salesPerson->phone_number) }}" required>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $salesPerson->email) }}" required>
                        </div>

                        <!-- Address -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Address') }}</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $salesPerson->address) }}</textarea>
                        </div>

                        <!-- Emergency Contact Name -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Emergency Contact Name') }}</label>
                            <input type="text" name="emergency_contact_name" class="form-control"
                                value="{{ old('emergency_contact_name', $salesPerson->emergency_contact_name) }}">
                        </div>

                        <!-- Emergency Contact Number -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Emergency Contact Number') }}</label>
                            <input type="text" name="emergency_contact_number" class="form-control"
                                value="{{ old('emergency_contact_number', $salesPerson->emergency_contact_number) }}">
                        </div>

                        <!-- ID Proof -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('ID Proof') }}</label>
                            <input type="file" name="id_proof" class="form-control">
                            @if ($salesPerson->id_proof)
                                <small class="d-block mt-1">
                                    {{ translate('Current:') }}
                                    <img src="{{ asset('storage/' . $salesPerson->id_proof) }}" alt="ID Proof"
                                        class="img-thumbnail" width="80">
                                </small>
                            @endif
                        </div>

                        <!-- Person Photo -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Person Photo') }}</label>
                            <input type="file" name="person_photo" class="form-control">
                            @if ($salesPerson->person_photo)
                                <small class="d-block mt-1">
                                    {{ translate('Current:') }}
                                    <img src="{{ asset('storage/' . $salesPerson->person_photo) }}" alt="Person Photo"
                                        class="rounded-circle" width="80" height="80">
                                </small>
                            @endif
                        </div>

                        <!-- Branch -->
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Branch') }}</label>
                            <select name="branch" class="form-control">
                                <option value="">{{ translate('Select Branch') }}</option>
                                @foreach(\App\Model\Branch::all() as $branch)
                                    <option value="{{ $branch->name }}" 
                                        {{ old('branch', $salesPerson->branch) == $branch->name ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ translate('Password') }}</label>
                        <input type="password" name="password" class="form-control">
                        <small>Leave blank to keep existing password</small>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> {{ translate('Update') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
