@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Edit Tyre Record</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Maintenance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.maintenance.tyre-management.index') }}">Tyre Management</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Tyre Record</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.maintenance.tyre-management.update', $tyreManagement) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $tyreManagement->vehicle_id) == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->vehicle_number }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tyre Position <span class="text-danger">*</span></label>
                        <select name="tyre_position" class="form-select @error('tyre_position') is-invalid @enderror" required>
                            <option value="">Select Position</option>
                            <optgroup label="Left Side (8 Slots)">
                                <option value="L1" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L1' ? 'selected' : '' }}>L1 - Front Left (Steering)</option>
                                <option value="L2" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L2' ? 'selected' : '' }}>L2 - Drive 1 Left Outer</option>
                                <option value="L3" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L3' ? 'selected' : '' }}>L3 - Drive 1 Left Inner</option>
                                <option value="L4" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L4' ? 'selected' : '' }}>L4 - Drive 2 Left Outer</option>
                                <option value="L5" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L5' ? 'selected' : '' }}>L5 - Drive 2 Left Inner</option>
                                <option value="L6" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L6' ? 'selected' : '' }}>L6 - Axle 4 Left Outer</option>
                                <option value="L7" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L7' ? 'selected' : '' }}>L7 - Axle 4 Left Inner</option>
                                <option value="L8" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L8' ? 'selected' : '' }}>L8 - Axle 5 Left Outer 4</option>
                                <option value="L9" {{ old('tyre_position', $tyreManagement->tyre_position) == 'L9' ? 'selected' : '' }}>L9 - Axle 5 Left Inner 4</option>
                            </optgroup>
                            <optgroup label="Right Side (9 Slots)">
                                <option value="R1" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R1' ? 'selected' : '' }}>R1 - Front Right (Steering)</option>
                                <option value="R2" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R2' ? 'selected' : '' }}>R2 - Drive 1 Right Outer</option>
                                <option value="R3" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R3' ? 'selected' : '' }}>R3 - Drive 1 Right Inner</option>
                                <option value="R4" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R4' ? 'selected' : '' }}>R4 - Drive 2 Right Outer</option>
                                <option value="R5" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R5' ? 'selected' : '' }}>R5 - Drive 2 Right Inner</option>
                                <option value="R6" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R6' ? 'selected' : '' }}>R6 - Axle 4 Right Outer</option>
                                <option value="R7" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R7' ? 'selected' : '' }}>R7 - Axle 4 Right Inner</option>
                                <option value="R8" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R8' ? 'selected' : '' }}>R8 - Axle 5 Right Outer 4</option>
                                <option value="R9" {{ old('tyre_position', $tyreManagement->tyre_position) == 'R9' ? 'selected' : '' }}>R9 - Axle 5 Right Inner 4</option>
                            </optgroup>
                            <optgroup label="Spare Carriers (2 Slots)">
                                <option value="SP1" {{ old('tyre_position', $tyreManagement->tyre_position) == 'SP1' ? 'selected' : '' }}>SP1 - Spare 1 (Stepney)</option>
                                <option value="SP2" {{ old('tyre_position', $tyreManagement->tyre_position) == 'SP2' ? 'selected' : '' }}>SP2 - Spare 2 (Stepney)</option>
                            </optgroup>
                            <optgroup label="General / Legacy">
                                <option value="Unassigned" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Unassigned' ? 'selected' : '' }}>Unassigned Pool</option>
                                <option value="Front Left" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Front Left' ? 'selected' : '' }}>Front Left</option>
                                <option value="Front Right" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Front Right' ? 'selected' : '' }}>Front Right</option>
                                <option value="Rear Left Outer" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Rear Left Outer' ? 'selected' : '' }}>Rear Left Outer</option>
                                <option value="Rear Left Inner" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Rear Left Inner' ? 'selected' : '' }}>Rear Left Inner</option>
                                <option value="Rear Right Outer" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Rear Right Outer' ? 'selected' : '' }}>Rear Right Outer</option>
                                <option value="Rear Right Inner" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Rear Right Inner' ? 'selected' : '' }}>Rear Right Inner</option>
                                <option value="Other" {{ old('tyre_position', $tyreManagement->tyre_position) == 'Other' ? 'selected' : '' }}>Other</option>
                            </optgroup>
                        </select>
                        @error('tyre_position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tyre Brand <span class="text-danger">*</span></label>
                        <select name="tyre_brand" id="tyre_brand_select" class="form-select @error('tyre_brand') is-invalid @enderror" required>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->name }}" data-id="{{ $brand->id }}" {{ old('tyre_brand', $tyreManagement->tyre_brand) == $brand->name ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                            @if(!empty($tyreManagement->tyre_brand) && !$brands->pluck('name')->contains($tyreManagement->tyre_brand))
                                <option value="{{ $tyreManagement->tyre_brand }}" selected>{{ $tyreManagement->tyre_brand }}</option>
                            @endif
                        </select>
                        @error('tyre_brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tyre Model</label>
                        <select name="tyre_model" id="tyre_model_select" class="form-select @error('tyre_model') is-invalid @enderror">
                            <option value="">Select Model</option>
                            @foreach($models as $model)
                                <option value="{{ $model->name }}" data-id="{{ $model->id }}" {{ old('tyre_model', $tyreManagement->tyre_model) == $model->name ? 'selected' : '' }}>{{ $model->name }}</option>
                            @endforeach
                            @if(!empty($tyreManagement->tyre_model) && !$models->pluck('name')->contains($tyreManagement->tyre_model))
                                <option value="{{ $tyreManagement->tyre_model }}" selected>{{ $tyreManagement->tyre_model }}</option>
                            @endif
                        </select>
                        @error('tyre_model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tyre Size <span class="text-danger">*</span></label>
                        <select name="tyre_size" id="tyre_size_select" class="form-select @error('tyre_size') is-invalid @enderror" required>
                            <option value="">Select Size</option>
                            @foreach($sizes as $size)
                                <option value="{{ $size->name }}" {{ old('tyre_size', $tyreManagement->tyre_size) == $size->name ? 'selected' : '' }}>{{ $size->name }}</option>
                            @endforeach
                            @if(!empty($tyreManagement->tyre_size) && !$sizes->pluck('name')->contains($tyreManagement->tyre_size))
                                <option value="{{ $tyreManagement->tyre_size }}" selected>{{ $tyreManagement->tyre_size }}</option>
                            @endif
                        </select>
                        @error('tyre_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number', $tyreManagement->serial_number) }}">
                        @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h6 class="mt-3 mb-2 fw-semibold">Purchase & Installation</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" max="9999-12-31" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', $tyreManagement->purchase_date?->format('Y-m-d')) }}">
                        @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Purchase Cost (₹)</label>
                        <input type="number" step="0.01" min="0" name="purchase_cost" class="form-control @error('purchase_cost') is-invalid @enderror" value="{{ old('purchase_cost', $tyreManagement->purchase_cost) }}">
                        @error('purchase_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Installation Date</label>
                        <input type="date" max="9999-12-31" name="installation_date" class="form-control @error('installation_date') is-invalid @enderror" value="{{ old('installation_date', $tyreManagement->installation_date?->format('Y-m-d')) }}">
                        @error('installation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Installation KM</label>
                        <input type="number" step="0.01" min="0" name="installation_km" class="form-control @error('installation_km') is-invalid @enderror" value="{{ old('installation_km', $tyreManagement->installation_km) }}">
                        @error('installation_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h6 class="mt-3 mb-2 fw-semibold">Tread & Pressure</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tread Depth New (mm)</label>
                        <input type="number" step="0.01" min="0" name="tread_depth_new" class="form-control @error('tread_depth_new') is-invalid @enderror" value="{{ old('tread_depth_new', $tyreManagement->tread_depth_new) }}">
                        @error('tread_depth_new') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tread Depth Current (mm)</label>
                        <input type="number" step="0.01" min="0" name="tread_depth_current" class="form-control @error('tread_depth_current') is-invalid @enderror" value="{{ old('tread_depth_current', $tyreManagement->tread_depth_current) }}">
                        @error('tread_depth_current') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Pressure (PSI)</label>
                        <input type="number" step="0.1" min="0" name="pressure_psi" class="form-control @error('pressure_psi') is-invalid @enderror" value="{{ old('pressure_psi', $tyreManagement->pressure_psi) }}">
                        @error('pressure_psi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', $tyreManagement->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="removed" {{ old('status', $tyreManagement->status) == 'removed' ? 'selected' : '' }}>Removed</option>
                            <option value="scrap" {{ old('status', $tyreManagement->status) == 'scrap' ? 'selected' : '' }}>Scrap</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h6 class="mt-3 mb-2 fw-semibold">Removal Details (Optional)</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Removal Date</label>
                        <input type="date" max="9999-12-31" name="removal_date" class="form-control @error('removal_date') is-invalid @enderror" value="{{ old('removal_date', $tyreManagement->removal_date?->format('Y-m-d')) }}">
                        @error('removal_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Removal KM</label>
                        <input type="number" step="0.01" min="0" name="removal_km" class="form-control @error('removal_km') is-invalid @enderror" value="{{ old('removal_km', $tyreManagement->removal_km) }}">
                        @error('removal_km') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Removal Reason</label>
                        <select name="removal_reason" class="form-select @error('removal_reason') is-invalid @enderror">
                            <option value="">Select Reason</option>
                            <option value="Worn Out" {{ old('removal_reason', $tyreManagement->removal_reason) == 'Worn Out' ? 'selected' : '' }}>Worn Out</option>
                            <option value="Puncture / Damage" {{ old('removal_reason', $tyreManagement->removal_reason) == 'Puncture / Damage' ? 'selected' : '' }}>Puncture / Damage</option>
                            <option value="Burst" {{ old('removal_reason', $tyreManagement->removal_reason) == 'Burst' ? 'selected' : '' }}>Burst</option>
                            <option value="Retread" {{ old('removal_reason', $tyreManagement->removal_reason) == 'Retread' ? 'selected' : '' }}>Retread</option>
                            <option value="Upgraded" {{ old('removal_reason', $tyreManagement->removal_reason) == 'Upgraded' ? 'selected' : '' }}>Upgraded</option>
                            <option value="Other" {{ old('removal_reason', $tyreManagement->removal_reason) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('removal_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $tyreManagement->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Tyre Record</button>
                <a href="{{ route('admin.maintenance.tyre-management.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('#tyre_brand_select').on('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        const brandId = selectedOpt ? selectedOpt.getAttribute('data-id') : null;

        if (!brandId) return;

        // Load models for selected brand
        fetch("{{ url('admin/masters/tyre-models/get-by-brand') }}/" + brandId)
            .then(res => res.json())
            .then(models => {
                let html = '<option value="">Select Model</option>';
                models.forEach(m => {
                    html += `<option value="${m.name}" data-id="${m.id}">${m.name}</option>`;
                });
                $('#tyre_model_select').html(html).trigger('change');
            });

        // Load sizes for selected brand
        fetch("{{ url('admin/masters/tyre-sizes/get-by-brand') }}/" + brandId)
            .then(res => res.json())
            .then(sizes => {
                let html = '<option value="">Select Size</option>';
                sizes.forEach(s => {
                    html += `<option value="${s.name}">${s.name}</option>`;
                });
                $('#tyre_size_select').html(html).trigger('change');
            });
    });

    $('#tyre_model_select').on('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        const modelId = selectedOpt ? selectedOpt.getAttribute('data-id') : null;

        if (!modelId) return;

        // Load sizes for selected model
        fetch("{{ url('admin/masters/tyre-sizes/get-by-model') }}/" + modelId)
            .then(res => res.json())
            .then(sizes => {
                if (sizes.length > 0) {
                    let html = '<option value="">Select Size</option>';
                    sizes.forEach(s => {
                        html += `<option value="${s.name}">${s.name}</option>`;
                    });
                    $('#tyre_size_select').html(html).trigger('change');
                }
            });
    });
});
</script>
@endsection

