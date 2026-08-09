@extends('admin.layouts.app')

@php
if (!function_exists('inr_format')) {
    function inr_format($num) {
        $num = round($num, 0);
        $explrestunits = "";
        if (strlen($num) > 3) {
            $lastthree = substr($num, strlen($num) - 3, strlen($num));
            $restunits = substr($num, 0, strlen($num) - 3); // extracts the last three digits
            $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits; // adds zero in the beginning if length is odd
            $expunit = str_split($restunits, 2);
            for ($i = 0; $i < sizeof($expunit); $i++) {
                // creates each of the 2's group and adds a comma to the end
                if ($i == 0) {
                    $explrestunits .= (int)$expunit[$i] . ","; // if is first value , convert into integer
                } else {
                    $explrestunits .= $expunit[$i] . ",";
                }
            }
            $thecash = $explrestunits . $lastthree;
        } else {
            $thecash = $num;
        }
        return $thecash;
    }
}
@endphp

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Profit & Loss Report</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Profit & Loss</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.reports.profit-loss') }}" class="mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" max="9999-12-31" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" max="9999-12-31" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <option value="">All Years</option>
                            @for($y = date('Y'); $y >= 2025; $y--)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                        <a href="{{ route('admin.reports.profit-loss') }}" class="btn btn-outline-secondary flex-grow-1"><i class="bx bx-reset me-1"></i>Reset</a>
                        <a href="{{ route('admin.reports.profit-loss.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                        <a href="{{ route('admin.reports.profit-loss.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card bg-label-success h-100">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Total Income</h6>
                    <h4 class="mb-0">₹ {{ inr_format($summary['total_income']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-label-danger h-100">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Total Expenses</h6>
                    <h4 class="mb-0">₹ {{ inr_format($summary['total_expenses']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card {{ $summary['net_profit'] >= 0 ? 'bg-label-primary' : 'bg-label-warning' }} h-100">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">{{ $summary['net_profit'] >= 0 ? 'Net Profit' : 'Net Loss' }}</h6>
                    <h4 class="mb-0">₹ {{ inr_format(abs($summary['net_profit'])) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-label-info h-100">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Margin</h6>
                    <h4 class="mb-0">
                        @if($summary['total_income'] > 0)
                            {{ number_format(($summary['net_profit'] / $summary['total_income']) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Income Breakdown</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td>Freight Charges</td>
                            <td class="text-end"><strong>₹ {{ inr_format($summary['total_income']) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total Advance</td>
                            <td class="text-end text-success">₹ {{ inr_format($summary['total_advance']) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Expense Breakdown</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td>Fuel</td>
                            <td class="text-end">₹ {{ inr_format($summary['fuel_expense']) }}</td>
                        </tr>
                        <tr>
                            <td>FastTag (Toll)</td>
                            <td class="text-end">₹ {{ inr_format($summary['fasttag_expense']) }}</td>
                        </tr>
                        <tr>
                            <td>AdBlue</td>
                            <td class="text-end">₹ {{ inr_format($summary['adblue_expense']) }}</td>
                        </tr>
                        <tr>
                            <td>Other Trip Expenses</td>
                            <td class="text-end">₹ {{ inr_format($summary['other_trip_expense']) }}</td>
                        </tr>
                        <tr>
                            <td>Trip Advance</td>
                            <td class="text-end">₹ {{ inr_format($summary['total_trip_advance']) }}</td>
                        </tr>
                        <tr>
                            <td>Bilty Commission</td>
                            <td class="text-end">₹ {{ inr_format($summary['total_commission']) }}</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Total Expenses</strong></td>
                            <td class="text-end"><strong>₹ {{ inr_format($summary['total_expenses']) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Trend</h5>
                </div>
                <div class="card-body">
                    <div id="pnlReportChart"></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartMonths = @json($months);
    const chartData = @json($monthlyData);

    const incomeArr = chartData.map(d => d.income);
    const expenseArr = chartData.map(d => d.expense);
    const profitArr = chartData.map((d, i) => d.income - d.expense);

    const options = {
        series: [
            { name: 'Income', type: 'bar', data: incomeArr },
            { name: 'Expenses', type: 'bar', data: expenseArr },
            { name: 'Profit', type: 'line', data: profitArr }
        ],
        chart: { height: 380, type: 'line', stacked: false, toolbar: { show: false } },
        colors: ['#10b981', '#ef4444', '#062E39'],
        stroke: { width: [0, 0, 3], curve: 'smooth' },
        plotOptions: { bar: { columnWidth: '40%', endingShape: 'rounded' } },
        fill: { opacity: [0.85, 0.85, 1], gradient: { inverseColors: false, shade: 'light', type: 'vertical', opacityFrom: 0.85, opacityTo: 0.55 } },
        labels: chartMonths,
        markers: { size: 0 },
        yaxis: { labels: { formatter: v => '₹ ' + Math.round(v).toLocaleString('en-IN') } },
        tooltip: { shared: true, intersect: false, y: { formatter: v => '₹ ' + Math.round(v).toLocaleString('en-IN') } },
        legend: { position: 'top', horizontalAlign: 'center' }
    };

    const chart = new ApexCharts(document.querySelector('#pnlReportChart'), options);
    chart.render();
});
</script>
@endsection