@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Add Spare Part</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Maintenance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.maintenance.spare-part.index') }}">Spare Parts</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">New Spare Part</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.maintenance.spare-part.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Part Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Part Number</label>
                        <input type="text" name="part_number" class="form-control @error('part_number') is-invalid @enderror" value="{{ old('part_number') }}">
                        @error('part_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror">
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->vehicle_number }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" min="0" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 0) }}" oninput="calcAmount()">
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Unit Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" id="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price', 0) }}" oninput="calcAmount()">
                        @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Amount (₹)</label>
                        <input type="text" id="amount_display" class="form-control" value="0.00" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Part Change Date</label>
                    <input type="date" max="9999-12-31" name="part_change_date" class="form-control @error('part_change_date') is-invalid @enderror" value="{{ old('part_change_date') }}">
                    @error('part_change_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Save Part</button>
                <a href="{{ route('admin.maintenance.spare-part.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function calcAmount() {
    var qty = parseFloat(document.querySelector('[name="quantity"]').value) || 0;
    var price = parseFloat(document.querySelector('[name="unit_price"]').value) || 0;
    document.getElementById('amount_display').value = (qty * price).toFixed(2);
}
document.querySelector('[name="quantity"]').addEventListener('input', calcAmount);
</script>
@endsection
