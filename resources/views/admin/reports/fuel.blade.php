@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Fuel Report</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Fuel Report</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.vehicle') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-car me-1"></i> Vehicle Report</a>
            <a href="{{ route('admin.reports.trip-reports') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-trip me-1"></i> Trip Reports</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fuel Consumption Details</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.fuel') }}" class="mb-3">
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
                        <label class="form-label">Fuel Company</label>
                        <select name="fuel_company_id" id="fuel_company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($fuelCompanies as $fc)
                            <option value="{{ $fc->id }}" {{ request('fuel_company_id') == $fc->id ? 'selected' : '' }}>{{ $fc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Fuel Pump</label>
                        <select name="fuel_pump_id" id="fuel_pump_id" class="form-select">
                            <option value="">All Pumps</option>
                            @foreach($fuelPumps as $fp)
                            <option value="{{ $fp->id }}" data-company-id="{{ $fp->fuel_company_id }}" {{ request('fuel_pump_id') == $fp->id ? 'selected' : '' }}>{{ $fp->name }}</option>
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
                            <a href="{{ route('admin.reports.fuel') }}" class="btn btn-outline-secondary">Reset</a>
                            <a href="{{ route('admin.reports.fuel.export', 'excel') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-success btn-sm"><i class="bx bx-spreadsheet me-1"></i> Excel</a>
                            <a href="{{ route('admin.reports.fuel.export', 'pdf') . '?' . http_build_query(request()->except('page')) }}" class="btn btn-danger btn-sm"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
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
                            <th>Pump</th>
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
                        @forelse($fuelDetails as $fd)
                        <tr>
                            <td>{{ $fd->date?->format('d-m-Y') ?? '-' }}</td>
                            <td><strong>{{ $fd->trip?->builty?->vehicle?->vehicle_number ?? '-' }}</strong></td>
                            <td>{{ $fd->fuelPump?->name ?? '-' }}</td>
                            <td>{{ $fd->fuelCompany?->name ?? '-' }}</td>
                            <td>
                                @php
                                    $pt = strtolower($fd->payment_type ?? '');
                                    $badgeClass = match($pt) {
                                        'cash' => 'bg-label-success',
                                        'debit' => 'bg-label-info',
                                        'credit' => 'bg-label-warning',
                                        default => 'bg-label-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($fd->payment_type ?? '-') }}</span>
                            </td>
                            <td class="text-end">{{ number_format($fd->quantity, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($fd->rate, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($fd->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($fd->km, 2) }}</td>
                            <td>{{ $fd->trip?->builty?->lr_no ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No data found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($fuelDetails, 'links'))
                <div class="mt-3">
                    {{ $fuelDetails->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const companySelect = document.getElementById('fuel_company_id');
        const pumpSelect = document.getElementById('fuel_pump_id');
        
        if (companySelect && pumpSelect) {
            const pumpOptions = Array.from(pumpSelect.options).filter(opt => opt.value !== "");
            
            function filterPumps() {
                const selectedCompany = companySelect.value;
                let firstValidOpt = null;
                
                pumpOptions.forEach(opt => {
                    if (selectedCompany === "" || opt.dataset.companyId === selectedCompany) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                    }
                });
                
                // If current selected pump is now hidden, reset pump selection
                const selectedPumpOption = pumpSelect.options[pumpSelect.selectedIndex];
                if (selectedPumpOption && selectedPumpOption.value !== "" && selectedPumpOption.style.display === 'none') {
                    pumpSelect.value = "";
                }
            }
            
            companySelect.addEventListener('change', filterPumps);
            filterPumps(); // Run on load
        }
    });
</script>
@endpush
