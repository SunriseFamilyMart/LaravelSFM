<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center border rounded px-3 py-3">
            <div>
                <h5 class="mb-0">{{ translate('Setup Fixed Delivery Charge') }}</h5>
                <p class="mb-0">{{ translate('Setup fixed delivery charge you want to deliver from store') }}</p>
            </div>

            <label class="toggle-switch h--45px toggle-switch-sm rounded">
                <input type="checkbox" class="toggle-switch-input change-delivery-charge-type-{{ $branch->id }}"
                    name="delivery_charge_type"
                    {{ $branch?->delivery_charge_setup?->delivery_charge_type == 'fixed' ? 'checked' : '' }}
                    data-type="fixed" data-branch-id="{{ $branch->id }}" id="toggleFixed-{{ $branch->id }}">
                <span class="toggle-switch-label">
                    <span class="toggle-switch-indicator"></span>
                </span>
            </label>
        </div>
    </div>
</div>

<div class="card mt-4" id="fixedDeliverySection-{{ $branch->id }}">
    <div class="card-body">
        <form action="{{ route('admin.business-settings.store.store-fixed-delivery-charge') }}" method="POST">
            @csrf
            <div class="row">
                <input type="hidden" name="branch_id" id="" value="{{ $branch->id }}">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="fixed_delivery_charge">{{ translate('Fixed Delivery Charge') }}
                            ({{ Helpers::currency_symbol() }})</label>
                        <input type="number" class="form-control" name="fixed_delivery_charge" min="0.1"
                            max="99999999" step="0.1"
                            value="{{ $branch?->delivery_charge_setup?->fixed_delivery_charge }}"
                            id="fixed_delivery_charge" placeholder="Ex: 10" required>
                    </div>
                </div>
            </div>
            <div class="btn--container mt-4 justify-content-end">
                <button type="reset" class="btn btn--reset">{{ translate('reset') }}</button>
                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                    class="btn btn--primary call-demo">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>


<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center border rounded px-3 py-3">
            <div>
                <h5 class="mb-0">{{ translate('Setup Kilometer Wise Delivery Charge') }}</h5>
                <p class="mb-0">
                    {{ translate('Setup delivery charges for per km  and how far you want to deliver from store') }}
                </p>
            </div>

            <label class="toggle-switch h--45px toggle-switch-sm rounded">
                <input type="checkbox" class="toggle-switch-input change-delivery-charge-type-{{ $branch->id }}"
                    name="delivery_charge_type"
                    {{ $branch?->delivery_charge_setup?->delivery_charge_type == 'distance' ? 'checked' : '' }}
                    data-type="distance" data-branch-id="{{ $branch->id }}"
                    id="toggleKilometerWise-{{ $branch->id }}">
                <span class="toggle-switch-label text">
                    <span class="toggle-switch-indicator"></span>
                </span>
            </label>
        </div>
    </div>
</div>

<div class="card mt-4" id="kilometerWiseSection-{{ $branch->id }}">
    <div class="card-body">
        <form action="{{ route('admin.business-settings.store.store-kilometer-wise-delivery-charge') }}"
            method="POST">
            @csrf
            <div class="row">
                <input type="hidden" name="branch_id" id="" value="{{ $branch->id }}">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="per_km_charge">{{ translate('Per KM Delivery Charge') }}
                            ({{ Helpers::currency_symbol() }})</label>
                        <input type="number" class="form-control" name="delivery_charge_per_kilometer" min="1"
                            max="99999999" step="0.1"
                            value="{{ $branch?->delivery_charge_setup?->delivery_charge_per_kilometer }}"
                            id="delivery_charge_per_kilometer" placeholder="Ex: 10" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="min_delivery_charge">{{ translate('Minimum Delivery Charge') }}
                            ({{ Helpers::currency_symbol() }})</label>
                        <input type="number" class="form-control" name="minimum_delivery_charge" min="0"
                            max="99999999" step="0.1"
                            value="{{ $branch?->delivery_charge_setup?->minimum_delivery_charge }}"
                            id="minimum_delivery_charge" placeholder="Ex: 10" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="min_distance_free_delivery">{{ translate('Distance Area for Free Delivery') }}
                            (Km)</label>
                        <input type="number" class="form-control" name="minimum_distance_for_free_delivery"
                            min="0" max="99999999" step="0.01"
                            value="{{ $branch?->delivery_charge_setup?->minimum_distance_for_free_delivery }}"
                            id="minimum_distance_for_free_delivery" placeholder="Ex: 10" required>
                    </div>
                </div>
            </div>
            <div class="btn--container mt-4 justify-content-end">
                <button type="reset" class="btn btn--reset">{{ translate('reset') }}</button>
                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                    class="btn btn--primary call-demo">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>

