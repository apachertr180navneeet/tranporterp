@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Edit User</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}">
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}">
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                            <span class="input-group-text cursor-pointer" onclick="togglePassword('password')" style="background: transparent;">
                                <i class="bx bx-hide" style="color: #8c98a8; cursor: pointer;"></i>
                            </span>
                        </div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                            <span class="input-group-text cursor-pointer" onclick="togglePassword('password_confirmation')" style="background: transparent;">
                                <i class="bx bx-hide" style="color: #8c98a8; cursor: pointer;"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company</label>
                        <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror">
                            <option value="">Select Company</option>
                            @foreach(\App\Models\Company::where('status','active')->orWhere('id', $user->company_id)->orderBy('name')->get() as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                            <option value="">Select Branch</option>
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="">Select Role</option>
                            @foreach($roles->pluck('name') as $role)
                                <option value="{{ $role }}" {{ old('role', $user->roles->pluck('name')->first()) == $role ? 'selected' : '' }}>{{ $role }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">State</label>
                        <select name="state" id="state" class="form-select @error('state') is-invalid @enderror">
                            <option value="">Select State</option>
                            @foreach(array_keys($states) as $stateName)
                                <option value="{{ $stateName }}" {{ old('state', $user->state) == $stateName ? 'selected' : '' }}>{{ $stateName }}</option>
                            @endforeach
                        </select>
                        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <select name="city" id="city" class="form-select @error('city') is-invalid @enderror">
                            <option value="">Select City</option>
                        </select>
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $user->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Update User</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
window.togglePassword = function(id) {
    const input = document.getElementById(id);
    const parent = input.closest('.input-group');
    const icon = parent ? parent.querySelector('.bx-hide, .bx-show') : null;
    if (input && icon) {
        if (input.type === 'password') { input.type = 'text'; icon.classList.replace('bx-hide', 'bx-show'); }
        else { input.type = 'password'; icon.classList.replace('bx-show', 'bx-hide'); }
    }
}

$(document).ready(function() {
    const stateSelect = document.getElementById('state');
    const citySelect = document.getElementById('city');
    const currentCity = "{{ $user->city }}";

    function loadCities() {
        const selectedState = stateSelect.value;
        citySelect.innerHTML = '<option value="">Select City</option>';
        if (!selectedState) return;
        $.ajax({
            url: '{{ route("admin.users.get-cities") }}',
            type: 'GET',
            data: { state: selectedState },
            success: function(data) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                data.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    if (city === currentCity) option.selected = true;
                    citySelect.appendChild(option);
                });
            }
        });
    }

    $('#state').on('change', function() { loadCities(); });
    if (stateSelect.value) { loadCities(); }

    function loadBranches(companyId, selectedBranchId, selectedBranchName) {
        if (companyId) {
            $.ajax({
                url: '{{ route("admin.users.get-branches") }}',
                type: 'GET',
                data: { company_id: companyId },
                success: function(data) {
                    $('#branch_id').empty().append('<option value="">Select Branch</option>');
                    var found = false;
                    $.each(data, function(key, value) {
                        var selected = value.id == selectedBranchId ? 'selected' : '';
                        if (selected) found = true;
                        $('#branch_id').append('<option value="' + value.id + '" ' + selected + '>' + value.name + '</option>');
                    });
                    if (selectedBranchId && !found && selectedBranchName) {
                        $('#branch_id').append('<option value="' + selectedBranchId + '" selected>' + selectedBranchName + '</option>');
                    }
                    $('#branch_id').trigger('change');
                }
            });
        } else {
            $('#branch_id').empty().append('<option value="">Select Branch</option>').trigger('change');
        }
    }

    var initialCompanyId = $('#company_id').val();
    if (initialCompanyId) {
        loadBranches(initialCompanyId, "{{ old('branch_id', $user->branch_id) }}", "{{ $user->branch->name ?? '' }}");
    }

    $('#company_id').change(function() {
        loadBranches($(this).val(), null, null);
    });
});
</script>
@endsection
