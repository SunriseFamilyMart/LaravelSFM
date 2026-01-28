@extends('layouts.admin.app')

@section('title', translate('delivery fee setup'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            @include('admin-views.business-settings.partial.business-settings-navmenu')
        </div>

        <div class="tab-content">
            <div class="tab-pane active" id="delivery-fee">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span>{{translate('General Delivery Fee Setup')}}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{route('admin.business-settings.store.store-free-delivery-over-amount')}}" method="post"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mt-5">
                                    <?php
                                    $freeDeliveryStatus=\App\CentralLogics\Helpers::get_business_settings('free_delivery_over_amount_status');
                                    ?>
                                    <div class="form-group">
                                        <label class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control change-free-delivery-status">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1"><strong>{{translate('free_delivery_over_amount_status')}}</strong></span>
                                                <span class="form-label-secondary text-danger d-flex ml-1" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('If this field is active and the order amount exceeds this free delivery over amount then the delivery fee will be free.')}}"><img src="{{asset('public/assets/admin/img/info-circle.svg')}}" alt="info"></span>
                                            </span>
                                            <input type="checkbox" class="toggle-switch-input" name="free_delivery_status" id="free_delivery_status" {{ $freeDeliveryStatus == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @php($freeDeliveryOverAmount=\App\CentralLogics\Helpers::get_business_settings('free_delivery_over_amount'))
                                    <div class="form-group mb-0">
                                        <label>{{translate('free_delivery_over_amount')}}<span>({{ \App\CentralLogics\Helpers::currency_symbol() }})</span>
                                            <i class="tio-info-outined"
                                               data-toggle="tooltip"
                                               data-placement="top"
                                               title="{{ translate('If the order amount exceeds this amount the delivery fee will be free.') }}"></i>

                                        </label>
                                        <input type="number" value="{{$freeDeliveryOverAmount}}" name="free_delivery_over_amount" class="form-control" id="free_delivery_over_amount" placeholder=""
                                               {{ $freeDeliveryStatus == 0 ? 'readonly' : '' }} {{ $freeDeliveryStatus == 0 ? 'readonly' : '' }} min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn--reset">{{translate('reset')}}</button>
                                <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        class="btn btn--primary call-demo">{{translate('submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-5">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span>{{translate('Branch Wise Delivery Fee Setup')}}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="branchTabs" role="tablist">
                            @foreach($branches as $branch)
                                <li class="nav-item">
                                    <a class="nav-link" id="branch{{ $branch->id }}-tab" data-toggle="tab" href="#branch{{ $branch->id }}" role="tab"
                                       aria-controls="branch{{ $branch->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                        {{ $branch->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content" id="branchTabsContent">
                            @foreach($branches as $branch)
                                <div class="tab-pane fade" id="branch{{ $branch->id }}" role="tabpanel" aria-labelledby="branch{{ $branch->id }}-tab">
                                    @include('admin-views.business-settings.partial.delivery_charge_form', ['branch' => $branch])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>

        $('#free_delivery_status').on('change', function () {
            var $freeDeliveryOverAmountField = $('#free_delivery_over_amount');
            if ($(this).is(':checked')) {
                $freeDeliveryOverAmountField.removeAttr('readonly');
            } else {
                $freeDeliveryOverAmountField.attr('readonly', 'readonly');
            }
        });


        $(document).ready(function() {
            $('#addAreaModal').on('shown.bs.modal', function () {
                $('#areaName').trigger('focus');
            });

            $('[data-toggle="modal"]').on('click', function() {
                var branchId = $(this).data('branch-id');
                $('.branchIdInput').val(branchId);
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var tabId = $(e.target).attr('href'); // e.g., #branch1
                localStorage.setItem('activeTab', tabId);

            });

            var activeTab = localStorage.getItem('activeTab');

            if (activeTab) {
                $('#branchTabs a[href="' + activeTab + '"]').tab('show');
            } else {
                $('#branchTabs a:first').tab('show');
            }
        });

    </script>

@endpush
