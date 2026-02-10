@extends('layouts.admin.app')

@section('title', translate('Picking Order Details'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/order.png') }}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('Picking Order Details') }}
                </span>
            </h1>
            <a href="{{ route('admin.picking.index') }}" class="btn btn--primary">
                <i class="tio-back-ui"></i> {{ translate('Back to Pick List') }}
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3 mb-lg-5">
                    <div class="card-header">
                        <h1 class="page-header-title">
                            <span class="mr-3">{{ translate('Order ID') }} #{{ $order['id'] }}</span>
                            <span class="badge badge-soft-{{ $order['order_status'] == 'pending' ? 'warning' : 'info' }} py-2 px-3">
                                {{ translate($order['order_status']) }}
                            </span>
                        </h1>
                        <span>
                            <i class="tio-date-range"></i>
                            {{ date('d M Y H:i', strtotime($order['created_at'])) }}
                        </span>
                    </div>

                    <!-- Order Items -->
                    <div class="card-body">
                        <h5 class="card-title">{{ translate('Order Items') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ translate('Product') }}</th>
                                        <th>{{ translate('Unit') }}</th>
                                        <th class="text-right">{{ translate('Qty') }}</th>
                                        <th class="text-right">{{ translate('Unit Weight') }} (kg)</th>
                                        <th class="text-right">{{ translate('Total Weight') }} (kg)</th>
                                        <th class="text-right">{{ translate('Price') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalWeight = 0;
                                        $totalItems = 0;
                                    @endphp
                                    @foreach ($order->details as $key => $detail)
                                        @php
                                            $product = $detail->product;
                                            $productName = $product ? $product->name : 'Product #' . $detail->product_id;
                                            $unitWeight = $product->weight ?? 0;
                                            $itemTotalWeight = $unitWeight * $detail->quantity;
                                            $totalWeight += $itemTotalWeight;
                                            $totalItems += $detail->quantity;
                                        @endphp
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                <div class="media">
                                                    @if ($product && $product->image)
                                                        @php
                                                            $images = json_decode($product->image, true);
                                                        @endphp
                                                        @if ($images && is_array($images) && count($images) > 0)
                                                            <img class="avatar avatar-sm mr-3"
                                                                src="{{ asset('storage/app/public/product/' . $images[0]) }}"
                                                                onerror="this.src='{{ asset('public/assets/admin/img/160x160/2.png') }}'"
                                                                alt="{{ $productName }}">
                                                        @endif
                                                    @endif
                                                    <div class="media-body">
                                                        <h5 class="text-hover-primary mb-0">{{ $productName }}</h5>
                                                        @if ($detail->variant)
                                                            <small>{{ translate('Variant') }}: {{ $detail->variant }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $detail->unit ?? 'pcs' }}</td>
                                            <td class="text-right">{{ $detail->quantity }}</td>
                                            <td class="text-right">{{ number_format($unitWeight, 2) }}</td>
                                            <td class="text-right">{{ number_format($itemTotalWeight, 2) }}</td>
                                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($detail->price) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" class="text-right"><strong>{{ translate('Totals') }}:</strong></td>
                                        <td class="text-right"><strong>{{ $totalItems }}</strong></td>
                                        <td></td>
                                        <td class="text-right"><strong>{{ number_format($totalWeight, 2) }} kg</strong></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Store Information -->
                @if ($order->store)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">{{ translate('Store Information') }}</h5>
                        </div>
                        <div class="card-body">
                            <h6>{{ $order->store->store_name }}</h6>
                            <div class="mt-2">
                                <strong>{{ translate('Route') }}:</strong> {{ $order->store->route_name ?? translate('N/A') }}<br>
                                <strong>{{ translate('Address') }}:</strong> {{ $order->store->address ?? translate('N/A') }}<br>
                                <strong>{{ translate('Phone') }}:</strong> {{ $order->store->phone_number ?? translate('N/A') }}<br>
                                @if ($order->store->gst_number)
                                    <strong>{{ translate('GST Number') }}:</strong> {{ $order->store->gst_number }}<br>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Delivery Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title">{{ translate('Delivery Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body">
                                @if ($order->branch)
                                    <strong>{{ translate('Branch') }}:</strong> {{ $order->branch->name }}<br>
                                @endif
                                @if ($order->delivery_date)
                                    <strong>{{ translate('Delivery Date') }}:</strong> {{ $order->delivery_date }}<br>
                                @endif
                                @if ($order->time_slot)
                                    <strong>{{ translate('Time Slot') }}:</strong> 
                                    {{ $order->time_slot->start_time ?? '' }} - {{ $order->time_slot->end_time ?? '' }}<br>
                                @endif
                                @if ($order->delivery_man)
                                    <strong>{{ translate('Delivery Man') }}:</strong> 
                                    {{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}<br>
                                @endif
                                @if ($order->order_note)
                                    <hr>
                                    <strong>{{ translate('Delivery Instructions') }}:</strong><br>
                                    <p class="text-muted">{{ $order->order_note }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{ translate('Order Summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-6">{{ translate('Subtotal') }}:</dt>
                            <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::set_symbol($order->order_amount) }}</dd>

                            @if ($order->total_tax_amount > 0)
                                <dt class="col-6">{{ translate('Tax') }}:</dt>
                                <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::set_symbol($order->total_tax_amount) }}</dd>
                            @endif

                            @if ($order->delivery_charge > 0)
                                <dt class="col-6">{{ translate('Delivery Fee') }}:</dt>
                                <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::set_symbol($order->delivery_charge) }}</dd>
                            @endif

                            @if (isset($order->weight_charge_amount) && $order->weight_charge_amount > 0)
                                <dt class="col-6">{{ translate('Weight Charge') }}:</dt>
                                <dd class="col-6 text-right">{{ \App\CentralLogics\Helpers::set_symbol($order->weight_charge_amount) }}</dd>
                            @endif

                            <dt class="col-6"><strong>{{ translate('Total') }}:</strong></dt>
                            <dd class="col-6 text-right">
                                <strong>{{ \App\CentralLogics\Helpers::set_symbol($order->order_amount + $order->total_tax_amount + ($order->delivery_charge ?? 0) + ($order->weight_charge_amount ?? 0)) }}</strong>
                            </dd>
                        </dl>

                        <hr>
                        <div class="text-center">
                            <strong>{{ translate('Payment Method') }}:</strong><br>
                            <span class="badge badge-soft-dark">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
