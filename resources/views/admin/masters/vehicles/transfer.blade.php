@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Transfer Vehicle</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.masters.vehicles.index') }}">Vehicles</a></li>
                    <li class="breadcrumb-item active">Transfer</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Transferring:</strong> {{ $vehicle->vehicle_number }}<br>
                <strong>Current Company:</strong> {{ $vehicle->company->name ?? 'N/A' }} |
                <strong>Current Branch:</strong> {{ $vehicle->branch->name ?? 'N/A' }}
            </div>
            <form method="POST" action="{{ route('admin.masters.vehicles.transfer.update', $vehicle->id) }}">
                @csrf @method('PUT')
                @if(auth()->user()->isSuperAdmin())
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Transfer to Company *</label>
                        <select name="company_id" id="company_id" class="form-select select2" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ $vehicle->company_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Transfer to Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select select2">
                            <option value="">Select Branch</option>
                        </select>
                    </div>
                </div>
                @else
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Transfer to Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select select2">
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $vehicle->branch_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                <div class="mt-4 d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-warning"><i class="bx bx-transfer-alt"></i> Transfer Vehicle</button>
                    <a href="{{ route('admin.masters.vehicles.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
@if(auth()->user()->isSuperAdmin())
<script>
    $(document).ready(function() {
        function loadBranches(companyId, selectedBranchId) {
            if (companyId) {
                $.ajax({
                    url: '{{ url("admin/users/get-branches") }}/' + companyId,
                    type: 'GET',
                    success: function(data) {
                        $('#branch_id').empty();
                        $('#branch_id').append('<option value="">Select Branch</option>');
                        $.each(data, function(key, branch) {
                            var selected = branch.id == selectedBranchId ? 'selected' : '';
                            $('#branch_id').append('<option value="' + branch.id + '" ' + selected + '>' + branch.name + '</option>');
                        });
                        $('#branch_id').trigger('change');
                    }
                });
            } else {
                $('#branch_id').empty();
                $('#branch_id').append('<option value="">Select Branch</option>').trigger('change');
            }
        }

        var initialCompanyId = $('#company_id').val();
        var initialBranchId = "{{ $vehicle->branch_id }}";
        if (initialCompanyId) {
            loadBranches(initialCompanyId, initialBranchId);
        }

        $('#company_id').change(function() {
            loadBranches($(this).val(), null);
        });
    });
</script>
@endif
@endsection