<?php
$areaCount = $branch->delivery_charge_by_area->count();
?>
<div class="card my-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center border rounded mb-2 px-3 py-3">
            <div>
                <h5 class="mb-0">{{ translate('Setup Area/Zip Code Wise Delivery Charge') }}</h5>
                <p class="mb-0">
                    {{ translate('Create Area/Zip Code wise delivery region and specify the charges for each region') }}
                </p>
            </div>

            <label class="toggle-switch h--45px toggle-switch-sm rounded">
                <input type="checkbox"
                    class="toggle-switch-input  @if ($areaCount > 0) change-delivery-charge-type-{{ $branch->id }} @else change-delivery-charge-to-area-{{ $branch->id }} @endif"
                    name="delivery_charge_type"
                    {{ $branch?->delivery_charge_setup?->delivery_charge_type == 'area' ? 'checked' : '' }}
                    data-type="area" data-branch-id="{{ $branch->id }}" id="toggleAreaWise-{{ $branch->id }}">
                <span class="toggle-switch-label text">
                    <span class="toggle-switch-indicator"></span>
                </span>
            </label>
        </div>

        <div id="areaWiseSection-{{ $branch->id }}">
            <div class="d-flex flex-wrap gap-2 align-items-center my-4">
                <h4 class="mb-0 d-flex align-items-center gap-2">
                    {{ translate('Area/Zip Code List') }}
                </h4>
                <span
                    class="badge badge-soft-dark rounded-circle fz-12">{{ $branch->delivery_charge_by_area->count() }}</span>
            </div>

            <div class="row gx-2 gx-lg-3">
                <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                    <div class="card">
                        <div class="card-top px-card pt-4">
                            <div
                                class="d-flex flex-column flex-md-row flex-wrap gap-3 justify-content-md-between align-items-md-center">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="search"
                                            class="form-control min-width-300px"
                                            placeholder="{{ translate('Search by area name or zip code') }}"
                                            aria-label="Search" value="{{ request()->input('search') }}"
                                            autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn--primary">
                                                {{ translate('Search') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="d-flex flex-wrap justify-content-md-end gap-3">
                                    <div>
                                        <a type="button" class="btn btn-outline-primary text-nowrap"
                                            href="{{ route('admin.business-settings.store.export-area-delivery-charge', [$branch->id]) . (request('search') ? '?search=' . request('search') : '') }}">
                                            <i class="tio-upload"></i>
                                            {{ translate('Export') }}
                                        </a>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary text-nowrap"
                                            data-toggle="modal"
                                            data-target="#importConfirmModal-{{ $branch->id }}">
                                            <i class="tio-download-to"></i>
                                            {{ translate('Import') }}
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn--primary" data-toggle="modal"
                                        data-target="#addAreaModal-{{ $branch->id }}"
                                        data-id="{{ $branch->id }}">
                                        <i class="tio-add"></i>
                                        {{ translate('Add Area/Zip Code') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="py-4">
                            <div class="table-responsive datatable-custom">
                                <table
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{ translate('SL') }}</th>
                                            <th>{{ translate('Area Name/Zip Code') }}</th>
                                            <th>{{ translate('Delivery Charge') }} ({{ Helpers::currency_symbol() }})
                                            </th>
                                            <th class="text-center">{{ translate('action') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody id="set-rows">
                                        @forelse($branch->delivery_charge_by_area as $key => $deliveryArea)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $deliveryArea->area_name }}</td>
                                                <td>{{ Helpers::set_symbol($deliveryArea->delivery_charge) }}</td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-3">
                                                        <a class="btn btn-outline-info btn-sm edit square-btn edit-area"
                                                            data-toggle="modal"
                                                            data-target="#editDeliveryChargeModal-{{ $branch->id }}"
                                                            data-id="{{ $deliveryArea->id }}" href="#">
                                                            <i class="tio-edit"></i>
                                                        </a>
                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm delete square-btn form-alert"
                                                            data-id="area-{{ $deliveryArea->id }}"
                                                            data-message="{{ translate('Want to remove this Area') }}?">
                                                            <i class="tio-delete"></i>
                                                        </button>
                                                        <form
                                                            action="{{ route('admin.business-settings.store.delete-area-delivery-charge', [$deliveryArea->id, 'branch_id' => $deliveryArea->branch_id]) }}"
                                                            method="post" id="area-{{ $deliveryArea->id }}">
                                                            @csrf @method('delete')
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <img class="my-4"
                                                        src="{{ asset('public/assets/admin/svg/components/map.svg') }}"
                                                        alt="{{ translate('info icon') }}">
                                                    <h4>{{ translate('Create Area/Zip Code') }}</h4>
                                                    <p>{{ translate('Create area/zip code and setup delivery charge') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <div>
            <form id="weightChargeForm-{{ $branch->id }}"
                action="{{ route('admin.business-settings.store.weight-settings.change-extra-charge-on-weight-status') }}"
                method="POST">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                <input type="hidden" name="status" id="weightChargeStatus-{{ $branch->id }}" value="0">
                <!-- Hidden input to store status -->

                <div class="d-flex justify-content-between align-items-center border rounded px-3 py-3">
                    <div>
                        <h5 class="mb-0">{{ translate('Add Extra Delivery Charge on Weight') }}</h5>
                        <p class="mb-0">
                            {{ translate('To work properly Weight based delivery charge need to update your product weight from ') }}
                            <a class="text-decoration-underline" href="{{ route('admin.product.list') }}"
                                target="_blank">{{ translate('Product List') }}</a>
                        </p>
                    </div>
                    <label class="toggle-switch h--45px toggle-switch-sm rounded"
                        for="toggleWeightCharge-{{ $branch->id }}">
                        <input type="checkbox" class="toggle-switch-input toggleWeightCharge-{{ $branch->id }}"
                            name="extra_charge_on_weight"
                            {{ $branch?->weight_settings_status?->value == 1 ? 'checked' : '' }}
                            data-branch-id="{{ $branch->id }}" id="toggleWeightCharge-{{ $branch->id }}">
                        <span class="toggle-switch-label">
                            <span class="toggle-switch-indicator"></span>
                        </span>
                    </label>
                </div>
            </form>

        </div>
        <div class="mt-5">
            <div id="weightChargeSection-{{ $branch->id }}"
                style="display : {{ $branch?->weight_settings_status?->value == 0 ? 'none' : 'block' }};">
                <div class="mb-5">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="border rounded p-2 pr-3 d-block cursor-pointer">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        name="weight_charge_type-{{ $branch->id }}"
                                        id="chargePerKg-{{ $branch->id }}" value="unit"
                                        {{ $branch?->weight_charge_type?->value != 'range' ? 'checked' : '' }}>
                                    <span class="form-check-label">
                                        {{ translate('Setup Charge Per Unit') }}
                                        <span>({{ Helpers::get_business_settings('product_weight_unit') }})</span>
                                    </span>
                                </div>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="border rounded p-2 pr-3 d-block cursor-pointer">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        name="weight_charge_type-{{ $branch->id }}"
                                        id="chargeByRange-{{ $branch->id }}" value="range"
                                        {{ $branch?->weight_charge_type?->value == 'range' ? 'checked' : '' }}>
                                    <span class="form-check-label">
                                        {{ translate('Setup Charge by Range') }}
                                        <span>({{ Helpers::get_business_settings('product_weight_unit') }})</span>
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.business-settings.store.weight-settings.store-weight-charge') }}"
                    method="POST">
                    @csrf
                    <div id="perKgSection-{{ $branch->id }}">
                        <div class="row mb-3">
                            <input type="hidden" name="branch_id" id="" value="{{ $branch->id }}">
                            <input type="hidden" name="weight_charge_type" value="unit">
                            <div class="col-md-6">
                                <label for="countFrom" class="form-label">{{ translate('Count charge from') }}
                                    <span>({{ Helpers::get_business_settings('product_weight_unit') }})</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="countFrom"
                                        name="count_charge_from" step="0.001" min="0"
                                        value="{{ collect($branch->weight_unit)->firstWhere('key', 'count_charge_from')['value'] ?? '' }}"
                                        placeholder="Ex: 5kg" required>
                                    @php
                                        $selectedOperation =
                                            collect($branch->weight_unit)->firstWhere(
                                                'key',
                                                'count_charge_from_operation',
                                            )['value'] ?? '';
                                    @endphp
                                    <div class="">
                                        <div class="input-group">
                                            <span></span>
                                            <select class="form-control" id="minWeight"
                                                name="count_charge_from_operation">
                                                <option value="greater_or_equal"
                                                    {{ $selectedOperation == 'greater_or_equal' ? 'selected' : '' }}>
                                                    {{ translate('Greater or Equal') }}</option>
                                                <option value="greater"
                                                    {{ $selectedOperation == 'greater' ? 'selected' : '' }}>
                                                    {{ translate('Greater') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="additionalCharge" class="form-label">
                                    {{ translate('Additional charge per') }}
                                    {{ Helpers::get_business_settings('product_weight_unit') }}
                                    ({{ Helpers::currency_symbol() }})</label>
                                <input type="number" class="form-control" id="additionalCharge"
                                    name="additional_charge_per_unit" step="0.001"
                                    value="{{ collect($branch->weight_unit)->firstWhere('key', 'additional_charge_per_unit')['value'] ?? '' }}"
                                    placeholder="Ex: $1.5" min="0" required>
                            </div>
                        </div>
                        <div class="btn--container mt-4 justify-content-end">
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </div>
                    </div>
                </form>

                <form action="{{ route('admin.business-settings.store.weight-settings.store-weight-charge') }}"
                    method="POST">
                    @csrf
                    <div id="weightRangeSection-{{ $branch->id }}" style="display:none;">
                        <input type="hidden" name="branch_id" id="" value="{{ $branch->id }}">
                        <input type="hidden" name="weight_charge_type" value="range">
                        <div class="row g-3">
                            <div class="col-lg-9">
                                <div id="weightRows-{{ $branch->id }}">
                                    @php
                                        $weightRanges = $branch->weight_range
                                            ? json_decode($branch->weight_range->value, true)
                                            : [];
                                    @endphp

                                    @forelse($weightRanges as $index => $range)
                                        <div class="weight-range-item weight-row-{{ $branch->id }}">
                                            <div class="weight-rows">
                                                <div class="row g-3">
                                                    <div class="col-md-4 col-sm-6">
                                                        <label for="minWeight"
                                                            class="form-label">{{ translate('Min Weight') }}
                                                            ({{ Helpers::get_business_settings('product_weight_unit') }})
                                                        </label>
                                                        <input type="hidden" value="{{ $range['min_operation'] }}"
                                                            name="min_operation[]">
                                                        <div class="input-group">
                                                            <select class="form-control" id="minWeight"
                                                                name="min_operation[]"
                                                                @if ($index > 0) disabled @endif>
                                                                <option value="greater_or_equal"
                                                                    {{ $range['min_operation'] == 'greater_or_equal' ? 'selected' : '' }}>
                                                                    {{ translate('Greater or Equal') }}</option>
                                                                <option value="greater"
                                                                    {{ $range['min_operation'] == 'greater' ? 'selected' : '' }}>
                                                                    {{ translate('Greater') }}</option>
                                                            </select>
                                                            <input type="number" class="form-control"
                                                                name="min_weight[]" placeholder="Ex: X kg"
                                                                min="0" step="0.01"
                                                                value="{{ $range['min_weight'] }}" required
                                                                @if ($index > 0) readonly @endif>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-sm-6">
                                                        <label for="maxWeight"
                                                            class="form-label">{{ translate('Max Weight') }}
                                                            ({{ Helpers::get_business_settings('product_weight_unit') }})</label>
                                                        <div class="input-group">
                                                            <select class="form-control" name="max_operation[]"
                                                                id="maxWeight">
                                                                <option value="less_or_equal"
                                                                    {{ $range['max_operation'] == 'less_or_equal' ? 'selected' : '' }}>
                                                                    {{ translate('Less or Equal') }}</option>
                                                                <option value="less"
                                                                    {{ $range['max_operation'] == 'less' ? 'selected' : '' }}>
                                                                    {{ translate('Less') }}</option>
                                                            </select>
                                                            <input type="number" class="form-control"
                                                                name="max_weight[]" placeholder="Ex: X kg"
                                                                min="0" step="0.01"
                                                                value="{{ $range['max_weight'] }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="deliveryCharge"
                                                            class="form-label">{{ translate('Delivery Charge') }}
                                                            ({{ Helpers::currency_symbol() }})</label>
                                                        <input type="number" class="form-control"
                                                            id="deliveryCharge" name="delivery_charge[]"
                                                            placeholder="Ex: $5.00" min="0" step="0.01"
                                                            value="{{ $range['delivery_charge'] }}" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="btn-grp">
                                                <label class="form-label d-none d-sm-block">&nbsp;</label>
                                                <div
                                                    class="d-flex align-items-end gap-10px pt-2 justify-content-end justify-content-sm-start">
                                                    <button type="button"
                                                        class="btn btn-success add-row-{{ $branch->id }}"><i
                                                            class="tio-add"></i></button>
                                                    <button type="button"
                                                        class="btn btn-soft-danger remove-row-{{ $branch->id }}"><i
                                                            class="tio-clear"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="weight-range-item weight-row-{{ $branch->id }}">
                                            <div class="weight-rows">
                                                <div class="row g-3">
                                                    <div class="col-md-4 col-sm-6">
                                                        <label for="minWeight"
                                                            class="form-label">{{ translate('Min Weight') }}
                                                            ({{ Helpers::get_business_settings('product_weight_unit') }})</label>
                                                        <div class="input-group">
                                                            <select class="form-control" id="minWeight"
                                                                name="min_operation[]">
                                                                <option value="greater_or_equal">
                                                                    {{ translate('Greater or Equal') }}</option>
                                                                <option value="greater">{{ translate('Greater') }}
                                                                </option>
                                                            </select>
                                                            <input type="number" class="form-control"
                                                                name="min_weight[]" min="0" step="0.01"
                                                                placeholder="Ex: X kg" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-sm-6">
                                                        <label for="maxWeight"
                                                            class="form-label">{{ translate('Max Weight') }}
                                                            ({{ Helpers::get_business_settings('product_weight_unit') }})</label>
                                                        <div class="input-group">
                                                            <select class="form-control" name="max_operation[]"
                                                                id="maxWeight">
                                                                <option value="less_or_equal">
                                                                    {{ translate('Less or Equal') }}</option>
                                                                <option value="less">{{ translate('Less') }}
                                                                </option>
                                                            </select>
                                                            <input type="number" class="form-control"
                                                                name="max_weight[]" min="0" step="0.01"
                                                                placeholder="Ex: X kg" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="deliveryCharge"
                                                            class="form-label">{{ translate('Delivery Charge') }}
                                                            ({{ Helpers::currency_symbol() }})</label>
                                                        <input type="number" class="form-control"
                                                            id="deliveryCharge" name="delivery_charge[]"
                                                            min="0" step="0.01" placeholder="Ex: $5.00"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="btn-grp">
                                                <label class="form-label d-none d-sm-block">&nbsp;</label>
                                                <div
                                                    class="d-flex align-items-end gap-10px pt-2 justify-content-end justify-content-sm-start">
                                                    <button type="button"
                                                        class="btn btn-success add-row-{{ $branch->id }}"><i
                                                            class="tio-add"></i></button>
                                                    <button type="button"
                                                        class="btn btn-soft-danger remove-row-{{ $branch->id }}"><i
                                                            class="tio-clear"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card h-100 shadow-none bg-soft-secondary fs-12">
                                    <div class="card-body">
                                        <h4>{{ translate('Rules') }}
                                            <i class="tio-info-outined" data-toggle="tooltip" data-placement="top"
                                                title="{{ translate('Extra delivery charges based on weight will be 0 if the products weight are not within the following weight ranges.') }}">
                                            </i>
                                        </h4>
                                        <ul class="pl-4" id="weight-range-rules-{{ $branch->id }}">
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="btn--container mt-4 justify-content-end">
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="confirmChangeModalToArea-{{ $branch->id }}" tabindex="-1" role="dialog"
    aria-labelledby="confirmChangeModalToArea" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img src="{{ asset('public/assets/admin/svg/components/info.svg') }}"
                        alt="{{ translate('image') }}" class="mb-4">
                    <h4>{{ translate('Are You Sure') }}?</h4>
                    <p>{{ translate('Do you want to change the delivery charge setup? You can only use one setup at a time. When you switch to a new setup, the previous one is automatically deactivated.') }}
                    </p>

                    <p>{{ translate('You must create at least one Zipcode & Its Delivery Charge to enable this delivery charge option.') }}
                    </p>

                </div>

                <div class="text-center mb-4">
                    <h5 id="addAreaModalLabel">{{ translate('Add New Area/Zip Code & Delivery Charge') }}</h5>
                </div>
                <div class="my-2">
                    <form
                        action="{{ route('admin.business-settings.store.store-delivery-wise-delivery-charge', ['change_status' => 1]) }}"
                        method="POST">
                        @csrf
                        <div class="row bg-soft-secondary py-3">
                            <input type="hidden" name="branch_id" class="branchIdInput"
                                id="branchIdInput-{{ $branch->id }}" value="{{ $branch->id }}">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="areaName">{{ translate('Zip Code / Area Name') }}</label>
                                    <input type="text" class="form-control" id="areaName" name="area_name"
                                        min="0" max="99999999" step="0.1" placeholder="Ex: 1216"
                                        maxlength="255" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="deliveryCharge">{{ translate('Delivery Charge') }}
                                        ({{ Helpers::currency_symbol() }})</label>
                                    <input type="number" class="form-control" id="deliveryCharge"
                                        name="delivery_charge" min="0.01" step="0.01" placeholder="Ex: $20"
                                        required>
                                </div>
                            </div>
                        </div>

                        <div class="btn--container mt-4 justify-content-center">
                            <button type="reset" class="btn btn--reset"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="editDeliveryChargeModal-{{ $branch->id }}" tabindex="-1"
    aria-labelledby="editDeliveryChargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="text-center mb-4">
                    <h5 id="editDeliveryChargeModalLabel">
                        {{ translate('Edit Area Name/Zip Code & Delivery Charge') }}</h5>
                </div>
                <div class="my-2">
                    <form id="editDeliveryChargeForm-{{ $branch->id }}" method="POST">
                        @csrf
                        <div class="row bg-soft-secondary py-3">
                            <div class="col-md-6">
                                <label for="areaName"
                                    class="form-label">{{ translate('Zip Code / Area Name') }}</label>
                                <input type="text" class="form-control" id="areaName-{{ $branch->id }}"
                                    name="area_name" placeholder="Enter area name or zip code">
                            </div>
                            <div class="col-md-6">
                                <label for="deliveryCharge"
                                    class="form-label">{{ translate('Delivery Charge') }}({{ Helpers::currency_symbol() }})</label>
                                <input type="number" class="form-control" id="deliveryCharge-{{ $branch->id }}"
                                    name="delivery_charge" min="0.01" step="0.01"
                                    placeholder="Enter delivery charge">
                            </div>
                        </div>
                        <div class="btn--container mt-4 justify-content-center">
                            <button type="reset" class="btn btn--reset"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                class="btn btn--primary call-demo"
                                id="saveChangesButton">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addAreaModal-{{ $branch->id }}" tabindex="-1" role="dialog"
    aria-labelledby="addAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h5 id="addAreaModalLabel">{{ translate('Add New Area/Zip Code & Delivery Charge') }}</h5>
                </div>
                <div class="my-2">
                    <form action="{{ route('admin.business-settings.store.store-delivery-wise-delivery-charge') }}"
                        method="POST">
                        @csrf
                        <div class="row bg-soft-secondary py-3">
                            <input type="hidden" name="branch_id" class="branchIdInput"
                                id="branchIdInput-{{ $branch->id }}">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="areaName">{{ translate('Zip Code / Area Name') }}</label>
                                    <input type="text" class="form-control" id="areaName" name="area_name"
                                        placeholder="Ex: 1216" maxlength="255" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="deliveryCharge">{{ translate('Delivery Charge') }}
                                        ({{ Helpers::currency_symbol() }})</label>
                                    <input type="number" class="form-control" id="deliveryCharge"
                                        name="delivery_charge" placeholder="Ex: $20" min="0" max="99999999"
                                        step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="btn--container mt-4 justify-content-center">
                            <button type="reset" class="btn btn--reset"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                class="btn btn--primary call-demo">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- import Modal -->
<div class="modal fade" id="importConfirmModal-{{ $branch->id }}" tabindex="-1" role="dialog"
    aria-labelledby="confirmChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="importForm-{{ $branch->id }}">
                @csrf
                <div class="modal-body">
                    <div class="text-center my-4">
                        <img src="{{ asset('public/assets/admin/svg/components/file.svg') }}"
                            alt="{{ translate('image') }}" class="mb-4">
                        <h4>{{ translate('Add New or Replace in the List') }}</h4>
                        <p>{{ translate('You can download the example file to understand how the file must be filled with proper data.') }}
                            <a href="{{ asset('public/assets/area_bulk_format.xlsx') }}" download=""
                                class="fz-16 btn-link">
                                {{ translate('Download Format') }}
                            </a>
                        </p>
                        <p>{{ translate('To upload and add new data to your list, click the "Add New" button. To replace the existing list, click the "Replace" button.') }}
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="area_list">{{ translate('Import Area/Zip Code') }}</label>
                        <input type="file" class="form-control" id="area_list-{{ $branch->id }}"
                            name="area_file" accept=".xlsx, .xls" required>
                    </div>
                </div>
                <div class="btn--container mb-4 justify-content-center">
                    <button type="button" data-type="replace"
                        class="btn btn--secondary import-button-{{ $branch->id }}"
                        id="replace-{{ $branch->id }}">{{ translate('Replace') }}</button>
                    <button type="button" data-type="new"
                        class="btn btn--primary import-button-{{ $branch->id }}"
                        id="addNew-{{ $branch->id }}">{{ translate('Add New') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Turning On a New Setup -->
<div class="modal fade" id="confirmChangeModal-{{ $branch->id }}" tabindex="-1" role="dialog"
    aria-labelledby="confirmChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ asset('public/assets/admin/svg/components/info.svg') }}"
                    alt="{{ translate('image') }}" class="mb-4">
                <h4>{{ translate('Are You Sure') }}?</h4>
                <p>{{ translate('Do you want to change the delivery charge setup? You can only use one setup at a time. When you switch to a new setup, the previous one is automatically deactivated.') }}
                </p>
            </div>
            <div class="btn--container mb-4 justify-content-center">
                <button type="button" class="btn btn--secondary"
                    id="cancelChange-{{ $branch->id }}">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn--primary"
                    id="confirmChange-{{ $branch->id }}">{{ translate('Yes') }},
                    {{ translate('Change') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Choosing New Setup When Turning Off -->
<div class="modal fade" id="deactivationModal-{{ $branch->id }}" tabindex="-1" role="dialog"
    aria-labelledby="deactivationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img src="{{ asset('public/assets/admin/svg/components/info.svg') }}"
                        alt="{{ translate('image') }}" class="mb-4">
                    <h4>{{ translate('To Turn Off, Select an Option Below.') }}</h4>
                    <p>{{ translate('If you want to turn off all setup, you need to choose one of the options below and continue. Without this, the delivery charge can’t work.') }}
                    </p>
                </div>
                <!-- Delivery charge options -->
                <div class="bg-soft-secondary mx-6 p-3">
                    <div id="option-fixed-{{ $branch->id }}" class="delivery-option mt-2">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="option-fixed-radio-{{ $branch->id }}"
                                name="new_delivery_charge_type" value="fixed" class="custom-control-input">
                            <label class="custom-control-label"
                                for="option-fixed-radio-{{ $branch->id }}">{{ translate('Fixed Delivery Charge Setup') }}</label>
                        </div>
                    </div>

                    <div id="option-distance-{{ $branch->id }}" class="delivery-option mt-2">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="option-distance-radio-{{ $branch->id }}"
                                name="new_delivery_charge_type" value="distance" class="custom-control-input">
                            <label class="custom-control-label"
                                for="option-distance-radio-{{ $branch->id }}">{{ translate('Kilometer Wise Delivery Charge Setup') }}</label>
                        </div>
                    </div>

                    <div id="option-area-{{ $branch->id }}" class="delivery-option mt-2">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="option-area-radio-{{ $branch->id }}"
                                name="new_delivery_charge_type" value="area" class="custom-control-input">
                            <label class="custom-control-label"
                                for="option-area-radio-{{ $branch->id }}">{{ translate('Area/Zip Code Wise Delivery Charge Setup') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn--container mb-4 justify-content-center">
                <button type="button" class="btn btn--secondary"
                    id="cancelDeactivation-{{ $branch->id }}">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn--primary"
                    id="confirmDeactivation-{{ $branch->id }}">{{ translate('Continue') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- change Weight status Modal -->
<div class="modal fade" id="weightChargeModal-{{ $branch->id }}" tabindex="-1" role="dialog"
    aria-labelledby="weightChargeModalLabel-{{ $branch->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ asset('public/assets/admin/svg/components/info.svg') }}"
                    alt="{{ translate('image') }}" class="mb-4">
                <h4>{{ translate('Need to Update Product Weight') }}</h4>
                <p>{{ translate('To apply a delivery charge based on weight, update the product weight in the product details. Without this, the delivery charge will not work correctly.') }}
                </p>
            </div>

            <div class="btn--container mb-4 justify-content-center">
                <button type="button" class="btn btn--secondary"
                    data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn--primary"
                    id="confirmWeightCharge-{{ $branch->id }}">{{ translate('Yes') }},
                    {{ translate('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>

@push('script_2')
    <script>
        $(document).ready(function() {
            $('#kilometerWiseSection-{{ $branch->id }}').toggle($('#toggleKilometerWise-{{ $branch->id }}')
                .is(':checked'));
            $('#toggleKilometerWise-{{ $branch->id }}').change(function() {
                $('#kilometerWiseSection-{{ $branch->id }}').toggle(this.checked);
            });

            $('#areaWiseSection-{{ $branch->id }}').toggle($('#toggleAreaWise-{{ $branch->id }}').is(
                ':checked'));
            $('#toggleAreaWise-{{ $branch->id }}').change(function() {
                $('#areaWiseSection-{{ $branch->id }}').toggle(this.checked);
            });

            $('#fixedDeliverySection-{{ $branch->id }}').toggle($('#toggleFixed-{{ $branch->id }}').is(
                ':checked'));
            $('#toggleFixed-{{ $branch->id }}').change(function() {
                $('#fixedDeliverySection-{{ $branch->id }}').toggle(this.checked);
            });
        });

        $(document).ready(function() {
            $('[data-toggle="modal"]').on('click', function() {
                var branchId = $(this).data('id');
                $('#addAreaModal-' + branchId).on('shown.bs.modal', function() {
                    $(this).find('.branchIdInput').val(branchId);
                });
            });
        });

        $(document).ready(function() {
            $('.edit-area').on('click', function() {
                var deliveryAreaId = $(this).data('id');
                var branchId = $(this).data('target').split('-').pop();

                var actionUrl = '/admin/business-settings/store/update-area-delivery-charge/' +
                    deliveryAreaId;
                $('#editDeliveryChargeForm-' + branchId).attr('action', actionUrl);

                $.ajax({
                    url: '/admin/business-settings/store/edit-area-delivery-charge/' +
                        deliveryAreaId,
                    method: 'GET',
                    success: function(data) {
                        $('#areaName-' + branchId).val(data.area_name);
                        $('#deliveryCharge-' + branchId).val(data.delivery_charge);
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            });
        });

        $(document).ready(function() {
            let checkbox = null;
            let previousState = null;

            function showDeactivationModal() {
                const currentType = checkbox.data('type');

                $('.delivery-option').hide();
                $('input[name="new_delivery_charge_type"]').prop('checked', false);

                if (currentType === 'fixed') {
                    $('#option-distance-{{ $branch->id }}, #option-area-{{ $branch->id }}').show();
                    $('#option-distance-radio-{{ $branch->id }}').prop('checked', true);
                } else if (currentType === 'distance') {
                    $('#option-fixed-{{ $branch->id }}, #option-area-{{ $branch->id }}').show();
                    $('#option-fixed-radio-{{ $branch->id }}').prop('checked', true);
                } else if (currentType === 'area') {
                    $('#option-fixed-{{ $branch->id }}, #option-distance-{{ $branch->id }}').show();
                    $('#option-fixed-radio-{{ $branch->id }}').prop('checked', true);
                }

                $('#deactivationModal-{{ $branch->id }}').modal('show');
            }

            function confirmDeactivation() {
                if (checkbox) {
                    let deliveryChargeType = checkbox.data('type');
                    let branchId = checkbox.data('branch-id');
                    let status = 0;

                    // Get the selected new delivery charge type
                    let newDeliveryChargeType = $('input[name="new_delivery_charge_type"]:checked').val();

                    $.ajax({
                        url: "{{ route('admin.business-settings.store.change-delivery-charge-type') }}",
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            delivery_charge_type: deliveryChargeType,
                            branch_id: branchId,
                            status: status,
                            new_delivery_charge_type: newDeliveryChargeType
                        },
                        success: function(response) {
                            if (response.status != false) {
                                toastr.success(response.message);
                                location.reload();
                            } else {
                                toastr.error(response.error);
                                checkbox.prop('checked', previousState);
                            }
                        },
                        error: function() {
                            checkbox.prop('checked', previousState);
                        }
                    });
                }
            }

            $('.change-delivery-charge-type-{{ $branch->id }}').change(function() {
                checkbox = $(this);
                previousState = checkbox.is(':checked');

                if (!previousState) {
                    showDeactivationModal();
                } else {
                    // Show the existing modal for activation
                    $('#confirmChangeModal-{{ $branch->id }}').modal('show');
                }
            });

            $('#confirmChange-{{ $branch->id }}').click(function() {
                if (checkbox) {
                    let deliveryChargeType = checkbox.data('type');
                    let branchId = checkbox.data('branch-id');
                    let status = 1; // We're activating a new type

                    $.ajax({
                        url: "{{ route('admin.business-settings.store.change-delivery-charge-type') }}",
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            delivery_charge_type: deliveryChargeType,
                            branch_id: branchId,
                            status: status
                        },
                        success: function(response) {
                            console.log(response)
                            if (response.status != false) {
                                toastr.success(response.message);
                                location.reload();
                            } else {
                                toastr.error(response.error);
                                checkbox.prop('checked', previousState);
                            }
                        },
                        error: function() {
                            checkbox.prop('checked', previousState);
                        }
                    });
                }
            });

            $('#confirmDeactivation-{{ $branch->id }}').click(function() {
                confirmDeactivation();
            });

            $('#cancelChange-{{ $branch->id }}').click(function() {
                if (checkbox) {
                    checkbox.prop('checked', previousState);
                }
                $('#confirmChangeModal-{{ $branch->id }}').modal('hide');
            });

            $('#cancelDeactivation-{{ $branch->id }}').click(function() {
                if (checkbox) {
                    checkbox.prop('checked', previousState);
                }
                $('#deactivationModal-{{ $branch->id }}').modal('hide');
            });

            $('#deactivationModal-{{ $branch->id }}, #confirmChangeModal-{{ $branch->id }}').on(
                'hidden.modal',
                function() {
                    if (checkbox) {
                        checkbox.prop('checked', previousState);
                    }
                });

            $('.change-delivery-charge-to-area-{{ $branch->id }}').change(function() {
                checkbox = $(this);
                previousState = checkbox.is(':checked');

                if (!previousState) {
                    showDeactivationModal();
                } else {
                    $('#confirmChangeModalToArea-{{ $branch->id }}').modal('show');
                }

                $('#confirmChangeModalToArea-{{ $branch->id }}').on('hidden.bs.modal', function() {
                    if (checkbox) {
                        checkbox.prop('checked', !previousState);
                    }
                });

            });
        });

        $(document).ready(function() {
            $('.import-button-{{ $branch->id }}').on('click', function() {
                var branchId = {{ $branch->id }};
                var type = $(this).data('type');
                var form = $('#importForm-' + branchId)[0];
                var formData = new FormData(form);
                formData.append('type', type);

                $.ajax({
                    url: '/admin/business-settings/store/import-area-delivery-charge/' + branchId,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name=_token]').val()
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            toastr.success(response.message);
                            location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        let response = JSON.parse(xhr.responseText);
                        toastr.error(response.message);
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Toggle visibility of weight charge section based on checkbox status
            $('#toggleWeightCharge-{{ $branch->id }}').change(function() {
                $('#weightChargeSection-{{ $branch->id }}').toggle(this.checked);
            });

            // Toggle between Per Kg and Weight Range sections
            $('input[name="weight_charge_type-{{ $branch->id }}"]').change(function() {
                if ($('#chargePerKg-{{ $branch->id }}').is(':checked')) {
                    $('#perKgSection-{{ $branch->id }}').show();
                    $('#weightRangeSection-{{ $branch->id }}').hide();
                } else if ($('#chargeByRange-{{ $branch->id }}').is(':checked')) {
                    $('#perKgSection-{{ $branch->id }}').hide();
                    $('#weightRangeSection-{{ $branch->id }}').show();
                }
            });
            $('input[name="weight_charge_type-{{ $branch->id }}"]').trigger('change');

            // Handle showing the modal or directly submitting the form
            $('#toggleWeightCharge-{{ $branch->id }}').change(function() {
                if (this.checked) {
                    $('#weightChargeModal-{{ $branch->id }}').modal('show');
                } else {
                    $('#weightChargeStatus-{{ $branch->id }}').val(0);
                    $('#weightChargeForm-{{ $branch->id }}').submit();
                }
            });

            // Confirm action in the modal
            $('#confirmWeightCharge-{{ $branch->id }}').click(function() {
                $('#weightChargeStatus-{{ $branch->id }}').val(1);
                $('#weightChargeForm-{{ $branch->id }}').submit();
            });

            // Handle modal close without confirming
            $('#weightChargeModal-{{ $branch->id }}').on('hidden.bs.modal', function() {
                if (!$('#weightChargeStatus-{{ $branch->id }}').val()) {
                    $('#toggleWeightCharge-{{ $branch->id }}').prop('checked', false);
                }
            });
        });


        $(document).ready(function() {

            function updateRules() {
                const rows = $('#weightRows-{{ $branch->id }} .weight-row-{{ $branch->id }}');
                const totalRows = rows.length;

                const rulesList = $('#weight-range-rules-{{ $branch->id }}');

                let symbol = '{{ Helpers::currency_symbol() }}';
                let weightUnit = '{{ Helpers::get_business_settings('product_weight_unit') }}';

                rulesList.empty();
                if (totalRows > 0) {
                    rows.each(function(index) {
                        const minWeight = $(this).find('input[name="min_weight[]"]').val();
                        const minOperation = $(this).find('select[name="min_operation[]"]').val();
                        const maxWeight = $(this).find('input[name="max_weight[]"]').val();
                        const maxOperation = $(this).find('select[name="max_operation[]"]').val();

                        const deliveryCharge = $(this).find('input[name="delivery_charge[]"]').val();
                        let rule = '';

                        if (minOperation === 'greater_or_equal') {
                            rule += minWeight + ' ' + weightUnit;
                            rule += ' >= weight ';
                        } else if (minOperation === 'greater') {
                            rule += minWeight + ' ' + weightUnit;
                            rule += ' > weight ';
                        }

                        if (maxOperation === 'less_or_equal') {
                            rule += '<= ' + maxWeight + ' ' + weightUnit;
                        } else if (maxOperation === 'less') {
                            rule += '< ' + maxWeight + ' ' + weightUnit;
                        }

                        rule += ' = ' + deliveryCharge + ' ' + symbol;

                        rulesList.append('<li>' + rule + '</li>');
                    });
                } else {
                    rulesList.append('<li>{{ translate('No rules added yet') }}</li>');
                }
            }

            $('input[name="delivery_charge[]"], input[name="max_weight[]"], input[name="min_weight[]"]').on('input',
                function() {
                    updateRules()
                    updateMinMaxWeight()
                });

            $('select[name="max_operation[]"], select[name="min_operation[]"]').on('change', function() {
                updateRules()
                updateMinMaxWeight()
            });

            function updateButtons() {
                const rows = $('#weightRows-{{ $branch->id }} .weight-row-{{ $branch->id }}');
                const totalRows = rows.length;

                // Hide all add and remove buttons first
                rows.find('.add-row-{{ $branch->id }}').hide();
                rows.find('.remove-row-{{ $branch->id }}').hide();

                if (totalRows >= 0) {
                    rows.each(function(index) {
                        const addButton = $(this).find('.add-row-{{ $branch->id }}');
                        const removeButton = $(this).find('.remove-row-{{ $branch->id }}');

                        addButton.show();
                        removeButton.show();

                        if (index == 0) {
                            removeButton.hide()
                            totalRows > 1 && addButton.hide()
                        } else if (index === totalRows - 1) {
                            addButton.show();
                            removeButton.show();
                        } else {
                            addButton.hide();
                            removeButton.hide();
                        }

                    });
                } else {
                    rows.find('.add-row-{{ $branch->id }}').show();
                }
                updateRules()
                updateMinMaxWeight();
            }

            // Initialize buttons on document ready
            updateButtons();

            // Adjust Min max value when overall data updating
            function updateMinMaxWeight() {
                const rows = $('#weightRows-{{ $branch->id }} .weight-row-{{ $branch->id }}');
                const totalRows = rows.length;

                if (totalRows > 0) {
                    rows.each(function(index) {
                        const minWeight = $(this).find('input[name="min_weight[]"]').val();
                        const maxWeight = $(this).find('input[name="max_weight[]"]').val();

                        if (index > 0) {
                            const previousRow = $(this).prev();
                            if (previousRow) {
                                const previousMaxWeight = previousRow.find('input[name="max_weight[]"]')
                                    .val();
                                const previousMaxOperation = previousRow.find(
                                    'select[name="max_operation[]"]').val();

                                $(this).find('input[name="min_weight[]"]').val(previousMaxWeight)
                                $(this).find('input[name="max_weight[]"]').attr('min', previousMaxWeight)

                                if (previousMaxOperation === 'less') {
                                    $(this).find('select[name="min_operation[]"]').val('greater_or_equal')
                                        .prop('disabled', true).append(
                                            '<input type="hidden" name="min_operation[]" value="greater_or_equal">'
                                        );
                                } else if (previousMaxOperation === 'less_or_equal') {
                                    $(this).find('select[name="min_operation[]"]').val('greater').prop(
                                        'disabled', true).append(
                                        '<input type="hidden" name="min_operation[]" value="greater">');
                                }
                            }
                        } else {
                            // $(this).find('select[name="min_operation[]"]').val('greater_or_equal').prop('disabled', true).append('<input type="hidden" name="min_operation[]" value="greater_or_equal">');
                        }
                    });
                }
            }

            // Bind the add row event
            $('#weightRows-{{ $branch->id }}').on('click', '.add-row-{{ $branch->id }}', function() {
                // Target the last row in the container to use as a reference for cloning
                var lastRow = $('#weightRows-{{ $branch->id }} .weight-row-{{ $branch->id }}:last');
                var newRow = lastRow.clone(true);

                // Get the previous (last) row's max weight and operation
                var previousRowMaxWeight = lastRow.find('input[name="max_weight[]"]').val();
                var previousRowMaxOperation = lastRow.find('select[name="max_operation[]"]').val();
                var previousRowMinWeight = lastRow.find('input[name="min_weight[]"]').val();



                if (parseFloat(previousRowMaxWeight) <= 0 || parseFloat(previousRowMinWeight) < 0) {
                    Swal.fire({
                        type: 'warning',
                        title: '{{ translate('Weight values must be positive') }}!',
                        text: '{{ translate('Please enter positive values for Min Weight and Max Weight.') }}',
                        confirmButtonText: '{{ translate('OK') }}',
                        confirmButtonColor: '#544c30',
                    });
                    if (parseFloat(previousRowMinWeight) <= 0) {
                        lastRow.find('input[name="min_weight[]"]').val('');
                    }
                    if (parseFloat(previousRowMaxWeight) <= 0) {
                        lastRow.find('input[name="max_weight[]"]').val('');
                    }
                    return; // Prevent adding a new row if weights are not positive
                }

                if (!previousRowMinWeight) {
                    Swal.fire({
                        type: 'warning',
                        title: '{{ translate('Please enter a value for Min Weight.') }}',
                        text: '{{ translate('Min Weight is required before adding a new row') }}!',
                        confirmButtonText: '{{ translate('OK') }}',
                        confirmButtonColor: '#544c30',
                    });
                    return; // Prevent adding a new row if max weight is not provided
                }

                // Check if max weight is provided
                if (!previousRowMaxWeight) {
                    Swal.fire({
                        type: 'warning',
                        title: '{{ translate('Please enter a value for Max Weight.') }}',
                        text: '{{ translate('Max Weight is required before adding a new row') }}!',
                        confirmButtonText: '{{ translate('OK') }}',
                        confirmButtonColor: '#544c30',
                    });
                    return; // Prevent adding a new row if max weight is not provided
                }

                if (parseFloat(previousRowMaxWeight) <= parseFloat(previousRowMinWeight)) {
                    Swal.fire({
                        type: 'warning',
                        title: '{{ translate('Max Weight must be greater than Min Weight') }}!',
                        text: '{{ translate('Please select at least one login option.') }}',
                        confirmButtonText: '{{ translate('OK') }}',
                        confirmButtonColor: '#544c30',
                    });
                    lastRow.find('input[name="max_weight[]"]').val('');
                    return; // Prevent adding a new row if validation fails
                }

                // Clear inputs for the new row
                newRow.find('input').val('');
                newRow.find('select').prop('selectedIndex', 0);

                // Change button from add to remove for the new row
                // newRow.find('.add-row-{{ $branch->id }}').removeClass('btn-success add-row-{{ $branch->id }}').addClass('btn-danger remove-row-{{ $branch->id }}').text('-');

                // Set the new row's min weight to the previous row's max weight
                newRow.find('input[name="min_weight[]"]').val(previousRowMaxWeight).prop('readonly', true);

                // Conditionally set the new row's min weight operation based on the previous row's max operation
                if (previousRowMaxOperation === 'less') {
                    newRow.find('select[name="min_operation[]"]').val('greater_or_equal').prop('disabled',
                        true).append(
                        '<input type="hidden" name="min_operation[]" value="greater_or_equal">');

                } else if (previousRowMaxOperation === 'less_or_equal') {
                    newRow.find('select[name="min_operation[]"]').val('greater').prop('disabled', true)
                        .append('<input type="hidden" name="min_operation[]" value="greater">');
                }

                // Append the new row to the container
                $('#weightRows-{{ $branch->id }}').append(newRow);
                lastRow.find('input[name="min_weight[]"]').prop('readonly', true);

                // Update buttons after adding a new row
                updateButtons();
            });

            // Bind the remove row event
            $('#weightRows-{{ $branch->id }}').on('click', '.remove-row-{{ $branch->id }}', function() {
                $(this).closest('.weight-row-{{ $branch->id }}').remove();

                const remainingRows = $(
                    '#weightRows-{{ $branch->id }} .weight-row-{{ $branch->id }}');

                if (remainingRows.length === 1) {
                    // If only one row remains, make the min_weight field editable (readonly = false)
                    remainingRows.find('input[name="min_weight[]"]').prop('readonly', false);
                } else {
                    remainingRows.first().find('input[name="min_weight[]"]').prop('readonly',
                        true); // Readonly for the first row
                }

                updateButtons();
            });
        });
    </script>
@endpush
