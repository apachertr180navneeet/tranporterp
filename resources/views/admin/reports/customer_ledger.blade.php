@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Customer Ledger</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Customer Ledger</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.vehicle') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-car me-1"></i> Vehicle Report</a>
            <a href="{{ route('admin.reports.trip') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-trip me-1"></i> Trip Report</a>
            <a href="{{ route('admin.reports.driver-trip') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-user me-1"></i> Driver Trip</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Select Customer</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.customer-ledger') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold">Select Customer (Consignee) <span class="text-danger">*</span></label>
                    <select name="consignee_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        @foreach($consignees as $consignee)
                        <option value="{{ $consignee->id }}" {{ request('consignee_id') == $consignee->id ? 'selected' : '' }}>{{ $consignee->name }} {{ $consignee->phone ? '(' . $consignee->phone . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> View Ledger</button>
                        <a href="{{ route('admin.reports.customer-ledger') }}" class="btn btn-outline-secondary">Reset</a>
                        @if(request('consignee_id'))
                        <a href="{{ route('admin.reports.customer-ledger.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                        <a href="{{ route('admin.reports.customer-ledger.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedConsignee)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Customer Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td width="120"><strong>Name:</strong></td><td>{{ $selectedConsignee->name }}</td></tr>
                        <tr><td><strong>Phone:</strong></td><td>{{ $selectedConsignee->phone ?? 'N/A' }}</td></tr>
                        <tr><td><strong>Email:</strong></td><td>{{ $selectedConsignee->email ?? 'N/A' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td width="120"><strong>GSTIN:</strong></td><td>{{ $selectedConsignee->gstin ?? 'N/A' }}</td></tr>
                        <tr><td><strong>City:</strong></td><td>{{ $selectedConsignee->city ?? 'N/A' }}</td></tr>
                        <tr><td><strong>Address:</strong></td><td>{{ $selectedConsignee->address ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($summary)
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-label-primary">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Total LR</h6>
                    <h4 class="mb-0">{{ $summary->total_lr }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-label-info">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Freight</h6>
                    <h4 class="mb-0">₹ {{ number_format($summary->total_freight, 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-label-warning">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">GST</h6>
                    <h4 class="mb-0">₹ {{ number_format($summary->total_gst, 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-label-secondary">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Other</h6>
                    <h4 class="mb-0">₹ {{ number_format($summary->total_other, 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-label-success">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Total</h6>
                    <h4 class="mb-0">₹ {{ number_format($summary->total_amount, 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card bg-label-danger">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Due</h6>
                    <h4 class="mb-0">₹ {{ number_format($summary->total_remaining, 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Transactions</h5>
            <span class="text-muted">Showing {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() ?? 0 }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>LR No</th>
                        <th>Date</th>
                        <th>From → To</th>
                        <th>Vehicle</th>
                        <th class="text-end">Freight</th>
                        <th class="text-end">GST</th>
                        <th class="text-end">Other</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $b)
                    <tr>
                        <td><strong>{{ $b->lr_no }}</strong></td>
                        <td>{{ $b->lr_date?->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $b->originCity?->name ?? $b->from_city }} → {{ $b->destinationCity?->name ?? $b->to_city }}</td>
                        <td>{{ $b->vehicle?->vehicle_number ?? '-' }}</td>
                        <td class="text-end">₹ {{ number_format($b->freight_charges, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($b->gst_amount, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($b->other_charges, 2) }}</td>
                        <td class="text-end"><strong>₹ {{ number_format($b->total_amount, 2) }}</strong></td>
                        <td class="text-end">₹ {{ number_format($b->advance_amount, 2) }}</td>
                        <td class="text-end">
                            @php
                                $due = $b->remaining_amount ?? ($b->total_amount - $b->advance_amount);
                            @endphp
                            <span class="{{ $due > 0 ? 'text-danger fw-bold' : 'text-success' }}">₹ {{ number_format($due, 2) }}</span>
                        </td>
                        <td><span class="badge bg-label-{{ $b->payment_type == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($b->payment_type) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center py-4 text-muted">No transactions found for this customer</td></tr>
                    @endforelse
                </tbody>
                @if($summary && $transactions->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">Totals:</td>
                        <td class="text-end">₹ {{ number_format($summary->total_freight, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($summary->total_gst, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($summary->total_other, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($summary->total_amount, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($summary->total_advance, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($summary->total_remaining, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if(method_exists($transactions, 'links'))
        <div class="card-footer">
            {{ $transactions->withQueryString()->links() }}
        </div>
        @endif
    </div>
    @elseif(request()->has('consignee_id'))
    <div class="alert alert-info">Please select a customer to view the ledger.</div>
    @endif
</div>
@endsection
