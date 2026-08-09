@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Users</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Admin</li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Add User</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2">
                <div class="col-12 col-md-3">
                    <select name="company_id" class="form-select">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($allRoles as $role)
                            <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email..." value="{{ request('search') }}">
                </div>
                <div class="col-12 col-md-auto"><button type="submit" class="btn btn-outline-secondary w-100"><i class="bx bx-search me-1"></i> Filter</button></div>
                @if(request()->hasAny(['search','status','company_id','role']))
                <div class="col-12 col-md-auto"><a href="{{ route('admin.users.index') }}" class="btn btn-outline-danger w-100"><i class="bx bx-x me-1"></i> Reset</a></div>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Branch</th><th>Role</th><th class="text-nowrap">Status</th><th class="text-nowrap">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $users->firstItem() + $index }}</td>
                            <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ $user->company->name ?? 'N/A' }}</td>
                            <td>{{ $user->branch->name ?? 'N/A' }}</td>
                            <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                            <td><span class="badge bg-label-{{ $user->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($user->status) }}</span></td>
                            <td>
                                @canany(['view users', 'edit users', 'delete users'])
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                    <div class="dropdown-menu">
                                        @can('view users')
                                        <a class="dropdown-item" href="{{ route('admin.users.show', $user->id) }}"><i class="bx bx-show me-1"></i> View</a>
                                        @endcan
                                        @can('edit users')
                                        <a class="dropdown-item" href="{{ route('admin.users.edit', $user->id) }}"><i class="bx bx-edit-alt me-1"></i> Edit</a>
                                        <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="d-inline">@csrf
                                            <button type="submit" class="dropdown-item"><i class="bx bx-{{ $user->status == 'active' ? 'pause' : 'play' }} me-1"></i> {{ $user->status == 'active' ? 'Deactivate' : 'Activate' }}</button>
                                        </form>
                                        @endcan
                                        @can('delete users')
                                        <button type="button" class="dropdown-item text-danger" onclick="handleDelete({{ $user->id }}, '{{ $user->first_name }} {{ $user->last_name }}')"><i class="bx bx-trash me-1"></i> Delete</button>
                                        @endcan
                                    </div>
                                </div>
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4 text-muted">No users found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $users->withQueryString()->links() }}</div>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">@csrf @method('DELETE')</form>
@endsection

@section('script')
<script>
    function handleDelete(id, name) {
        Swal.fire({ title: 'Delete User?', text: "This will delete user '" + name + "'!", icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!' }).then((result) => {
            if (result.isConfirmed) { const form = document.getElementById('delete-form'); form.action = "{{ url('admin/users') }}/" + id; form.submit(); }
        })
    }
</script>
@endsection
