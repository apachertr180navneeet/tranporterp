@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">TDS Report</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.sales-ledger') }}">Sales Ledger</a></li>
                    <li class="breadcrumb-item active">TDS Report</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.sales-ledger') }}" class="btn btn-outline-secondary btn-sm me-1">
                <i class="bx bx-arrow-back me-1"></i> Back to Sales Ledger
            </a>
            <a href="{{ route('admin.reports.sales-ledger.history') }}" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-history me-1"></i> Receiving History
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0">Filter TDS Records</h5>
        </div>
        <div class="card-body mt-3">
            <form method="GET" action="{{ route('admin.reports.tds-report') }}" class="row g-3">
                @if(auth()->user()->isSuperAdmin())
                <div class="col-md-3">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="all">All Companies</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Consigner</label>
                    <select name="consignor_id" class="form-select">
                        <option value="">All Consigners</option>
                        @foreach($consignors as $consignor)
                        <option value="{{ $consignor->id }}" {{ request('consignor_id') == $consignor->id ? 'selected' : '' }}>{{ $consignor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bill Number</label>
                    <input type="text" name="bill_number" class="form-control" value="{{ request('bill_number') }}" placeholder="Search by Bill No">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary w-50"><i class="bx bx-search me-1"></i> Search</button>
                        <a href="{{ route('admin.reports.tds-report') }}" class="btn btn-outline-secondary w-50">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>S.No</th>
                        <th>Receiving Date</th>
                        <th>Bill Number</th>
                        <th>Consigner / Bill To</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th class="text-end">Receiving Amt</th>
                        <th class="text-end">Receiving GST</th>
                        <th class="text-end">TDS Amount</th>
                        <th class="text-end">Other Deduction</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @php
                        $totalRecv = 0;
                        $totalGst = 0;
                        $totalTds = 0;
                        $totalDeduction = 0;
                    @endphp
                    @forelse($receivings as $index => $receiving)
                        @php
                            $totalRecv += $receiving->receiving_amount;
                            $totalGst += $receiving->receiving_gst;
                            $totalTds += $receiving->tds;
                            $totalDeduction += $receiving->deduction;
                            $billNo = !empty($receiving->invoice?->bill_number) ? $receiving->invoice?->bill_number : ($receiving->invoice?->invoice_no ?? ('#'.$receiving->invoice_id));
                        @endphp
                        <tr>
                            <td>{{ $receivings->firstItem() + $index }}</td>
                            <td>{{ $receiving->date?->format('d-m-Y') }}</td>
                            <td><strong class="text-primary">{{ $billNo }}</strong></td>
                            <td>{{ $receiving->invoice?->consignor_name }}</td>
                            <td>{{ $receiving->company?->name ?? 'N/A' }}</td>
                            <td>{{ $receiving->branch?->name ?? 'N/A' }}</td>
                            <td class="text-end text-success">₹ {{ number_format($receiving->receiving_amount, 2) }}</td>
                            <td class="text-end text-success">₹ {{ number_format($receiving->receiving_gst, 2) }}</td>
                            <td class="text-end text-danger fw-bold">₹ {{ number_format($receiving->tds, 2) }}</td>
                            <td class="text-end text-warning">₹ {{ number_format($receiving->deduction, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No TDS records found</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($receivings->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="6" class="text-end">Total Page Amount:</td>
                        <td class="text-end text-success">₹ {{ number_format($totalRecv, 2) }}</td>
                        <td class="text-end text-success">₹ {{ number_format($totalGst, 2) }}</td>
                        <td class="text-end text-danger">₹ {{ number_format($totalTds, 2) }}</td>
                        <td class="text-end text-warning">₹ {{ number_format($totalDeduction, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($receivings->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $receivings->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
