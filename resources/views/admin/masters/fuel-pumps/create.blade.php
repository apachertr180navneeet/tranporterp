@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Add Fuel Pump</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.masters.fuel-pumps.index') }}">Fuel Pumps</a></li>
                    <li class="breadcrumb-item active">Add Fuel Pump</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.masters.fuel-pumps.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fuel Company</label>
                        <select name="fuel_company_id" class="form-select @error('fuel_company_id') is-invalid @enderror">
                            <option value="">Select Fuel Company</option>
                            @foreach($fuelCompanies as $fuelCompany)
                            <option value="{{ $fuelCompany->id }}" {{ old('fuel_company_id') == $fuelCompany->id ? 'selected' : '' }}>{{ $fuelCompany->name }}</option>
                            @endforeach
                            </select>
                        @error('fuel_company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fuel Pump Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter pump name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Number</label>
                        <input type="text" name="number" class="form-control @error('number') is-invalid @enderror" value="{{ old('number') }}" placeholder="Pump number / code">
                        @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner Name</label>
                        <input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}" placeholder="Owner name">
                        @error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner Mobile</label>
                        <input type="text" name="owner_mobile" class="form-control @error('owner_mobile') is-invalid @enderror" value="{{ old('owner_mobile') }}" placeholder="Owner mobile number">
                        @error('owner_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Pump address">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('admin.masters.fuel-pumps.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
