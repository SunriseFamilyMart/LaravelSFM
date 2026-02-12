@extends('layouts.admin.app')

@section('title', translate('UPI Verification'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="mb-0 page-header-title">
                <span class="page-header-icon">
                    <i class="tio-checkmark-circle nav-icon"></i>
                </span>
                <span>{{ translate('UPI Verification') }}</span>
            </h1>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Filters') }}</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.delivery-details.upi-verification') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label>{{ translate('Start Date') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label>{{ translate('End Date') }}</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-3">
                            <label>{{ translate('Delivery Man') }}</label>
                            <select name="delivery_man_id" class="form-control">
                                <option value="all" {{ $deliveryManId == 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                @foreach ($deliveryMen as $dm)
                                    <option value="{{ $dm->id }}" {{ $deliveryManId == $dm->id ? 'selected' : '' }}>
                                        {{ $dm->f_name }} {{ $dm->l_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 align-self-end">
                            <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                            <a href="{{ route('admin.delivery-details.upi-verification') }}" class="btn btn-secondary">{{ translate('Clear') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- UPI Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('UPI Transactions') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('ID') }}</th>
                                <th>{{ translate('Payment Ref') }}</th>
                                <th>{{ translate('Order ID') }}</th>
                                <th>{{ translate('Store') }}</th>
                                <th>{{ translate('Amount') }}</th>
                                <th>{{ translate('Transaction ID') }}</th>
                                <th>{{ translate('Created At') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upiTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->id }}</td>
                                    <td>{{ $transaction->transaction_ref ?? 'N/A' }}</td>
                                    <td>
                                        @if ($transaction->order_id)
                                            <a href="{{ route('admin.orders.details', $transaction->order_id) }}" target="_blank">
                                                #{{ $transaction->order_id }}
                                            </a>
                                        @else
                                            {{ translate('N/A') }}
                                        @endif
                                    </td>
                                    <td>{{ $transaction->store->store_name ?? $transaction->store->customer_name ?? 'N/A' }}</td>
                                    <td>₹{{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ $transaction->transaction_ref ?? 'N/A' }}</td>
                                    <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @if (str_contains($transaction->remarks ?? '', 'verified'))
                                            <span class="badge badge-success">{{ translate('Verified') }}</span>
                                        @elseif (str_contains($transaction->remarks ?? '', 'rejected'))
                                            <span class="badge badge-danger">{{ translate('Rejected') }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ translate('Pending') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!str_contains($transaction->remarks ?? '', 'verified') && !str_contains($transaction->remarks ?? '', 'rejected'))
                                            <button type="button" class="btn btn-sm btn-success" 
                                                data-toggle="modal" 
                                                data-target="#verifyUpiModal"
                                                data-ledger-id="{{ $transaction->id }}"
                                                data-action="verify">
                                                {{ translate('Verify') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                data-toggle="modal" 
                                                data-target="#verifyUpiModal"
                                                data-ledger-id="{{ $transaction->id }}"
                                                data-action="reject">
                                                {{ translate('Reject') }}
                                            </button>
                                        @else
                                            <span class="text-muted">{{ translate('Already processed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">{{ translate('No UPI transactions found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $upiTransactions->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Verify/Reject UPI Modal -->
    <div class="modal fade" id="verifyUpiModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.delivery-details.upi-verification.update') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="verifyUpiModalTitle">{{ translate('Verify UPI Transaction') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="ledger_id" id="upi_ledger_id">
                        <input type="hidden" name="action" id="upi_action">

                        <div class="form-group">
                            <label>{{ translate('Remarks') }}</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="{{ translate('Optional remarks') }}"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <p class="mb-0" id="upi_action_text"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="upi_submit_btn">{{ translate('Confirm') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        $('#verifyUpiModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var ledgerId = button.data('ledger-id');
            var action = button.data('action');

            var modal = $(this);
            modal.find('#upi_ledger_id').val(ledgerId);
            modal.find('#upi_action').val(action);

            if (action === 'verify') {
                modal.find('#verifyUpiModalTitle').text('{{ translate('Verify UPI Transaction') }}');
                modal.find('#upi_action_text').text('{{ translate('Are you sure you want to verify this UPI transaction?') }}');
                modal.find('#upi_submit_btn').removeClass('btn-danger').addClass('btn-success');
            } else {
                modal.find('#verifyUpiModalTitle').text('{{ translate('Reject UPI Transaction') }}');
                modal.find('#upi_action_text').text('{{ translate('Are you sure you want to reject this UPI transaction?') }}');
                modal.find('#upi_submit_btn').removeClass('btn-success').addClass('btn-danger');
            }
        });
    </script>
    @endpush
@endsection
