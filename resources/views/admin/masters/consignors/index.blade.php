@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Consignors</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Masters</li>
                    <li class="breadcrumb-item active">Consignors</li>
                </ol>
            </nav>
        </div>
        <div>
            @canany(['create consignors', 'import consignors'])
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bx bx-import me-1"></i> Import</button>
            <a href="{{ route('admin.masters.consignors.download-template') }}" class="btn btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
            @endcanany

            @can('delete consignors')
            <a href="{{ route('admin.masters.consignors.trashed') }}" class="btn btn-outline-danger"><i class="bx bx-trash me-1"></i> Recycle Bin </a>
            @endcan

            @can('create consignors')
            <a href="{{ route('admin.masters.consignors.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Add Consignor</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom py-3">
            <form method="GET" class="row g-2">
                <div class="col-12 col-md"><input type="text" name="search" class="form-control" placeholder="Search by name, phone, GSTIN..." value="{{ request('search') }}"></div>
                <div class="col-12 col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto"><button type="submit" class="btn btn-outline-secondary w-100"><i class="bx bx-search me-1"></i> Search</button></div>
                @if(request()->hasAny(['search','status']))
                <div class="col-12 col-md-auto"><a href="{{ route('admin.masters.consignors.index') }}" class="btn btn-outline-danger w-100"><i class="bx bx-x me-1"></i> Clear</a></div>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>#</th><th>Vendor Code</th><th>Name</th><th>Phone</th><th>Email</th><th>GSTIN</th><th>City</th>@if(auth()->user()->isSuperAdmin())<th>Company</th><th>Branch</th>@endif<th class="text-nowrap">Status</th><th class="text-nowrap">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($consignors as $index => $consignor)
                    <tr>
                        <td class="text-nowrap">{{ $consignors->firstItem() + $index }}</td>
                        <td class="text-nowrap">{{ $consignor->vendor_code ?? '-' }}</td>
                        <td class="text-nowrap"><strong>{{ $consignor->name }}</strong></td>
                        <td class="text-nowrap">{{ $consignor->phone }}</td>
                        <td style="max-width: 200px">{{ $consignor->email ?? '-' }}</td>
                        <td>{{ $consignor->gstin ?? '-' }}</td>
                        <td>{{ $consignor->city ?? '-' }}</td>
                        @if(auth()->user()->isSuperAdmin())
                        <td>{{ $consignor->company->name ?? '-' }}</td>
                        <td>{{ $consignor->branch->name ?? '-' }}</td>
                        @endif
                        <td class="text-nowrap"><span class="badge bg-label-{{ $consignor->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($consignor->status) }}</span></td>
                        <td class="text-center text-nowrap">
                            @can('edit consignors')
                            <a href="{{ route('admin.masters.consignors.edit', $consignor->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                            <a href="{{ route('admin.masters.consignors.transfer', $consignor->id) }}" class="btn btn-sm btn-icon btn-outline-info" title="Transfer"><i class="bx bx-transfer-alt"></i></a>
                            <form action="{{ route('admin.masters.consignors.toggle-status', $consignor->id) }}" method="POST" class="d-inline">@csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-{{ $consignor->status == 'active' ? 'warning' : 'success' }}" title="{{ $consignor->status == 'active' ? 'Deactivate' : 'Activate' }}"><i class="bx bx-{{ $consignor->status == 'active' ? 'pause' : 'play' }}"></i></button>
                            </form>
                            @endcan
                            @can('delete consignors')
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="handleDelete({{ $consignor->id }}, '{{ $consignor->name }}')" title="Delete"><i class="bx bx-trash"></i></button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 11 : 9 }}" class="text-center py-4 text-muted">No consignors found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $consignors->withQueryString()->links() }}</div>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">@csrf @method('DELETE')</form>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.masters.consignors.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Import Consignors</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Download the template first, fill it in, then upload here.</p>
                    <div class="mb-3"><label class="form-label">Choose Excel File (xlsx, xls, csv) *</label><input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required></div>
                    @if(auth()->user()->isSuperAdmin())
                    <div class="mb-3"><label class="form-label">Company *</label><select name="company_id" id="import_company_id" class="form-select" required><option value="">Select Company</option>@foreach(\App\Models\Company::where('status','active')->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label">Branch</label><select name="branch_id" id="import_branch_id" class="form-select"><option value="">Select Branch</option></select></div>
                    @endif
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="bx bx-upload me-1"></i> Import</button> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function handleDelete(id, name) {
        Swal.fire({ title: 'Delete Consignor?', text: "This will delete '" + name + "'!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!' }).then((result) => {
            if (result.isConfirmed) { const form = document.getElementById('delete-form'); form.action = "{{ url('admin/masters/consignors') }}/" + id; form.submit(); }
        })
    }

    @if(auth()->user()->isSuperAdmin())
    $(document).ready(function() {
        function loadImportBranches(companyId, selectedBranchId) {
            if (companyId) {
                $.ajax({
                    url: '{{ url("admin/users/get-branches") }}/' + companyId,
                    type: 'GET',
                    success: function(data) {
                        $('#import_branch_id').empty().append('<option value="">Select Branch</option>');
                        $.each(data, function(key, branch) {
                            var selected = branch.id == selectedBranchId ? 'selected' : '';
                            $('#import_branch_id').append('<option value="' + branch.id + '" ' + selected + '>' + branch.name + '</option>');
                        });
                        $('#import_branch_id').trigger('change');
                    }
                });
            } else {
                $('#import_branch_id').empty().append('<option value="">Select Branch</option>').trigger('change');
            }
        }
        $('#import_company_id').change(function() { loadImportBranches($(this).val(), null); });
    });
    @endif
</script>
@endsection
