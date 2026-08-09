@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Add Maintenance History</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Maintenance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.maintenance.maintenance-history.index') }}">Maintenance History</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">New Maintenance Record</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.maintenance.maintenance-history.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->vehicle_number }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Service Type <span class="text-danger">*</span></label>
                        <select name="service_type" class="form-select @error('service_type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            <option value="Oil Change" {{ old('service_type') == 'Oil Change' ? 'selected' : '' }}>Oil Change</option>
                            <option value="General Service" {{ old('service_type') == 'General Service' ? 'selected' : '' }}>General Service</option>
                            <option value="Tire Replacement" {{ old('service_type') == 'Tire Replacement' ? 'selected' : '' }}>Tire Replacement</option>
                            <option value="Brake Service" {{ old('service_type') == 'Brake Service' ? 'selected' : '' }}>Brake Service</option>
                            <option value="Transmission" {{ old('service_type') == 'Transmission' ? 'selected' : '' }}>Transmission</option>
                            <option value="Battery Replacement" {{ old('service_type') == 'Battery Replacement' ? 'selected' : '' }}>Battery Replacement</option>
                            <option value="AC Service" {{ old('service_type') == 'AC Service' ? 'selected' : '' }}>AC Service</option>
                            <option value="Engine Repair" {{ old('service_type') == 'Engine Repair' ? 'selected' : '' }}>Engine Repair</option>
                            <option value="Clutch Replacement" {{ old('service_type') == 'Clutch Replacement' ? 'selected' : '' }}>Clutch Replacement</option>
                            <option value="Electrical Repair" {{ old('service_type') == 'Electrical Repair' ? 'selected' : '' }}>Electrical Repair</option>
                            <option value="Body Repair" {{ old('service_type') == 'Body Repair' ? 'selected' : '' }}>Body Repair</option>
                            <option value="Other" {{ old('service_type') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('service_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Service Date <span class="text-danger">*</span></label>
                        <input type="date" max="9999-12-31" name="service_date" class="form-control @error('service_date') is-invalid @enderror" value="{{ old('service_date', date('Y-m-d')) }}" required>
                        @error('service_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current KM</label>
                        <input type="number" step="0.01" min="0" name="current_km" class="form-control @error('current_km') is-invalid @enderror" value="{{ old('current_km') }}" placeholder="Odometer reading">
                        @error('current_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }} {{ $vendor->vendor_code ? '(' . $vendor->vendor_code . ')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cost (₹)</label>
                        <input type="number" step="0.01" min="0" name="cost" class="form-control @error('cost') is-invalid @enderror" value="{{ old('cost') }}" placeholder="0.00">
                        @error('cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="What was done?">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <h6 class="mt-3 mb-2 fw-semibold">Linked Records (Optional)</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Service Schedule</label>
                        <select name="service_schedule_id" class="form-select @error('service_schedule_id') is-invalid @enderror">
                            <option value="">None</option>
                            @foreach($serviceSchedules as $schedule)
                                <option value="{{ $schedule->id }}" {{ old('service_schedule_id') == $schedule->id ? 'selected' : '' }}>{{ $schedule->service_type }} - {{ $schedule->vehicle?->vehicle_number }} ({{ $schedule->scheduled_date?->format('d-m-Y') }})</option>
                            @endforeach
                        </select>
                        @error('service_schedule_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Spare Part Used</label>
                        <select name="spare_part_id" class="form-select @error('spare_part_id') is-invalid @enderror">
                            <option value="">None</option>
                            @foreach($spareParts as $part)
                                <option value="{{ $part->id }}" {{ old('spare_part_id') == $part->id ? 'selected' : '' }}>{{ $part->name }} ({{ $part->part_number ?: 'No part #' }})</option>
                            @endforeach
                        </select>
                        @error('spare_part_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="completed" {{ old('status', 'completed') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h6 class="mt-3 mb-2 fw-semibold">Next Service (Optional)</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Next Service Date</label>
                        <input type="date" max="9999-12-31" name="next_service_date" class="form-control @error('next_service_date') is-invalid @enderror" value="{{ old('next_service_date') }}">
                        @error('next_service_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Next Service KM</label>
                        <input type="number" step="0.01" min="0" name="next_service_km" class="form-control @error('next_service_km') is-invalid @enderror" value="{{ old('next_service_km') }}" placeholder="Odometer at next service">
                        @error('next_service_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Record</button>
                <a href="{{ route('admin.maintenance.maintenance-history.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
