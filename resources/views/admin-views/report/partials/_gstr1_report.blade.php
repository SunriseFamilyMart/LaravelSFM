<div>
    <!-- Filters -->
    <form method="GET" id="gstr1-report-form">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('Branch') }}</label>
                <select class="custom-select" name="branch_id" id="gstr1_branch_id">
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
                <input type="date" name="start_date" id="gstr1_start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">{{ translate('End Date') }}</label>
                <input type="date" name="end_date" id="gstr1_end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label class="input-label">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block" onclick="filterGstr1Report()">
                    <i class="tio-filter-outlined"></i> {{ translate('Filter') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Export Button -->
    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger" onclick="exportGstr1Pdf()">
                <i class="tio-download"></i> {{ translate('Download PDF') }}
            </button>
        </div>
    </div>

    <!-- GSTR-1 Report Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{ translate('GSTR-1: Outward Supplies (Sales)') }}</h5>
            <p class="text-muted mb-0">{{ translate('HSN-wise Summary of Outward Supplies') }}</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('HSN Code') }}</th>
                        <th>{{ translate('Tax Rate') }} (%)</th>
                        <th>{{ translate('Taxable Value') }}</th>
                        <th>{{ translate('CGST Amount') }}</th>
                        <th>{{ translate('SGST Amount') }}</th>
                        <th>{{ translate('Total Tax') }}</th>
                        <th>{{ translate('Total Value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($gstrData) > 0)
                        @foreach ($gstrData as $index => $data)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $data['hsn_code'] }}</td>
                                <td>{{ number_format($data['tax_rate'], 2) }}%</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($data['taxable_value']) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($data['cgst_amount']) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($data['sgst_amount']) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($data['total_tax']) }}</td>
                                <td>{{ \App\CentralLogics\Helpers::set_symbol($data['total_value']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-active font-weight-bold">
                            <td colspan="3" class="text-right">{{ translate('Grand Total') }}</td>
                            <td>{{ \App\CentralLogics\Helpers::set_symbol($grandTotalTaxable) }}</td>
                            <td>{{ \App\CentralLogics\Helpers::set_symbol($grandTotalCGST) }}</td>
                            <td>{{ \App\CentralLogics\Helpers::set_symbol($grandTotalSGST) }}</td>
                            <td>{{ \App\CentralLogics\Helpers::set_symbol($grandTotalTax) }}</td>
                            <td>{{ \App\CentralLogics\Helpers::set_symbol($grandTotalValue) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="8" class="text-center">{{ translate('No data available') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Note -->
    <div class="alert alert-info mt-3">
        <h6 class="alert-heading">{{ translate('Note:') }}</h6>
        <p class="mb-0">{{ translate('CGST (Central GST) and SGST (State GST) are calculated as 50% each of the total tax amount. This is standard for intra-state transactions.') }}</p>
    </div>
</div>

<script>
    function filterGstr1Report() {
        var branchId = $('#gstr1_branch_id').val();
        var startDate = $('#gstr1_start_date').val();
        var endDate = $('#gstr1_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.gstr1") }}' + 
                  '?branch_id=' + branchId + 
                  '&start_date=' + startDate + 
                  '&end_date=' + endDate;

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                $('#gstr1-report').html(data);
            }
        });
    }

    function exportGstr1Pdf() {
        var branchId = $('#gstr1_branch_id').val();
        var startDate = $('#gstr1_start_date').val();
        var endDate = $('#gstr1_end_date').val();

        var url = '{{ route("admin.report.advanced-reports.gstr1.export-pdf") }}' + 
                  '?branch_id=' + branchId + 
                  '&start_date=' + startDate + 
                  '&end_date=' + endDate;

        window.open(url, '_blank');
    }
</script>
