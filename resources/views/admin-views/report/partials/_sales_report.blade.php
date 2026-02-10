<div>
    <!-- Filters -->
    <form method="GET" id="sales-report-form">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('Branch') }}</label>
                <select class="custom-select" name="branch_id" id="sales_branch_id">
                    <option value="all" {{ is_null($branchId) || $branchId == 'all' ? 'selected' : '' }}>
                        {{ translate('All Branches') }}
                    </option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch['id'] }}" {{ $branch['id'] == $branchId ? 'selected' : '' }}>
                            {{ $branch['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('Start Date') }}</label>
                <input type="date" name="start_date" id="sales_start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('End Date') }}</label>
                <input type="date" name="end_date" id="sales_end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block" onclick="filterSalesReport()">
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
                    <h6 class="card-subtitle">{{ translate('Total Orders') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ $totalOrders }}</h2>
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
                    <h6 class="card-subtitle">{{ translate('Total Tax') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ \App\CentralLogics\Helpers::set_symbol($totalTax) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-hover-shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle">{{ translate('Total Discount') }}</h6>
                    <div class="row align-items-center gx-2 mb-1">
                        <div class="col-12">
                            <h2 class="card-title text-inherit">{{ \App\CentralLogics\Helpers::set_symbol($totalDiscount) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Button -->
    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger" onclick="exportSalesPdf()">
                <i class="tio-download"></i> {{ translate('Download PDF') }}
            </button>
        </div>
    </div>

    <!-- Sales by Product Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{ translate('Sales by Product') }}</h5>
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
                        <th>{{ translate('Total Tax') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($salesByProduct) > 0)
                        @foreach ($salesByProduct as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $product['name'] }}</td>
                                <td>{{ $product['hsn_code'] ?? 'N/A' }}</td>
                                <td>{{ $product['quantity'] }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($product['total_amount']) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($product['total_tax']) }}</td>
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
    function filterSalesReport() {
        var branchId = $('#sales_branch_id').val();
        var startDate = $('#sales_start_date').val();
        var endDate = $('#sales_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.sales") }}' + 
                  '?branch_id=' + branchId + 
                  '&start_date=' + startDate + 
                  '&end_date=' + endDate;

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                $('#sales-report').html(data);
            }
        });
    }

    function exportSalesPdf() {
        var branchId = $('#sales_branch_id').val();
        var startDate = $('#sales_start_date').val();
        var endDate = $('#sales_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.sales.export-pdf") }}' + 
                  '?branch_id=' + branchId + 
                  '&start_date=' + startDate + 
                  '&end_date=' + endDate;

        window.open(url, '_blank');
    }
</script>
