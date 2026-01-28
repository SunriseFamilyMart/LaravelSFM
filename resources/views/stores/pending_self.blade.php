@extends('layouts.admin.app')

@section('title','Pending Store Registrations')

@section('content')
<div class="content container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h1 mb-1">Pending Store Registrations</h2>
            <p class="mb-0 text-muted">Stores self-registered from the Store app. Approve + assign a salesperson before they can login.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.stores.index') }}">All Stores</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form class="w-100" method="GET" action="{{ route('admin.stores.pendingSelf') }}">
                <div class="input-group input-group-merge">
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search store name, customer, phone...">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Store</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th style="width:240px;">Assign Salesperson</th>
                            <th class="text-center" style="width:220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($stores as $store)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong>{{ $store->store_name }}</strong>
                                    <span class="text-muted">Owner: {{ $store->customer_name }}</span>
                                    <span class="badge badge-soft-warning mt-1" style="width:fit-content;">Pending</span>
                                </div>
                            </td>
                            <td>{{ $store->phone_number }}</td>
                            <td style="max-width:360px; white-space:normal;">{{ $store->address }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.stores.approveSelf', $store->id) }}" class="d-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-control" name="sales_person_id" required>
                                        <option value="" disabled selected>Select salesperson</option>
                                        @foreach($salesPeople as $sp)
                                            <option value="{{ $sp->id }}">{{ $sp->name }} ({{ $sp->phone_number ?? 'N/A' }})</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-success" type="submit">Approve</button>
                                </form>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('admin.stores.rejectSelf', $store->id) }}" onsubmit="return confirm('Reject this store registration?');" style="display:inline-block;">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-outline-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-4">
                                <div class="text-muted">No pending store registrations.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {!! $stores->links() !!}
        </div>
    </div>
</div>
@endsection
