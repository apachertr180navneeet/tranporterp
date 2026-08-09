@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Fuel Pumps</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Fuel Pumps</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bx bx-import me-1"></i> Import</button>
            <a href="{{ route('admin.masters.fuel-pumps.download-template') }}" class="btn btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
            <a href="{{ route('admin.masters.fuel-pumps.trashed') }}" class="btn btn-outline-danger"><i class="bx bx-trash me-1"></i> Recycle Bin</a>
            <a href="{{ route('admin.masters.fuel-pumps.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Add Fuel Pump</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.masters.fuel-pumps.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, number, owner..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
                @if(request()->hasAny(['search','status']))
                <div class="col-md-2">
                    <a href="{{ route('admin.masters.fuel-pumps.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fuel Company</th>
                        <th>Fuel Pump Name</th>
                        <th>Status</th>
                        <th>Number</th>
                        <th>Owner Name</th>
                        <th>Owner Mobile</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fuelPumps as $key => $fuelPump)
                    <tr>
                        <td>{{ ($fuelPumps->currentPage() - 1) * $fuelPumps->perPage() + $key + 1 }}</td>
                        <td>{{ $fuelPump->fuelCompany->name ?? '-' }}</td>
                        <td class="fw-semibold">{{ $fuelPump->name }}</td>
                        <td><span class="badge bg-label-{{ $fuelPump->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($fuelPump->status) }}</span></td>
                        <td>{{ $fuelPump->number ?? '-' }}</td>
                        <td>{{ $fuelPump->owner_name ?? '-' }}</td>
                        <td>{{ $fuelPump->owner_mobile ?? '-' }}</td>
                        <td>{{ \Str::limit($fuelPump->address, 40) ?? '-' }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.masters.fuel-pumps.edit', $fuelPump->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                            <form action="{{ route('admin.masters.fuel-pumps.toggle-status', $fuelPump->id) }}" method="POST" class="d-inline">@csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-{{ $fuelPump->status == 'active' ? 'warning' : 'success' }}" title="{{ $fuelPump->status == 'active' ? 'Deactivate' : 'Activate' }}"><i class="bx bx-{{ $fuelPump->status == 'active' ? 'pause' : 'play' }}"></i></button>
                            </form>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="handleDelete({{ $fuelPump->id }}, '{{ $fuelPump->name }}')" title="Delete"><i class="bx bx-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <p class="text-muted mb-0">No fuel pumps found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($fuelPumps->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $fuelPumps->links() }}
        </div>
        @endif
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">@csrf @method('DELETE')</form>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.masters.fuel-pumps.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Import Fuel Pumps</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Download the template first, fill it in, then upload here.</p>
                    <div class="mb-3"><label class="form-label">Fuel Company</label>
                        <select name="fuel_company_id" class="form-select">
                            <option value="">Select Fuel Company</option>
                            @foreach($fuelCompanies as $fuelCompany)
                            <option value="{{ $fuelCompany->id }}">{{ $fuelCompany->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Choose Excel File (xlsx, xls, csv) *</label><input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required></div>
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
        Swal.fire({
            title: 'Delete Fuel Pump?',
            text: "Are you sure you want to delete '" + name + "'?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Delete it',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-form');
                form.action = "{{ url('admin/masters/fuel-pumps') }}/" + id;
                form.submit();
            }
        })
    }
</script>
@endsection
