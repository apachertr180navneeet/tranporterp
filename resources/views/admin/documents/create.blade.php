@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Document Management /</span> Upload Document
            </h4>
            <p class="text-muted mb-0">Upload new documents, assign categories, nested folders, expiry dates, and tags.</p>
        </div>
        <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to Documents
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">

                    <!-- Company Selection (if Super Admin) -->
                    @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Company</label>
                        <select name="company_id" id="company_id" class="form-select" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" {{ $companyId == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="company_id" id="company_id" value="{{ auth()->user()->company_id }}">
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">All Branches / Main Office</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- File Upload Box -->
                    <div class="col-12">
                        <div class="p-4 border-2 border-dashed rounded text-center bg-light position-relative">
                            <i class="bx bx-cloud-upload text-primary display-4 mb-2"></i>
                            <h5 class="fw-bold mb-1">Choose File or Drag & Drop Here</h5>
                            <p class="text-muted small mb-3">Supported: PDF, DOC, DOCX, XLS, XLSX, CSV, ZIP, RAR, JPG, PNG, WEBP, MP4, CAD (Max: 50MB)</p>
                            <input type="file" name="document_file" id="documentFileInput" class="form-control w-50 mx-auto" required onchange="detectFileName(this)">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Document Title / Name</label>
                        <input type="text" name="name" id="docNameInput" class="form-control" placeholder="e.g. Vehicle Insurance Policy 2026-2027" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required fw-semibold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Folder</label>
                        <select name="folder_id" class="form-select">
                            <option value="">Root Directory</option>
                            @foreach($folders as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Issue Date</label>
                        <input type="date" name="issue_date" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Effective Date</label>
                        <input type="date" name="effective_date" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-danger">Expiry Date (For Reminders)</label>
                        <input type="date" name="expiry_date" class="form-control border-danger">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tags (Comma Separated)</label>
                        <input type="text" name="tags" class="form-control" placeholder="e.g. RC, Insurance, Truck, Permit, 2026">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Department</label>
                        <input type="text" name="department" class="form-control" placeholder="e.g. Transport, HR, Accounts">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required fw-semibold">Initial Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide additional details or context about this document..."></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Internal Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Private internal notes..."></textarea>
                    </div>

                    <div class="col-12 text-end pt-3">
                        <a href="{{ route('admin.documents.index') }}" class="btn btn-label-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bx bx-upload me-1"></i> Save & Upload Document</button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function detectFileName(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var nameInput = document.getElementById('docNameInput');
        if(!nameInput.value) {
            var rawName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
            nameInput.value = rawName.replace(/[-_]/g, ' ');
        }
    }
}

$(document).ready(function() {
    function loadBranches(companyId, selectedBranchId) {
        var $branchSelect = $('#branch_id');
        if (!$branchSelect.length) return;

        if (!companyId) {
            $branchSelect.html('<option value="">All Branches / Main Office</option>');
            return;
        }

        $.ajax({
            url: '{{ url("admin/users/get-branches") }}/' + companyId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var options = '<option value="">All Branches / Main Office</option>';
                if (data && data.length > 0) {
                    $.each(data, function(index, branch) {
                        var selected = (selectedBranchId && selectedBranchId == branch.id) ? 'selected' : '';
                        options += '<option value="' + branch.id + '" ' + selected + '>' + branch.name + '</option>';
                    });
                }
                $branchSelect.html(options);
            },
            error: function() {
                $branchSelect.html('<option value="">All Branches / Main Office</option>');
            }
        });
    }

    var initialCompanyId = $('#company_id').val();
    var initialBranchId = "{{ old('branch_id') }}";
    if (initialCompanyId) {
        loadBranches(initialCompanyId, initialBranchId);
    }

    $(document).on('change', '#company_id', function() {
        var companyId = $(this).val();
        loadBranches(companyId, null);
    });
});
</script>
@endpush
@endsection
