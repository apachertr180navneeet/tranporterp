@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Apply for Leave</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.leaves.index') }}">Leaves</a></li>
                    <li class="breadcrumb-item active">Apply</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.leaves.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type" class="form-select" required>
                                <option value="">Select type</option>
                                <option value="sick">Sick Leave</option>
                                <option value="casual">Casual Leave</option>
                                <option value="annual">Annual Leave</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                                <input type="date" max="9999-12-31" name="start_date" class="form-control" min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">End Date <span class="text-danger">*</span></label>
                                <input type="date" max="9999-12-31" name="end_date" class="form-control" min="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="4" placeholder="Describe the reason for your leave..." required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-send me-1"></i> Submit Application</button>
                            <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bx bx-info-circle text-primary me-1"></i> Leave Policy</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> Submit at least 1 day in advance for planned leaves.</li>
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> Sick leave can be applied on the same day.</li>
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> Your supervisor will review and approve/reject.</li>
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> You'll be notified when the status changes.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
