@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-0">Edit Branch</h5>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.branches.index') }}">Branches</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.branches.update', $branch->id) }}" method="POST" id="branchForm">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Company *</label><select name="company_id"
                                class="form-select @error('company_id') is-invalid @enderror" required>
                                <option value="">Select Company</option>
                                @foreach (\App\Models\Company::where('status', 'active')->orWhere('id', $branch->company_id)->orderBy('name')->get() as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id', $branch->company_id) == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6"><label class="form-label">Name *</label><input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $branch->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $branch->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $branch->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6"><label class="form-label">State *</label><select name="state" id="state"
                                class="form-select @error('state') is-invalid @enderror" required>
                                <option value="">Select State</option>
                                @foreach (array_keys($states) as $stateName)
                                    <option value="{{ $stateName }}"
                                        {{ old('state', $branch->state) == $stateName ? 'selected' : '' }}>
                                        {{ $stateName }}</option>
                                @endforeach
                            </select>
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6"><label class="form-label">City *</label><select name="city" id="city"
                                class="form-select @error('city') is-invalid @enderror" required>
                                <option value="">Select City</option>
                            </select>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12"><label class="form-label">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $branch->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4 d-grid gap-2 d-md-flex"><button type="submit" class="btn btn-primary"><i
                                class="bx bx-save me-1"></i> Update</button> <a href="{{ route('admin.branches.index') }}"
                            class="btn btn-secondary">Cancel</a></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const statesCitiesData = @json($cities);
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');
            const currentCity = "{{ $branch->city }}";

            function loadCities() {
                const selectedState = stateSelect.value;
                citySelect.innerHTML = '<option value="">Select City</option>';
                if (selectedState && statesCitiesData[selectedState]) {
                    statesCitiesData[selectedState].forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        if (city === currentCity) option.selected = true;
                        citySelect.appendChild(option);
                    });
                    $('#city').trigger('change');
                }
            }

            $('#state').on('change', function() {
                loadCities();
            });
            if (stateSelect.value) {
                loadCities();
            }

            $('#branchForm').validate({
                rules: {
                    name: {
                        required: true,
                        maxlength: 255
                    },
                    email: {
                        email: true
                    },
                    phone: {
                        digits: true,
                        minlength: 10,
                        maxlength: 15
                    },
                    state: {
                        required: true
                    },
                    city: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Branch name is required",
                        maxlength: "Branch name cannot exceed 255 characters"
                    },
                    email: {
                        email: "Please enter a valid email address"
                    },
                    phone: {
                        digits: "Phone number must contain digits only",
                        minlength: "Phone number must be at least 10 digits",
                        maxlength: "Phone number cannot exceed 15 digits"
                    },
                    state: {
                        required: "State is required"
                    },
                    city: {
                        required: "City is required"
                    }
                },
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    error.insertAfter(element);
                    element.addClass('is-invalid');
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid').removeClass('is-valid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid').addClass('is-valid');
                }
            });
        });
    </script>
@endsection
