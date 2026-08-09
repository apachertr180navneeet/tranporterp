@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Document Management /</span> Nested Folders
            </h4>
            <p class="text-muted mb-0">Create unlimited nested folder hierarchies for structured document organization.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(auth()->user()->isSuperAdmin() && $companies->count() > 0)
            <form action="{{ route('admin.documents.folders.index') }}" method="GET" id="companySelectForm" class="d-flex align-items-center me-2">
                <label class="form-label mb-0 me-2 fw-semibold text-nowrap">Company:</label>
                <select name="company_id" class="form-select form-select-sm" onchange="document.getElementById('companySelectForm').submit();">
                    <option value="">All Companies</option>
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" {{ $companyId == $comp->id ? 'selected' : '' }}>
                            {{ $comp->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endif
            <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                <i class="bx bx-folder-plus me-1"></i> Create Folder
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Folder Name & Path</th>
                        <th>Associated Category</th>
                        <th>Parent Folder</th>
                        <th>Branch</th>
                        <th>Total Documents</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($folders as $index => $folder)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong class="text-primary"><i class="bx bx-folder-open me-1"></i> {{ $folder->name }}</strong>
                            <div class="small text-muted">{{ $folder->full_path }}</div>
                        </td>
                        <td>{{ $folder->category ? $folder->category->name : 'General' }}</td>
                        <td>{{ $folder->parent ? $folder->parent->name : 'Root Level' }}</td>
                        <td>{{ $folder->branch ? $folder->branch->name : 'All Branches' }}</td>
                        <td><span class="badge bg-label-info">{{ $folder->documents_count }} Documents</span></td>
                        <td>
                            @if($folder->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-icon btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editFolderModal{{ $folder->id }}">
                                <i class="bx bx-edit-alt"></i>
                            </button>
                            <form action="{{ route('admin.documents.folders.destroy', $folder->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Deleting folder will unassign documents in it. Continue?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-sm btn-outline-danger">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editFolderModal{{ $folder->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('admin.documents.folders.update', $folder->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Folder: {{ $folder->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if(auth()->user()->isSuperAdmin())
                                        <div class="mb-3">
                                            <label class="form-label required">Company</label>
                                            <select name="company_id" class="form-select company-select" data-branch-target="#edit_folder_branch_{{ $folder->id }}" required>
                                                <option value="">Select Company</option>
                                                @foreach($companies as $comp)
                                                <option value="{{ $comp->id }}" {{ $folder->company_id == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @else
                                        <input type="hidden" name="company_id" value="{{ $folder->company_id ?? auth()->user()->company_id }}">
                                        @endif

                                        <div class="mb-3">
                                            <label class="form-label required">Folder Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $folder->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Parent Folder</label>
                                            <select name="parent_id" class="form-select">
                                                <option value="">Root Level</option>
                                                @foreach($allFolders as $f)
                                                    @if($f->id != $folder->id)
                                                    <option value="{{ $f->id }}" {{ $folder->parent_id == $f->id ? 'selected' : '' }}>
                                                        {{ $f->full_path }}
                                                    </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <select name="category_id" class="form-select">
                                                <option value="">Select Category (Optional)</option>
                                                @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ $folder->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Branch</label>
                                            <select name="branch_id" id="edit_folder_branch_{{ $folder->id }}" class="form-select">
                                                <option value="">All Branches</option>
                                                @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ $folder->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="2">{{ $folder->description }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="active" {{ $folder->status === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ $folder->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Update Folder</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No folders created yet. Click "Create Folder" to build custom folder structures.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.documents.folders.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if(auth()->user()->isSuperAdmin())
                    <div class="mb-3">
                        <label class="form-label required">Company</label>
                        <select name="company_id" id="create_folder_company_id" class="form-select company-select" data-branch-target="#create_folder_branch_id" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" {{ $companyId == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="company_id" value="{{ $companyId ?? auth()->user()->company_id }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label required">Folder Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Accounts, HR, Employees, RC, Insurance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Folder (for Nested Folders)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Root Level (Top Level Folder)</option>
                            @foreach($allFolders as $f)
                            <option value="{{ $f->id }}">{{ $f->full_path }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Associated Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category (Optional)</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" id="create_folder_branch_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Folder description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $(document).on('change', '.company-select', function() {
        var companyId = $(this).val();
        var branchTarget = $(this).data('branch-target');
        var $branchSelect = $(branchTarget);

        if (!$branchSelect.length) return;

        $branchSelect.html('<option value="">Loading branches...</option>');

        if (companyId) {
            $.ajax({
                url: '{{ url("admin/users/get-branches") }}/' + companyId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">All Branches</option>';
                    $.each(data, function(index, branch) {
                        options += '<option value="' + branch.id + '">' + branch.name + '</option>';
                    });
                    $branchSelect.html(options).trigger('change');
                },
                error: function() {
                    $branchSelect.html('<option value="">All Branches</option>').trigger('change');
                }
            });
        } else {
            $branchSelect.html('<option value="">All Branches</option>').trigger('change');
        }
    });
});
</script>
@endsection
