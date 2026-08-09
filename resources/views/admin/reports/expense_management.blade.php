@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Expense Management Report</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Expense Management</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.reports.expense-management') }}" class="mb-4">
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
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">All Vehicles</option>
                            @foreach(\App\Models\Vehicle::where('status', 'active')->orderBy('vehicle_number')->get() as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                        <a href="{{ route('admin.reports.expense-management') }}" class="btn btn-outline-secondary flex-grow-1"><i class="bx bx-reset me-1"></i>Reset</a>
                        <a href="{{ route('admin.reports.expense-management.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                        <a href="{{ route('admin.reports.expense-management.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row mb-4">
        <div class="col-md-4 col-6 mb-2">
            <div class="card bg-label-info">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Trip Expenses</h6>
                    <h4 class="mb-0">₹ {{ number_format($totalTripExpenses, 0) }}</h4>
                    <small class="text-muted">Fuel · FastTag · AdBlue · Other · Advance</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-2">
            <div class="card bg-label-warning">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Maintenance Expenses</h6>
                    <h4 class="mb-0">₹ {{ number_format($totalMaintenanceExpenses, 0) }}</h4>
                    <small class="text-muted">Service · Breakdown · Spare Parts</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-2">
            <div class="card bg-label-danger">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Grand Total</h6>
                    <h4 class="mb-0">₹ {{ number_format($grandTotal, 0) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-info">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">Fuel</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalFuelAmt, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-warning">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">FastTag</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalFastTag, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-primary">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">AdBlue</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalAdBlue, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-secondary">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">Other Trip Expenses</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalOtherExp, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-success">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">Maintenance</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalMaintenance, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-danger">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">Breakdown</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalBreakdown, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border border-purple" style="border-color: #6f42c1 !important;">
                <div class="card-body text-center py-2">
                    <h6 class="mb-1">Spare Parts</h6>
                    <h5 class="mb-0">₹ {{ number_format($totalSparePart, 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card bg-label-success">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Trip Advance</h6>
                    <h4 class="mb-0">₹ {{ number_format($totalTripAdvance, 0) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Vehicle-wise Expense Summary</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Vehicle</th>
                                <th class="text-end">Fuel</th>
                                <th class="text-end">FastTag</th>
                                <th class="text-end">AdBlue</th>
                                <th class="text-end">Other Trip</th>
                                <th class="text-end">Advance</th>
                                <th class="text-end">Maintenance</th>
                                <th class="text-end">Breakdown</th>
                                <th class="text-end">Spare Parts</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicles as $i => $v)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $v->vehicle_number }}</strong></td>
                                <td class="text-end">₹ {{ number_format($v->fuel_expense, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->fasttag_expense, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->adblue_expense, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->other_expense, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->advance_expense, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->maintenance_cost, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->breakdown_cost, 0) }}</td>
                                <td class="text-end">₹ {{ number_format($v->spare_part_cost, 0) }}</td>
                                <td class="text-end"><strong>₹ {{ number_format($v->total_expense, 0) }}</strong></td>
                            </tr>
                            @empty
                            <tr><td colspan="11" class="text-center text-muted py-3">No expense data found for the selected period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent Fuel Expenses</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Pump</th>
                                <th class="text-end">Qty (L)</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentFuelDetails as $fd)
                            <tr>
                                <td>{{ $fd->date->format('d-m-Y') }}</td>
                                <td>{{ $fd->trip?->builty?->vehicle?->vehicle_number ?? '-' }}</td>
                                <td>{{ $fd->fuelPump?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($fd->quantity, 2) }}</td>
                                <td class="text-end">₹ {{ number_format($fd->amount, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No fuel expenses</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent FastTag Expenses</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentFastTagDetails as $ft)
                            <tr>
                                <td>{{ $ft->transaction_time ? $ft->transaction_time->format('d-m-Y') : ($ft->created_at ? $ft->created_at->format('d-m-Y') : '-') }}</td>
                                <td>{{ $ft->trip?->builty?->vehicle?->vehicle_number ?? '-' }}</td>
                                <td>{{ Str::limit($ft->description, 30) }}</td>
                                <td class="text-end">₹ {{ number_format($ft->amount, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No FastTag expenses</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent AdBlue Expenses</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAdBlueDetails as $ab)
                            <tr>
                                <td>{{ $ab->date->format('d-m-Y') }}</td>
                                <td>{{ $ab->trip?->builty?->vehicle?->vehicle_number ?? '-' }}</td>
                                <td class="text-end">{{ number_format($ab->quantity, 2) }}</td>
                                <td class="text-end">₹ {{ number_format($ab->amount, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No AdBlue expenses</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent Other Trip Expenses</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Title</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOtherDetails as $od)
                            <tr>
                                <td>{{ $od->date->format('d-m-Y') }}</td>
                                <td>{{ $od->trip?->builty?->vehicle?->vehicle_number ?? '-' }}</td>
                                <td>{{ $od->title }}</td>
                                <td class="text-end">₹ {{ number_format($od->amount, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No other expenses</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
