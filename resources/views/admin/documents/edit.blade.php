@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Document Management /</span> Edit Document
            </h4>
            <p class="text-muted mb-0">Update document title, category, folder, expiry date, tags, and status.</p>
        </div>
        <a href="{{ route('admin.documents.show', $document->id) }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to View
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
            <form action="{{ route('admin.documents.update', $document->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">

                    <!-- Company & Branch Selection -->
                    @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Company</label>
                        <select name="company_id" id="company_id" class="form-select" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" {{ old('company_id', $document->company_id) == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="company_id" id="company_id" value="{{ $document->company_id }}">
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">All Branches / Main Office</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $document->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required fw-semibold">Document Title / Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $document->name }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required fw-semibold">Category</label>
                        <select name="category_id" class="form-select" required>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $document->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Folder</label>
                        <select name="folder_id" class="form-select">
                            <option value="">Root Directory</option>
                            @foreach($folders as $f)
                            <option value="{{ $f->id }}" {{ $document->folder_id == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Issue Date</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ $document->issue_date ? $document->issue_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Effective Date</label>
                        <input type="date" name="effective_date" class="form-control" value="{{ $document->effective_date ? $document->effective_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-danger">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control border-danger" value="{{ $document->expiry_date ? $document->expiry_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tags (Comma Separated)</label>
                        <input type="text" name="tags" class="form-control" value="{{ is_array($document->tags) ? implode(', ', $document->tags) : '' }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Department</label>
                        <input type="text" name="department" class="form-control" value="{{ $document->department }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required fw-semibold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $document->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="draft" {{ $document->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="archived" {{ $document->status === 'archived' ? 'selected' : '' }}>Archived</option>
                            <option value="expired" {{ $document->status === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $document->description }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Internal Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ $document->remarks }}</textarea>
                    </div>

                    <div class="col-12 text-end pt-3">
                        <a href="{{ route('admin.documents.show', $document->id) }}" class="btn btn-label-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update Document</button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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
    var initialBranchId = "{{ old('branch_id', $document->branch_id) }}";
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
