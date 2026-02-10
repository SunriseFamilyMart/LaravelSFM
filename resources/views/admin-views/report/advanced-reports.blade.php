@extends('layouts.admin.app')

@section('title', translate('Advanced Reports'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="media align-items-center">
                <img class="w--20" src="{{ asset('public/assets/admin') }}/svg/illustrations/report.svg"
                    alt="Image Description">
                <div class="media-body pl-3">
                    <h1 class="page-header-title mb-1">{{ translate('Advanced Reports') }}</h1>
                    <div>
                        <span>{{ translate('admin') }}:</span>
                        <a href="#"
                            class="text--primary-2">{{ auth('admin')->user()->f_name . ' ' . auth('admin')->user()->l_name }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $reportType == 'sales' ? 'active' : '' }}" 
                           href="#sales-report" 
                           data-toggle="tab" 
                           role="tab"
                           data-type="sales">
                            <i class="tio-chart-bar-1"></i> {{ translate('Sales Report') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $reportType == 'purchase' ? 'active' : '' }}" 
                           href="#purchase-report" 
                           data-toggle="tab" 
                           role="tab"
                           data-type="purchase">
                            <i class="tio-shopping-cart"></i> {{ translate('Purchase Report') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $reportType == 'gstr1' ? 'active' : '' }}" 
                           href="#gstr1-report" 
                           data-toggle="tab" 
                           role="tab"
                           data-type="gstr1">
                            <i class="tio-receipt"></i> {{ translate('GSTR-1 Report') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $reportType == 'gstr3' ? 'active' : '' }}" 
                           href="#gstr3-report" 
                           data-toggle="tab" 
                           role="tab"
                           data-type="gstr3">
                            <i class="tio-file-text"></i> {{ translate('GSTR-3 Report') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade {{ $reportType == 'sales' ? 'show active' : '' }}" 
                         id="sales-report" 
                         role="tabpanel">
                        <div class="text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">{{ translate('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $reportType == 'purchase' ? 'show active' : '' }}" 
                         id="purchase-report" 
                         role="tabpanel">
                        <div class="text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">{{ translate('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $reportType == 'gstr1' ? 'show active' : '' }}" 
                         id="gstr1-report" 
                         role="tabpanel">
                        <div class="text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">{{ translate('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $reportType == 'gstr3' ? 'show active' : '' }}" 
                         id="gstr3-report" 
                         role="tabpanel">
                        <div class="text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">{{ translate('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    $(document).ready(function() {
        // Load initial tab content
        loadTabContent('{{ $reportType }}');

        // Handle tab clicks
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var type = $(e.target).data('type');
            loadTabContent(type);
        });

        function loadTabContent(type) {
            var url = '';
            var targetDiv = '';

            switch(type) {
                case 'sales':
                    url = '{{ route("admin.report.advanced-reports.sales") }}';
                    targetDiv = '#sales-report';
                    break;
                case 'purchase':
                    url = '{{ route("admin.report.advanced-reports.purchase") }}';
                    targetDiv = '#purchase-report';
                    break;
                case 'gstr1':
                    url = '{{ route("admin.report.advanced-reports.gstr1") }}';
                    targetDiv = '#gstr1-report';
                    break;
                case 'gstr3':
                    url = '{{ route("admin.report.advanced-reports.gstr3") }}';
                    targetDiv = '#gstr3-report';
                    break;
            }

            if (url && targetDiv) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        $(targetDiv).html(data);
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = '<div class="alert alert-danger">' +
                            '<h6>{{ translate("Error loading report data") }}</h6>' +
                            '<p>{{ translate("Please check your connection and try again.") }}</p>' +
                            '</div>';
                        $(targetDiv).html(errorMsg);
                    }
                });
            }
        }
    });
</script>
@endpush
