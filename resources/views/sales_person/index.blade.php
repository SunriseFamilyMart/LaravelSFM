@extends('layouts.admin.app')

@section('title', translate('Sales People'))

@section('content')
<div class="container-fluid py-4">

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ translate('Sales People') }}</h2>
        <a href="{{ route('admin.sales-person.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ translate('Add New') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <!-- SEARCH -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <form action="{{ route('admin.sales-person.index') }}" method="GET" class="d-flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-control me-2"
                           placeholder="{{ translate('Search by name or phone') }}">
                    <button type="submit" class="btn btn-secondary">{{ translate('Search') }}</button>
                </form>
            </div>

            <!-- MAIN TABLE -->
            <div class="table-responsive">
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
                                        <img src="{{ asset('storage/' . $person->id_proof) }}" width="60"
                                             class="img-thumbnail">
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($person->person_photo)
                                        <img src="{{ asset('storage/' . $person->person_photo) }}" 
                                             width="60" height="60" class="rounded-circle">
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $person->emergency_contact_name }}<br>
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
                                        <button class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure?')">
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
            </div>

            <!-- PAGINATION -->
            <div class="mt-3">
                {{ $salesPeople->links('pagination::bootstrap-5') }}
            </div>

            <!-- SECOND SUMMARY TABLE (NEAT UI) AFTER PAGINATION -->
            <div class="mt-5">
                <h4 class="mb-3">📊 {{ translate('Today Summary') }}</h4>

                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>{{ translate('Sales Person') }}</th>
                            <th>{{ translate('Total Orders') }}</th>
                            <th>{{ translate('Total Trips') }}</th>
                            <th>{{ translate('Total Shops') }}</th>
                            <th>{{ translate('Total Sales') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($salesPeople as $person)
                            <tr>
                                <td><strong>{{ $person->name }}</strong></td>
                                <td>{{ $person->total_orders }}</td>
                                <td>{{ $person->total_trips }}</td>
                                <td>{{ $person->total_shops }}</td>
                                <td>₹ {{ number_format($person->total_sales, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection
