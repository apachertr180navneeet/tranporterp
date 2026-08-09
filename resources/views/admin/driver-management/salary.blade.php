@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Salary Management</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.driver-management.salary') }}">Driver Salary</a></li>
                    <li class="breadcrumb-item active">Salary Management</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.driver-management.advance') }}" class="btn btn-outline-primary"><i class="bx bx-coin me-1"></i> Advance Management</a>
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
            <h5 class="mb-0">{{ $editSalary ? 'Edit Salary' : 'Assign Salary to Driver' }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $editSalary ? route('admin.driver-management.salary.update', $editSalary) : route('admin.driver-management.salary.store') }}">
                @csrf
                @if($editSalary) @method('PUT') @endif
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Driver <span class="text-danger">*</span></label>
                        <select name="driver_id" class="form-select @error('driver_id') is-invalid @enderror" required>
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id', $editSalary?->driver_id) == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->phone ?? 'N/A' }}) @if($driver->driver_id)[{{ $driver->driver_id }}]@endif</option>
                            @endforeach
                        </select>
                        @error('driver_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Salary Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="salary_amount" class="form-control @error('salary_amount') is-invalid @enderror" value="{{ old('salary_amount', $editSalary?->salary_amount) }}" required>
                        @error('salary_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" max="9999-12-31" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $editSalary?->effective_from?->format('Y-m-d')) }}" required>
                        @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" max="9999-12-31" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $editSalary?->effective_to?->format('Y-m-d')) }}">
                        @error('effective_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editSalary ? 'Update Salary' : 'Assign Salary' }}</button>
                    @if($editSalary)
                        <a href="{{ route('admin.driver-management.salary') }}" class="btn btn-outline-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Driver Salary List</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Driver</th>
                        <th>Driver ID</th>
                        <th>Phone</th>
                        <th class="text-end">Salary (₹)</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salaries as $salary)
                    <tr>
                        <td>{{ $salary->driver?->name ?? '-' }}</td>
                        <td>{{ $salary->driver?->driver_id ?? '-' }}</td>
                        <td>{{ $salary->driver?->phone ?? '-' }}</td>
                        <td class="text-end">{{ number_format($salary->salary_amount, 2) }}</td>
                        <td>{{ $salary->effective_from->format('d-m-Y') }}</td>
                        <td>{{ $salary->effective_to ? $salary->effective_to->format('d-m-Y') : '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.driver-management.salary.edit', $salary) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No salaries assigned yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
