@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Driver-wise Trip Report</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Driver Trip Report</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.vehicle') }}" class="btn btn-outline-primary"><i class="bx bx-car me-1"></i> Vehicle Report</a>
            <a href="{{ route('admin.reports.trip') }}" class="btn btn-outline-primary"><i class="bx bx-trip me-1"></i> Trip Report</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Driver Trip Report</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.driver-trip') }}" class="mb-3 row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Select Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">Select Driver</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->phone ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                    <a href="{{ route('admin.reports.driver-trip') }}" class="btn btn-outline-secondary flex-fill">Reset</a>
                    <a href="{{ route('admin.reports.driver-trip.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                    <a href="{{ route('admin.reports.driver-trip.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
                </div>
            </form>

            @php $currentDriver = null; @endphp

            @forelse($trips as $builty)
            @php
                $trip = $builty->trip;
                $totalFuelQty = $trip?->fuelDetails->sum('quantity') ?? 0;
                $totalFuelAmt = $trip?->fuelDetails->sum('amount') ?? 0;

                if ($currentDriver !== $builty->driver_id):
                    $currentDriver = $builty->driver_id;
            @endphp
            <div class="card mb-3 border">
                <div class="card-header py-2 px-3 bg-light">
                    <strong>Driver:</strong> {{ $builty->driver?->name ?? 'N/A' }} &mdash; {{ $builty->driver?->phone ?? '' }} @if($builty->driver?->driver_id) | <strong>ID:</strong> {{ $builty->driver->driver_id }} @endif
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>LR No</th>
                                <th>Vehicle</th>
                                <th class="text-end">Fuel (L)</th>
                                <th class="text-end">Fuel Amt</th>
                                <th class="text-end">FastTag</th>
                                <th class="text-end">AdBlue</th>
                                <th class="text-end">Other</th>
                                <th class="text-end">Advance</th>
                            </tr>
                        </thead>
                        <tbody>
            @endif
                            <tr>
                                <td>{{ $builty->lr_no }}</td>
                                <td>{{ $builty->vehicle?->vehicle_number ?? '-' }}</td>
                                <td class="text-end">{{ number_format($totalFuelQty, 2) }}</td>
                                <td class="text-end">{{ number_format($totalFuelAmt, 2) }}</td>
                                <td class="text-end">{{ $trip ? number_format($trip->fasttag_total_amount, 2) : '-' }}</td>
                                <td class="text-end">{{ $trip ? number_format($trip->adblue_total_amount, 2) : '-' }}</td>
                                <td class="text-end">{{ $trip ? number_format($trip->other_amount, 2) : '-' }}</td>
                                <td class="text-end">{{ $trip ? number_format($trip->advance_total_amount, 2) : '-' }}</td>
                            </tr>
            @php if ($loop->last || ($trips[$loop->index + 1]->driver_id ?? null) !== $currentDriver): @endphp
                        </tbody>
                    </table>
                </div>
            </div>
            @php endif; @endphp
            @empty
            <div class="text-center py-4">
                <p class="text-muted mb-0">No data found</p>
            </div>
            @endforelse

            @if(method_exists($trips, 'links'))
                <div class="mt-3">
                    {{ $trips->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
