@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Tyre Models</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Tyre Models</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.masters.tyre-models.trashed') }}" class="btn btn-outline-danger me-2"><i class="bx bx-trash me-1"></i> Recycle Bin</a>
            <a href="{{ route('admin.masters.tyre-models.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Add Tyre Model</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.masters.tyre-models.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by model name or code..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="tyre_brand_id" class="form-select">
                        <option value="">All Brands</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ request('tyre_brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bx bx-search me-1"></i> Filter</button>
                    @if(request()->hasAny(['search','tyre_brand_id','status']))
                        <a href="{{ route('admin.masters.tyre-models.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Model Name</th>
                        <th>Tyre Brand</th>
                        <th>Model Code</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($models as $key => $model)
                    <tr>
                        <td>{{ ($models->currentPage() - 1) * $models->perPage() + $key + 1 }}</td>
                        <td class="fw-semibold">
                            <i class="bx bx-layer text-primary me-1"></i> {{ $model->name }}
                        </td>
                        <td>
                            <span class="badge bg-label-primary"><i class="bx bx-disc me-1"></i> {{ $model->brand->name ?? '-' }}</span>
                        </td>
                        <td>{{ $model->code ?? '-' }}</td>
                        <td>{{ Str::limit($model->description ?? '-', 40) }}</td>
                        <td>
                            <span class="badge bg-label-{{ $model->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($model->status) }}
                            </span>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.masters.tyre-models.edit', $model->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                            <form action="{{ route('admin.masters.tyre-models.toggle-status', $model->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-{{ $model->status == 'active' ? 'warning' : 'success' }}" title="{{ $model->status == 'active' ? 'Deactivate' : 'Activate' }}">
                                    <i class="bx bx-{{ $model->status == 'active' ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="handleDelete({{ $model->id }}, '{{ addslashes($model->name) }}')" title="Delete"><i class="bx bx-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <p class="text-muted mb-0">No tyre models found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($models->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $models->links() }}
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
            title: 'Delete Tyre Model?',
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
                form.action = "{{ url('admin/masters/tyre-models') }}/" + id;
                form.submit();
            }
        });
    }
</script>
@endsection
