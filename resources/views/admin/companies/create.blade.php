@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Add Company</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.companies.index') }}">Companies</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.companies.store') }}" method="POST" enctype="multipart/form-data" id="companyForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Name *</label><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">GST Number</label><input type="text" name="gst_number" class="form-control @error('gst_number') is-invalid @enderror" value="{{ old('gst_number') }}">@error('gst_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">PAN Number</label><input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number') }}">@error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Authorized Signatory / Owner Name</label><input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}">@error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">HSN/SAC Code</label><input type="text" name="hsn_code" class="form-control @error('hsn_code') is-invalid @enderror" value="{{ old('hsn_code') }}">@error('hsn_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6">
                        <label class="form-label">State</label>
                        <select name="state" class="form-select @error('state') is-invalid @enderror">
                            <option value="">-- Select State --</option>
                            @php
                            $indianStates = [
                                'Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat',
                                'Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh',
                                'Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab',
                                'Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh',
                                'Uttarakhand','West Bengal',
                                'Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu',
                                'Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'
                            ];
                            @endphp
                            @foreach($indianStates as $state)
                                <option value="{{ $state }}" {{ old('state') == $state ? 'selected' : '' }}>{{ $state }}</option>
                            @endforeach
                        </select>
                        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address') }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label class="form-label">Declaration (For Bills & Invoices)</label><textarea name="declaration" class="form-control @error('declaration') is-invalid @enderror" rows="2" placeholder="e.g. GST payable by recipient under Reverse Charge (RCM) on GTA services.">{{ old('declaration') }}</textarea>@error('declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label class="form-label">Disclaimer</label><textarea name="disclaimer" class="form-control @error('disclaimer') is-invalid @enderror" rows="3">{{ old('disclaimer') }}</textarea>@error('disclaimer')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>

                <div class="mt-4"><h6 class="fw-bold">Bank Details</h6><hr class="mt-1"></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Account Holder Name</label><input type="text" name="bank_holder_name" class="form-control @error('bank_holder_name') is-invalid @enderror" value="{{ old('bank_holder_name') }}">@error('bank_holder_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}">@error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Account Number</label><input type="text" name="bank_account_no" class="form-control @error('bank_account_no') is-invalid @enderror" value="{{ old('bank_account_no') }}">@error('bank_account_no')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">IFSC Code</label><input type="text" name="bank_ifsc" class="form-control @error('bank_ifsc') is-invalid @enderror" value="{{ old('bank_ifsc') }}">@error('bank_ifsc')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Branch</label><input type="text" name="bank_branch" class="form-control @error('bank_branch') is-invalid @enderror" value="{{ old('bank_branch') }}">@error('bank_branch')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Digital Signature</label><input type="file" name="digital_signature" class="form-control @error('digital_signature') is-invalid @enderror" accept="image/*">@error('digital_signature')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
                <div class="mt-4 d-grid gap-2 d-md-flex"><button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save</button> <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Cancel</a></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script>
$(document).ready(function() {

    $('#companyForm').validate({
        rules: {
            name: { required: true, maxlength: 255 },
            email: { email: true },
            phone: { digits: true, minlength: 10, maxlength: 15 }
        },
        messages: {
            name: { required: "Company name is required", maxlength: "Company name cannot exceed 255 characters" },
            email: { email: "Please enter a valid email address" },
            phone: { digits: "Phone number must contain digits only", minlength: "Phone number must be at least 10 digits", maxlength: "Phone number cannot exceed 15 digits" }
        },
        errorPlacement: function(error, element) { error.addClass('invalid-feedback'); error.insertAfter(element); element.addClass('is-invalid'); },
        highlight: function(element) { $(element).addClass('is-invalid').removeClass('is-valid'); },
        unhighlight: function(element) { $(element).removeClass('is-invalid').addClass('is-valid'); }
    });
});
</script>
@endsection
