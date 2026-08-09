@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Advance Management</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.driver-management.salary') }}">Driver Salary</a></li>
                    <li class="breadcrumb-item active">Advance Management</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.driver-management.salary') }}" class="btn btn-outline-primary"><i class="bx bx-money me-1"></i> Salary Management</a>
            <a href="{{ route('admin.driver-management.salary-slip') }}" class="btn btn-outline-primary"><i class="bx bx-receipt me-1"></i> Salary Slip</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $editAdvance ? 'Edit Advance' : 'Give Advance to Driver' }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $editAdvance ? route('admin.driver-management.advance.update', $editAdvance) : route('admin.driver-management.advance.store') }}">
                @csrf
                @if($editAdvance) @method('PUT') @endif
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Driver <span class="text-danger">*</span></label>
                        <select name="driver_id" class="form-select @error('driver_id') is-invalid @enderror" required>
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id', $editAdvance?->driver_id) == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->phone ?? 'N/A' }}) @if($driver->driver_id)[{{ $driver->driver_id }}]@endif</option>
                            @endforeach
                        </select>
                        @error('driver_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $editAdvance?->amount) }}" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Deduction Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 pt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deduction_type" id="deduction_full" value="full" {{ old('deduction_type', $editAdvance?->deduction_type ?? 'full') == 'full' ? 'checked' : '' }}>
                                <label class="form-check-label" for="deduction_full">Full (One Time)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deduction_type" id="deduction_monthly" value="monthly" {{ old('deduction_type', $editAdvance?->deduction_type) == 'monthly' ? 'checked' : '' }}>
                                <label class="form-check-label" for="deduction_monthly">Monthly</label>
                            </div>
                        </div>
                        @error('deduction_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2 mb-3" id="monthly_deduction_field" style="{{ old('deduction_type', $editAdvance?->deduction_type) == 'monthly' ? '' : 'display:none' }}">
                        <label class="form-label">Monthly Amount (₹)</label>
                        <input type="number" step="0.01" min="0" name="monthly_deduction" class="form-control @error('monthly_deduction') is-invalid @enderror" value="{{ old('monthly_deduction', $editAdvance?->monthly_deduction) }}">
                        @error('monthly_deduction') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" max="9999-12-31" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $editAdvance?->date?->format('Y-m-d')) }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" rows="1">{{ old('remark', $editAdvance?->remark) }}</textarea>
                        @error('remark') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editAdvance ? 'Update Advance' : 'Record Advance' }}</button>
                    @if($editAdvance)
                        <a href="{{ route('admin.driver-management.advance') }}" class="btn btn-outline-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Advance History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Driver</th>
                        <th>Driver ID</th>
                        <th>Phone</th>
                        <th class="text-end">Amount (₹)</th>
                        <th>Deduction Type</th>
                        <th class="text-end">Deduction Amt (₹)</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Remark</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($advances as $advance)
                    <tr>
                        <td>{{ $advance->driver?->name ?? '-' }}</td>
                        <td>{{ $advance->driver?->driver_id ?? '-' }}</td>
                        <td>{{ $advance->driver?->phone ?? '-' }}</td>
                        <td class="text-end">{{ number_format($advance->amount, 2) }}</td>
                        <td>{{ $advance->deduction_type === 'monthly' ? 'Monthly' : 'Full (One Time)' }}</td>
                        <td class="text-end">{{ $advance->deduction_type === 'monthly' ? number_format($advance->monthly_deduction, 2) : number_format($advance->amount, 2) }}</td>
                        <td>
                            @if($advance->is_cleared)
                                <span class="badge bg-label-success">Cleared</span>
                            @else
                                <span class="badge bg-label-warning" title="Balance: ₹{{ number_format($advance->balance, 2) }}">Pending (Bal: {{ number_format($advance->balance, 2) }})</span>
                            @endif
                        </td>
                        <td>{{ $advance->date->format('d-m-Y') }}</td>
                        <td>{{ $advance->remark ?? '-' }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.driver-management.advance.edit', $advance) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                            <form method="POST" action="{{ route('admin.driver-management.advance.destroy', $advance) }}" class="d-inline" onsubmit="return confirm('Delete this advance record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No advances recorded yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.querySelectorAll('input[name="deduction_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('monthly_deduction_field').style.display = this.value === 'monthly' ? '' : 'none';
    });
});
</script>
@endsection
