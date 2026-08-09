@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Tyre Management</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Maintenance</a></li>
                    <li class="breadcrumb-item active">Tyre Management</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('view tyre management')
            <a href="{{ route('admin.maintenance.tyre-management.layout') }}" class="btn btn-outline-primary"><i class="bx bx-grid-alt me-1"></i> Graphic Layout</a>
            @endcan
            @can('delete tyre management')
            <a href="{{ route('admin.maintenance.tyre-management.trashed') }}" class="btn btn-outline-danger"><i class="bx bx-trash me-1"></i> Recycle Bin</a>
            @endcan
            @can('create tyre management')
            <a href="{{ route('admin.maintenance.tyre-management.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> New Tyre</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Vehicle</label>
                    <select name="vehicle_id" class="form-select">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->vehicle_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="removed" {{ request('status') == 'removed' ? 'selected' : '' }}>Removed</option>
                        <option value="scrap" {{ request('status') == 'scrap' ? 'selected' : '' }}>Scrap</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Brand, serial number, position..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Vehicle</th>
                        <th>Position</th>
                        <th>Brand</th>
                        <th>Size</th>
                        <th>Serial #</th>
                        <th class="text-end">Tread Depth</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tyres as $tyre)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $tyre->vehicle?->vehicle_number ?? 'N/A' }}</strong></td>
                        <td>{{ $tyre->tyre_position }}</td>
                        <td>{{ $tyre->tyre_brand }}</td>
                        <td>{{ $tyre->tyre_size }}</td>
                        <td>{{ $tyre->serial_number ?? '-' }}</td>
                        <td class="text-end">{{ $tyre->tread_depth_current ? $tyre->tread_depth_current . ' mm' : '-' }}</td>
                        <td>
                            @php
                                $badge = ['active' => 'success', 'removed' => 'warning', 'scrap' => 'danger'];
                            @endphp
                            <span class="badge bg-label-{{ $badge[$tyre->status] ?? 'secondary' }}">{{ ucfirst($tyre->status) }}</span>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.maintenance.tyre-management.show', $tyre) }}" class="btn btn-sm btn-icon btn-outline-info" title="View"><i class="bx bx-show"></i></a>
                            @can('edit tyre management')
                            <a href="{{ route('admin.maintenance.tyre-management.edit', $tyre) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                            @endcan
                            @can('delete tyre management')
                            <form method="POST" action="{{ route('admin.maintenance.tyre-management.destroy', $tyre) }}" class="d-inline" onsubmit="return confirm('Delete this tyre record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No tyre records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($tyres, 'links'))
        <div class="card-footer">
            {{ $tyres->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
