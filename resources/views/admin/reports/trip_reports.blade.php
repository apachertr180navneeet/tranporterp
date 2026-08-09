@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Trip Reports</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Trip Reports</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.vehicle') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-car me-1"></i> Vehicle Report</a>
            <a href="{{ route('admin.reports.driver-trip') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-user me-1"></i> Driver Trip</a>
            <a href="{{ route('admin.reports.fuel') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-gas-pump me-1"></i> Fuel Report</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detailed Trip Report</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.trip-reports') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">All Vehicles</option>
                            @foreach($vehicleList as $v)
                            <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Trip Status</label>
                        <select name="trip_status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ request('trip_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="active" {{ request('trip_status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="complete" {{ request('trip_status') == 'complete' ? 'selected' : '' }}>Complete</option>
                            <option value="reject" {{ request('trip_status') == 'reject' ? 'selected' : '' }}>Reject</option>
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
                            <a href="{{ route('admin.reports.trip-reports') }}" class="btn btn-outline-secondary">Reset</a>
                            <a href="{{ route('admin.reports.trip-reports.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                            <a href="{{ route('admin.reports.trip-reports.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>LR No</th>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Route</th>
                            <th class="text-end">Freight</th>
                            <th class="text-end">GST</th>
                            <th class="text-end">Other</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Bilty Advance</th>
                            <th class="text-end">Trip Advance</th>
                            <th>Payment</th>
                            <th>Trip Status</th>
                            <th class="text-end">Fuel Exp</th>
                            <th class="text-end">FastTag</th>
                            <th class="text-end">AdBlue</th>
                            <th class="text-end">Other Exp</th>
                            <th class="text-end">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trips as $builty)
                        @php
                            $trip = $builty->trip;
                            $totalFuelAmt = $trip?->fuelDetails->sum('amount') ?? 0;
                            $totalExpenses = $totalFuelAmt
                                + ($trip?->fasttag_total_amount ?? 0)
                                + ($trip?->adblue_total_amount ?? 0)
                                + ($trip?->other_amount ?? 0)
                                + ($trip?->advance_total_amount ?? 0);
                            $netProfit = $builty->total_amount - $totalExpenses;
                        @endphp
                        <tr>
                            <td><strong>{{ $builty->lr_no }}</strong></td>
                            <td>{{ $builty->lr_date?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $builty->vehicle?->vehicle_number ?? '-' }}</td>
                            <td>{{ $builty->driver?->name ?? '-' }}</td>
                            <td>{{ $builty->originCity?->name ?? $builty->from_city }} → {{ $builty->destinationCity?->name ?? $builty->to_city }}</td>
                            <td class="text-end">₹ {{ number_format($builty->freight_charges, 0) }}</td>
                            <td class="text-end">₹ {{ number_format($builty->gst_amount, 0) }}</td>
                            <td class="text-end">₹ {{ number_format($builty->other_charges, 0) }}</td>
                            <td class="text-end"><strong>₹ {{ number_format($builty->total_amount, 0) }}</strong></td>
                            <td class="text-end">₹ {{ number_format($builty->advance_amount, 0) }}</td>
                            <td class="text-end">₹ {{ number_format($trip?->advance_total_amount ?? 0, 0) }}</td>
                            <td><span class="badge bg-label-{{ $builty->payment_type == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($builty->payment_type) }}</span></td>
                            <td>
                                @if($trip)
                                    <span class="badge bg-label-{{ $trip->status === 'complete' ? 'success' : ($trip->status === 'reject' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">₹ {{ number_format($totalFuelAmt, 0) }}</td>
                            <td class="text-end">₹ {{ number_format($trip?->fasttag_total_amount ?? 0, 0) }}</td>
                            <td class="text-end">₹ {{ number_format($trip?->adblue_total_amount ?? 0, 0) }}</td>
                            <td class="text-end">₹ {{ number_format($trip?->other_amount ?? 0, 0) }}</td>
                            <td class="text-end">
                                <span class="{{ $netProfit >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                                    ₹ {{ number_format($netProfit, 0) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="18" class="text-center">No data found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($trips, 'links'))
                <div class="mt-3">
                    {{ $trips->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
