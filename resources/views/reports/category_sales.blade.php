@extends('inventory.layouts.app')

@section('content')
    <div class="container py-4">

        {{-- Header Section --}}
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
            <h2 class="fw-bold text-primary mb-0 d-flex align-items-center">
                <i class="bi bi-bar-chart-line me-2 fs-3"></i> Category Wise Sales Report
            </h2>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('inventory.reports.category_sales.export', request()->query()) }}"
                    class="btn btn-success btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-excel fs-5"></i> Export Excel
                </a>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left fs-5"></i> Back
                </a>
            </div>
        </div>

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('inventory.reports.category_sales') }}" class="row g-3 mb-3">

            {{-- Search Box --}}
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by category..."
                    value="{{ request('search') }}">
            </div>

            {{-- Filter + Reset Buttons --}}
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-50">
                    <i class="bi bi-funnel"></i> Filter
                </button>

                <a href="{{ route('inventory.reports.category_sales') }}" class="btn btn-secondary w-50">
                    Reset
                </a>
            </div>
        </form>

        {{-- Card Table --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-primary text-white text-uppercase">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Category</th>
                                <th class="text-center">Purchased Qty</th>
                                <th class="text-end">Purchase Value (₹)</th>
                                <th class="text-center">Sold Qty</th>
                                <th class="text-end">Sales (₹)</th>
                                <th class="text-end">Tax (₹)</th>
                                <th class="text-end">Discount (₹)</th>
                                <th class="text-end">Profit / Loss (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $index => $report)
                                @php
                                    $profit = $report->total_sales - $report->total_purchase_value;
                                @endphp
                                <tr>
                                    <td class="text-center fw-semibold">{{ $reports->firstItem() + $index }}</td>
                                    <td class="fw-semibold text-dark">{{ ucfirst($report->category_name) }}</td>
                                    <td class="text-center">{{ $report->total_purchased_qty }}</td>
                                    <td class="text-end text-secondary">
                                        ₹{{ number_format($report->total_purchase_value, 2) }}</td>
                                    <td class="text-center">{{ $report->total_sold_qty }}</td>
                                    <td class="text-end text-success fw-semibold">
                                        ₹{{ number_format($report->total_sales, 2) }}</td>
                                    <td class="text-end text-info fw-semibold">₹{{ number_format($report->total_tax, 2) }}
                                    </td>
                                    <td class="text-end text-danger fw-semibold">
                                        ₹{{ number_format($report->total_discount, 2) }}</td>
                                    <td class="text-end fw-semibold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                        ₹{{ number_format($profit, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="bi bi-exclamation-circle me-2 fs-5"></i> No sales data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if ($reports->count() > 0)
                            @php
                                $total_profit = $reports->sum(fn($r) => $r->total_sales - $r->total_purchase_value);
                            @endphp
                            <tfoot class="bg-light fw-bold text-dark">
                                <tr>
                                    <td colspan="2" class="text-end">Grand Total:</td>
                                    <td class="text-center">{{ $reports->sum('total_purchased_qty') }}</td>
                                    <td class="text-end text-secondary">
                                        ₹{{ number_format($reports->sum('total_purchase_value'), 2) }}</td>
                                    <td class="text-center">{{ $reports->sum('total_sold_qty') }}</td>
                                    <td class="text-end text-success">₹{{ number_format($reports->sum('total_sales'), 2) }}
                                    </td>
                                    <td class="text-end text-info">₹{{ number_format($reports->sum('total_tax'), 2) }}</td>
                                    <td class="text-end text-danger">
                                        ₹{{ number_format($reports->sum('total_discount'), 2) }}</td>
                                    <td class="text-end {{ $total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                        ₹{{ number_format($total_profit, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div>
                    Showing {{ $reports->firstItem() ?? 0 }} to {{ $reports->lastItem() ?? 0 }} of
                    {{ $reports->total() }} categories
                </div>
                <div>
                    {{ $reports->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
