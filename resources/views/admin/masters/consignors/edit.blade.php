@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Edit Consignor</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.masters.consignors.index') }}">Consignors</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.masters.consignors.update', $consignor->id) }}">
                @csrf @method('PUT')
                @if(auth()->user()->isSuperAdmin())
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><label class="form-label">Company *</label><select name="company_id" id="company_id" class="form-select" required><option value="">Select Company</option>@foreach(\App\Models\Company::where('status','active')->orWhere('id', $consignor->company_id)->get() as $c)<option value="{{ $c->id }}" {{ old('company_id', $consignor->company_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Branch</label><select name="branch_id" id="branch_id" class="form-select"><option value="">Select Branch</option></select></div>
                </div>
                @endif
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Vendor Code</label><input type="text" name="vendor_code" class="form-control" value="{{ old('vendor_code', $consignor->vendor_code) }}"></div>
                    <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="{{ old('name', $consignor->name) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $consignor->phone) }}"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $consignor->email) }}"></div>
                    <div class="col-md-4"><label class="form-label">GSTIN</label><input type="text" name="gstin" class="form-control" value="{{ old('gstin', $consignor->gstin) }}"></div>
                    <div class="col-md-4"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="{{ old('city', $consignor->city) }}"></div>
                    <div class="col-md-6"><label class="form-label">State</label><input type="text" name="state" class="form-control" value="{{ old('state', $consignor->state) }}"></div>
                    <div class="col-md-6"><label class="form-label">Pincode</label><input type="text" name="pincode" class="form-control" value="{{ old('pincode', $consignor->pincode) }}"></div>
                    <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2">{{ old('address', $consignor->address) }}</textarea></div>
                </div>
                <div class="mt-4 d-grid gap-2 d-md-flex"><button type="submit" class="btn btn-primary">Update Consignor</button> <a href="{{ route('admin.masters.consignors.index') }}" class="btn btn-secondary">Cancel</a></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
@if(auth()->user()->isSuperAdmin())
<script>
    $(document).ready(function() {
        function loadBranches(companyId, selectedBranchId, selectedBranchName) {
            if (companyId) {
                $.ajax({
                    url: '{{ url("admin/users/get-branches") }}/' + companyId,
                    type: 'GET',
                    success: function(data) {
                        $('#branch_id').empty();
                        $('#branch_id').append('<option value="">Select Branch</option>');
                        var found = false;
                        $.each(data, function(key, branch) {
                            var selected = branch.id == selectedBranchId ? 'selected' : '';
                            if (selected) found = true;
                            $('#branch_id').append('<option value="' + branch.id + '" ' + selected + '>' + branch.name + '</option>');
                        });
                        if (selectedBranchId && !found && selectedBranchName) {
                            $('#branch_id').append('<option value="' + selectedBranchId + '" selected>' + selectedBranchName + '</option>');
                        }
                        $('#branch_id').trigger('change');
                    }
                });
            } else {
                $('#branch_id').empty();
                $('#branch_id').append('<option value="">Select Branch</option>').trigger('change');
            }
        }

        var initialCompanyId = $('#company_id').val();
        if (initialCompanyId) {
            loadBranches(initialCompanyId, "{{ old('branch_id', $consignor->branch_id) }}", "{{ $consignor->branch->name ?? '' }}");
        }

        $('#company_id').change(function() {
            loadBranches($(this).val(), null);
        });
    });
</script>
@endif
@endsection
