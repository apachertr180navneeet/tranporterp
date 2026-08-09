@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">AdBlue Report</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">AdBlue Report</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.vehicle') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-car me-1"></i> Vehicle Report</a>
            <a href="{{ route('admin.reports.trip-reports') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-trip me-1"></i> Trip Reports</a>
            <a href="{{ route('admin.reports.fuel') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-gas-pump me-1"></i> Fuel Report</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">AdBlue Consumption Details</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.adblue') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">All Vehicles</option>
                            @foreach($vehicleList as $v)
                            <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">AdBlue Company</label>
                        <select name="adblue_company_id" id="adblue_company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($adblueCompanies as $ac)
                            <option value="{{ $ac->id }}" {{ request('adblue_company_id') == $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="credit" {{ request('payment_type') == 'credit' ? 'selected' : '' }}>Credit</option>
                            <option value="debit" {{ request('payment_type') == 'debit' ? 'selected' : '' }}>Debit</option>
                            <option value="cash" {{ request('payment_type') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Date From</label>
                        <input type="date" max="9999-12-31" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Date To</label>
                        <input type="date" max="9999-12-31" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i> Filter</button>
                            <a href="{{ route('admin.reports.adblue') }}" class="btn btn-outline-secondary">Reset</a>
                            <a href="{{ route('admin.reports.adblue.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                            <a href="{{ route('admin.reports.adblue.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
                        </div>
                    </div>
                </div>
            </form>

            @if($summary)
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-label-info">
                        <div class="card-body text-center py-2">
                            <h6 class="mb-1">Total Quantity</h6>
                            <h4 class="mb-0">{{ number_format($summary->total_qty, 2) }} L</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-label-warning">
                        <div class="card-body text-center py-2">
                            <h6 class="mb-1">Total Amount</h6>
                            <h4 class="mb-0">₹ {{ number_format($summary->total_amount, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-label-primary">
                        <div class="card-body text-center py-2">
                            <h6 class="mb-1">Total KM</h6>
                            <h4 class="mb-0">{{ number_format($summary->total_km, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Company</th>
                            <th>Payment Type</th>
                            <th class="text-end">Qty (L)</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">KM</th>
                            <th>LR No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adblueDetails as $ad)
                        <tr>
                            <td>{{ $ad->date?->format('d-m-Y') ?? '-' }}</td>
                            <td><strong>{{ $ad->trip?->builty?->vehicle?->vehicle_number ?? '-' }}</strong></td>
                            <td>{{ $ad->adblueCompany?->name ?? '-' }}</td>
                            <td>
                                @php
                                    $pt = strtolower($ad->payment_type ?? '');
                                    $badgeClass = match($pt) {
                                        'cash' => 'bg-label-success',
                                        'debit' => 'bg-label-info',
                                        'credit' => 'bg-label-warning',
                                        default => 'bg-label-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($ad->payment_type ?? '-') }}</span>
                            </td>
                            <td class="text-end">{{ number_format($ad->quantity, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($ad->rate, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($ad->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($ad->km, 2) }}</td>
                            <td>{{ $ad->trip?->builty?->lr_no ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">No data found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($adblueDetails, 'links'))
                <div class="mt-3">
                    {{ $adblueDetails->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
