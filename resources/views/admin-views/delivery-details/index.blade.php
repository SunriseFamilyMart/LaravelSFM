@extends('layouts.admin.app')

@section('content')
    <div class="content container-fluid">
        <h2 class="mb-4">{{ isset($status) ? ucfirst($status) . ' Deliveries' : 'All Delivery Trips' }}</h2>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Trip Number</th>
                        <th>Delivery Man</th>
                        <th>Orders</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trips as $trip)
                        @php
                            $orderIds = is_array($trip->order_ids) ? $trip->order_ids : json_decode($trip->order_ids, true);
                        @endphp

                        <tr>
                            <td>{{ $trip->trip_number }}</td>
                            <td>{{ $trip->deliveryMan->f_name ?? 'N/A' }}</td>
                            <td>{{ implode(', ', $orderIds) }}</td>
                            <td><span class="badge badge-info">{{ ucfirst($trip->status) }}</span></td>
                            <td>{{ $trip->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.delivery-details.view', $trip->id) }}" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection