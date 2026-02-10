<div>
    <!-- Filters -->
    <form method="GET" id="gstr3-report-form">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('Branch') }}</label>
                <select class="custom-select" name="branch_id" id="gstr3_branch_id">
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
                <input type="date" name="start_date" id="gstr3_start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('End Date') }}</label>
                <input type="date" name="end_date" id="gstr3_end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block" onclick="filterGstr3Report()">
                    <i class="tio-filter-outlined"></i> {{ translate('Filter') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Export Button -->
    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger" onclick="exportGstr3Pdf()">
                <i class="tio-download"></i> {{ translate('Download PDF') }}
            </button>
        </div>
    </div>

    <!-- GSTR-3 Summary Report -->
    <div class="row g-3">
        <!-- Outward Supplies -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-soft-success">
                    <h5 class="card-title mb-0">{{ translate('Outward Supplies (Sales)') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>{{ translate('Taxable Value') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($outwardTaxable) }}</td>
                        </tr>
                        <tr>
                            <th>{{ translate('CGST') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($outwardCGST) }}</td>
                        </tr>
                        <tr>
                            <th>{{ translate('SGST') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($outwardSGST) }}</td>
                        </tr>
                        <tr class="font-weight-bold">
                            <th>{{ translate('Total Tax') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($outwardTotalTax) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inward Supplies -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-soft-warning">
                    <h5 class="card-title mb-0">{{ translate('Inward Supplies (Purchases)') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>{{ translate('Taxable Value') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($inwardTaxable) }}</td>
                        </tr>
                        <tr>
                            <th>{{ translate('CGST') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($inwardCGST) }}</td>
                        </tr>
                        <tr>
                            <th>{{ translate('SGST') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($inwardSGST) }}</td>
                        </tr>
                        <tr class="font-weight-bold">
                            <th>{{ translate('Total Tax') }}</th>
                            <td class="text-right">{{ \App\CentralLogics\Helpers::set_symbol($inwardTotalTax) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Net Tax Liability -->
        <div class="col-12">
            <div class="card {{ $netTaxLiability >= 0 ? 'border-success' : 'border-danger' }}">
                <div class="card-body text-center">
                    <h5 class="card-title">{{ translate('Net Tax Liability') }}</h5>
                    <h2 class="display-4 {{ $netTaxLiability >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ \App\CentralLogics\Helpers::set_symbol(abs($netTaxLiability)) }}
                    </h2>
                    <p class="text-muted">
                        @if($netTaxLiability >= 0)
                            {{ translate('Tax Payable') }}
                        @else
                            {{ translate('Tax Refundable') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Note -->
    <div class="alert alert-info mt-3">
        <h6 class="alert-heading">{{ translate('Note:') }}</h6>
        <ul class="mb-0">
            <li>{{ translate('Net Tax Liability = Output Tax (Sales) - Input Tax Credit (Purchases)') }}</li>
            <li>{{ translate('Positive value indicates tax payable to government') }}</li>
            <li>{{ translate('Negative value indicates refundable tax credit') }}</li>
        </ul>
    </div>
</div>

<script>
    function filterGstr3Report() {
        var branchId = $('#gstr3_branch_id').val();
        var startDate = $('#gstr3_start_date').val();
        var endDate = $('#gstr3_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.gstr3") }}' + 
                  '?branch_id=' + branchId + 
                  '&start_date=' + startDate + 
                  '&end_date=' + endDate;

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                $('#gstr3-report').html(data);
            }
        });
    }

    function exportGstr3Pdf() {
        var branchId = $('#gstr3_branch_id').val();
        var startDate = $('#gstr3_start_date').val();
        var endDate = $('#gstr3_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.gstr3.export-pdf") }}' + 
                  '?branch_id=' + branchId + 
                  '&start_date=' + startDate + 
                  '&end_date=' + endDate;

        window.open(url, '_blank');
    }
</script>
