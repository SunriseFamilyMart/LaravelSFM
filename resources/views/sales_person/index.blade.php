@extends('layouts.admin.app')

@section('title', translate('Sales People'))

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ translate('Sales People') }}</h2>
            <a href="{{ route('admin.sales-person.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> {{ translate('Add New') }}
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <form action="{{ route('admin.sales-person.index') }}" method="GET" class="d-flex">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control me-2"
                                placeholder="{{ translate('Search by name or phone') }}">
                            <button type="submit" class="btn btn-secondary">{{ translate('Search') }}</button>
                        </form>
                    </div>

                    <table class="table table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Email') }}</th>
                                <th>{{ translate('ID Proof') }}</th>
                                <th>{{ translate('Photo') }}</th>
                                <th>{{ translate('Emergency Contact') }}</th>
                                <th class="text-center">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salesPeople as $person)
                                <tr>
                                    <td>{{ $person->name }}</td>
                                    <td>{{ $person->phone_number }}</td>
                                    <td>{{ $person->email }}</td>
                                    <td>
                                        @if ($person->id_proof)
                                            <img src="{{ asset('storage/' . $person->id_proof) }}" alt="ID Proof"
                                                class="img-thumbnail" width="60">
                                        @else
                                            <span class="text-muted">{{ translate('N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($person->person_photo)
                                            <img src="{{ asset('storage/' . $person->person_photo) }}" alt="Person Photo"
                                                class="rounded-circle" width="60" height="60">
                                        @else
                                            <span class="text-muted">{{ translate('N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $person->emergency_contact_name }} <br>
                                        <small class="text-muted">{{ $person->emergency_contact_number }}</small>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('admin.sales-person.edit', $person->id) }}"
                                            class="btn btn-sm btn-warning me-1">
                                            <i class="bi bi-pencil-square"></i> {{ translate('Edit') }}
                                        </a>

                                        <form action="{{ route('admin.sales-person.destroy', $person->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('{{ translate('Are you sure you want to delete this sales person?') }}')">
                                                <i class="bi bi-trash"></i> {{ translate('Delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        {{ translate('No Sales People Found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Pagination Links -->
                    <div class="mt-3">
                        {{ $salesPeople->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
