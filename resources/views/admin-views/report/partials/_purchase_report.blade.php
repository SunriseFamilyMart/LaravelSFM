<div>
    <!-- Filters -->
    <form method="GET" id="purchase-report-form">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('Start Date') }}</label>
                <input type="date" name="start_date" id="purchase_start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('End Date') }}</label>
                <input type="date" name="end_date" id="purchase_end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block" onclick="filterPurchaseReport()">
                    <i class="tio-filter-outlined"></i> {{ translate('Filter') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="row g-2 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('Total Purchases') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ $totalPurchases }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('Total Amount') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ \App\CentralLogics\Helpers::set_symbol($totalAmount) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('Total Paid') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ \App\CentralLogics\Helpers::set_symbol($totalPaid) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('Balance') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ \App\CentralLogics\Helpers::set_symbol($totalBalance) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Button -->
    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger" onclick="exportPurchasePdf()">
                <i class="tio-download"></i> {{ translate('Download PDF') }}
            </button>
        </div>
    </div>

    <!-- Purchase by Product Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{ translate('Purchase by Product') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Product Name') }}</th>
                        <th>{{ translate('HSN Code') }}</th>
                        <th>{{ translate('Quantity') }}</th>
                        <th>{{ translate('Total Amount') }}</th>
                        <th>{{ translate('Tax Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($purchasesByProduct) > 0)
                        @foreach ($purchasesByProduct as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $product['product_name'] }}</td>
                                <td>{{ $product['hsn_code'] ?: 'N/A' }}</td>
                                <td>{{ $product['quantity'] }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($product['total_amount']) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($product['tax_amount']) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No data available') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function filterPurchaseReport() {
        var startDate = $('#purchase_start_date').val();
        var endDate = $('#purchase_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.purchase") }}' + 
                  '?start_date=' + startDate + 
                  '&end_date=' + endDate;

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                $('#purchase-report').html(data);
            }
        });
    }

    function exportPurchasePdf() {
        var startDate = $('#purchase_start_date').val();
        var endDate = $('#purchase_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.purchase.export-pdf") }}' + 
                  '?start_date=' + startDate + 
                  '&end_date=' + endDate;

        window.open(url, '_blank');
    }
</script>
