@extends('layouts.admin.app')

@section('title', translate('Inventory'))

@section('content')
    <div class="container mt-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold">{{ translate('Inventory List') }}</h2>
            <a href="{{ route('admin.inventories.create') }}" class="btn btn-primary">
                <i class="tio-add"></i> {{ translate('Add New') }}
            </a>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Inventory Table Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Phone') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('ID Proof') }}</th>
                            <th class="text-end">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inventories as $inventory)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $inventory->name }}</td>
                                <td>{{ $inventory->email }}</td>
                                <td>{{ $inventory->phone }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ $inventory->status == 'active' ? 'success' : 'secondary' }} text-white">
                                        {{ ucfirst($inventory->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($inventory->idproof)
                                        <a href="{{ asset('storage/' . $inventory->idproof) }}" target="_blank">
                                            {{ translate('View') }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ translate('N/A') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.inventories.edit', $inventory) }}"
                                            class="btn btn-sm btn-warning">
                                            <i class="tio-edit"></i> {{ translate('Edit') }}
                                        </a>

                                        <button type="button" class="btn btn-sm btn-info resetPasswordBtn"
                                            data-id="{{ $inventory->id }}" data-name="{{ $inventory->name }}">
                                            <i class="tio-key"></i> {{ translate('Reset Password') }}
                                        </button>

                                        <form action="{{ route('admin.inventories.destroy', $inventory) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('{{ translate('Delete this record?') }}')"
                                                class="btn btn-sm btn-danger">
                                                <i class="tio-delete"></i> {{ translate('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    {{ translate('No records found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer border-0">
                {{ $inventories->links() }}
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="resetPasswordForm" method="POST" action="{{ route('admin.inventories.reset-password') }}">
                @csrf
                <input type="hidden" name="inventory_id" id="inventory_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold" id="resetPasswordModalLabel">{{ translate('Reset Password') }}
                        </h5>
                        <button type="button" class="btn" data-bs-dismiss="modal"
                            aria-label="{{ translate('Close') }}">
                            <i class="tio-clear"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p id="inventoryName" class="fw-semibold mb-3 text-primary"></p>

                        <div class="mb-3">
                            <label class="form-label">{{ translate('New Password') }}</label>
                            <div class="input-group">
                                <input type="text" name="password" id="newPassword" class="form-control"
                                    placeholder="{{ translate('Enter or generate password') }}" required>
                                <button class="btn btn-outline-secondary" type="button" id="generatePasswordBtn">
                                    <i class="tio-auto"></i> {{ translate('Generate') }}
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-info small mb-0">
                            <i class="tio-info"></i>
                            {{ translate('You can manually enter a password or click "Generate" for an auto-generated one.') }}
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border"
                            data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Reset') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Open Reset Password Modal
            $('.resetPasswordBtn').on('click', function() {
                const inventoryId = $(this).data('id');
                const inventoryName = $(this).data('name');

                $('#inventory_id').val(inventoryId);
                $('#inventoryName').text("Reset password for: " + inventoryName);
                $('#newPassword').val('');

                // Bootstrap 5 modal via JS API
                const modalEl = document.getElementById('resetPasswordModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });

            // Generate random password
            $('#generatePasswordBtn').on('click', function() {
                const randomPass = Math.random().toString(36).slice(-10);
                $('#newPassword').val(randomPass);
            });
        });
    </script>
@endsection
