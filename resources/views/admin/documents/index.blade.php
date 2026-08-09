@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Document Management /</span> Document Explorer
            </h4>
            <p class="text-muted mb-0">Search, filter, view, download, and manage central company documents.</p>
        </div>
        <div class="d-flex gap-2">
            @can('create documents')
            <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">
                <i class="bx bx-cloud-upload me-1"></i> Upload Document
            </a>
            @endcan
            @can('delete documents')
            <a href="{{ route('admin.documents.trash') }}" class="btn btn-outline-danger">
                <i class="bx bx-trash me-1"></i> Trash
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Search & Filters Toolbar -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" id="searchTerm" class="form-control" placeholder="Search document...">
                </div>

                @if(auth()->user()->isSuperAdmin())
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Company</label>
                    <select id="filterCompany" class="form-select">
                        <option value="">All Companies</option>
                        @foreach($companies as $comp)
                        <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Branch</label>
                    <select id="filterBranch" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Category</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Folder</label>
                    <select id="filterFolder" class="form-select">
                        <option value="">All Folders</option>
                        @foreach($folders as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Expiry Filter</label>
                    <select id="filterExpiry" class="form-select">
                        <option value="">Any Expiry Date</option>
                        <option value="today">Expiring Today</option>
                        <option value="7_days">Expiring in 7 Days</option>
                        <option value="15_days">Expiring in 15 Days</option>
                        <option value="30_days">Expiring in 30 Days</option>
                        <option value="expired">Already Expired</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">File Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                        <option value="expired">Expired</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>

                <div class="col-md-1 text-end">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100" data-bs-toggle="tooltip" title="Reset Filters">
                        <i class="bx bx-refresh"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Toolbar & DataTable Card -->
    <form id="bulkForm" action="{{ route('admin.documents.bulk-action') }}" method="POST">
        @csrf
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <select name="action" id="bulkActionSelect" class="form-select form-select-sm" style="width: auto;">
                        <option value="">-- Bulk Actions --</option>
                        <option value="download_zip">Download Selected (ZIP)</option>
                        <option value="delete">Move Selected to Trash</option>
                        <option value="change_status">Change Status</option>
                        <option value="change_category">Change Category</option>
                        <option value="change_folder">Change Folder</option>
                    </select>

                    <div id="targetStatusDiv" class="d-none">
                        <select name="target_status" class="form-select form-select-sm">
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                            <option value="expired">Expired</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <div id="targetCategoryDiv" class="d-none">
                        <select name="target_category_id" class="form-select form-select-sm">
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="targetFolderDiv" class="d-none">
                        <select name="target_folder_id" class="form-select form-select-sm">
                            @foreach($folders as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-sm btn-dark" onclick="return confirm('Apply selected bulk action to checked items?');">Apply</button>
                </div>

                <div class="text-muted small">
                    <span id="selectedCount">0</span> item(s) selected
                </div>
            </div>

            <div class="table-responsive text-nowrap p-3">
                <table id="documentsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>Doc Number</th>
                            <th>Document Name</th>
                            <th>Category</th>
                            <th>Folder</th>
                            <th>Ver</th>
                            <th>Size</th>
                            <th>Uploaded By</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </form>

</div>
@endsection
@section('script')
<script>
$(document).ready(function() {
    var table = $('#documentsTable').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.documents.index') }}",
            type: "GET",
            data: function(d) {
                d.search_term = $('#searchTerm').val() || '';
                if ($('#filterCompany').length) {
                    d.company_id = $('#filterCompany').val() || '';
                }
                d.branch_id = $('#filterBranch').val() || '';
                d.category_id = $('#filterCategory').val() || '';
                d.folder_id = $('#filterFolder').val() || '';
                d.expiry_filter = $('#filterExpiry').val() || '';
                d.status = $('#filterStatus').val() || '';
            }
        },
        columns: [
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return '<input type="checkbox" name="document_ids[]" value="' + data + '" class="form-check-input doc-checkbox">';
                }
            },
            { data: 'document_number', name: 'document_number' },
            { 
                data: 'name', 
                name: 'name',
                render: function(data, type, row) {
                    var ext = row.file_extension ? row.file_extension : '';
                    return '<div><strong class="text-dark">' + (data || '') + '</strong><br><small class="text-muted">' + ext + '</small></div>';
                }
            },
            { data: 'category', name: 'category' },
            { data: 'folder', name: 'folder' },
            { data: 'version', name: 'version' },
            { data: 'file_size', name: 'file_size' },
            { data: 'uploader', name: 'uploader' },
            { 
                data: 'expiry_date', 
                name: 'expiry_date',
                render: function(data, type, row) {
                    if(!data || data === '-') return '-';
                    if(row.is_expired) {
                        return '<span class="badge bg-danger">' + data + ' (Expired)</span>';
                    } else if(row.is_expiring_soon) {
                        return '<span class="badge bg-warning text-dark">' + data + '</span>';
                    }
                    return data;
                }
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    if(!data) return '<span class="badge bg-secondary">-</span>';
                    var badge = 'bg-secondary';
                    var statusStr = String(data).toLowerCase();
                    if(statusStr === 'active') badge = 'bg-success';
                    if(statusStr === 'archived') badge = 'bg-info';
                    if(statusStr === 'expired') badge = 'bg-danger';
                    if(statusStr === 'draft') badge = 'bg-warning';
                    return '<span class="badge ' + badge + '">' + statusStr.toUpperCase() + '</span>';
                }
            },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25
    });

    // Dynamic branch loading when company filter changes
    $(document).on('change', '#filterCompany', function() {
        var companyId = $(this).val();
        var $branchSelect = $('#filterBranch');

        if (companyId) {
            $.ajax({
                url: '/admin/users/get-branches/' + companyId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">All Branches</option>';
                    if (data && data.length > 0) {
                        $.each(data, function(i, b) {
                            options += '<option value="' + b.id + '">' + b.name + '</option>';
                        });
                    }
                    $branchSelect.html(options);
                    table.draw();
                },
                error: function() {
                    $branchSelect.html('<option value="">All Branches</option>');
                    table.draw();
                }
            });
        } else {
            $branchSelect.html('<option value="">All Branches</option>');
            table.draw();
        }
    });

    // Real-time filter triggers for input and select
    $(document).on('change keyup input', '#searchTerm, #filterCompany, #filterCategory, #filterFolder, #filterBranch, #filterExpiry, #filterStatus', function() {
        table.draw();
    });

    $('#btnResetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        $('#filterForm select').val('');
        table.draw();
    });

    // Bulk checkboxes selector
    $('#selectAll').on('click', function() {
        $('.doc-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    $(document).on('change', '.doc-checkbox', function() {
        updateSelectedCount();
    });

    function updateSelectedCount() {
        var count = $('.doc-checkbox:checked').length;
        $('#selectedCount').text(count);
    }

    // Toggle bulk parameter inputs
    $('#bulkActionSelect').on('change', function() {
        var val = $(this).val();
        $('#targetStatusDiv, #targetCategoryDiv, #targetFolderDiv').addClass('d-none');
        if(val === 'change_status') $('#targetStatusDiv').removeClass('d-none');
        if(val === 'change_category') $('#targetCategoryDiv').removeClass('d-none');
        if(val === 'change_folder') $('#targetFolderDiv').removeClass('d-none');
    });
});
</script>
@endsection
