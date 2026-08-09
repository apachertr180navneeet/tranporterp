@extends('admin.layouts.app')

@section('style')
<style>
.select2-container--default .select2-selection--single { height: 38px; border: 1px solid #d9dee3; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
.select2-container { width: 100% !important; }
</style>
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Edit Fuel Pump</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.masters.fuel-pumps.index') }}">Fuel Pumps</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.masters.fuel-pumps.update', $fuelPump->id) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fuel Company</label>
                        <select name="fuel_company_id" class="form-select select2 @error('fuel_company_id') is-invalid @enderror">
                            <option value="">Select Fuel Company</option>
                            @foreach($fuelCompanies as $fuelCompany)
                            <option value="{{ $fuelCompany->id }}" {{ old('fuel_company_id', $fuelPump->fuel_company_id) == $fuelCompany->id ? 'selected' : '' }}>{{ $fuelCompany->name }}</option>
                            @endforeach
                            </select>
                        @error('fuel_company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fuel Pump Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $fuelPump->name) }}" placeholder="Enter pump name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Number</label>
                        <input type="text" name="number" class="form-control @error('number') is-invalid @enderror" value="{{ old('number', $fuelPump->number) }}" placeholder="Pump number / code">
                        @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner Name</label>
                        <input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name', $fuelPump->owner_name) }}" placeholder="Owner name">
                        @error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner Mobile</label>
                        <input type="text" name="owner_mobile" class="form-control @error('owner_mobile') is-invalid @enderror" value="{{ old('owner_mobile', $fuelPump->owner_mobile) }}" placeholder="Owner mobile number">
                        @error('owner_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Pump address">{{ old('address', $fuelPump->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('admin.masters.fuel-pumps.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    if (typeof window.initGlobalSelect2 === 'function') {
        window.initGlobalSelect2();
    } else if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
    }
});
</script>
@endsection
