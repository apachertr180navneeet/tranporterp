@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Tyre Brands</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Tyre Brands</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.masters.tyre-brands.trashed') }}" class="btn btn-outline-danger me-2"><i class="bx bx-trash me-1"></i> Recycle Bin</a>
            <a href="{{ route('admin.masters.tyre-brands.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Add Tyre Brand</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.masters.tyre-brands.index') }}" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by brand name, code, or description..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bx bx-search me-1"></i> Filter</button>
                </div>
                @if(request()->hasAny(['search','status']))
                <div class="col-md-2">
                    <a href="{{ route('admin.masters.tyre-brands.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
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
                        <th>Brand Name</th>
                        <th>Brand Code</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $key => $brand)
                    <tr>
                        <td>{{ ($brands->currentPage() - 1) * $brands->perPage() + $key + 1 }}</td>
                        <td class="fw-semibold">
                            <i class="bx bx-disc text-primary me-1"></i> {{ $brand->name }}
                        </td>
                        <td>
                            @if($brand->code)
                                <span class="badge bg-label-dark">{{ $brand->code }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($brand->description ?? '-', 50) }}</td>
                        <td>
                            <span class="badge bg-label-{{ $brand->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($brand->status) }}
                            </span>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.masters.tyre-brands.edit', $brand->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                            <form action="{{ route('admin.masters.tyre-brands.toggle-status', $brand->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-{{ $brand->status == 'active' ? 'warning' : 'success' }}" title="{{ $brand->status == 'active' ? 'Deactivate' : 'Activate' }}">
                                    <i class="bx bx-{{ $brand->status == 'active' ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="handleDelete({{ $brand->id }}, '{{ addslashes($brand->name) }}')" title="Delete"><i class="bx bx-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <p class="text-muted mb-0">No tyre brands found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($brands->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $brands->links() }}
        </div>
        @endif
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">@csrf @method('DELETE')</form>
@endsection

@section('script')
<script>
    function handleDelete(id, name) {
        Swal.fire({
            title: 'Delete Tyre Brand?',
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
                form.action = "{{ url('admin/masters/tyre-brands') }}/" + id;
                form.submit();
            }
        });
    }
</script>
@endsection
