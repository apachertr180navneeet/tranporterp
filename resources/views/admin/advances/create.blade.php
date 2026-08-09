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
            <h4 class="fw-bold mb-1">Request Salary Advance</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.advances.index') }}">Advances</a></li>
                    <li class="breadcrumb-item active">Request</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.advances.store') }}">
                        @csrf
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isCompanyAdmin())
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Employee <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select form-control-lg" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control form-control-lg" placeholder="Enter amount" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Deduction Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="deduction_type" id="deduction_full" value="full" checked onchange="toggleMonthlyDeduction()">
                                    <label class="form-check-label" for="deduction_full">Full One-Time</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="deduction_type" id="deduction_monthly" value="monthly" onchange="toggleMonthlyDeduction()">
                                    <label class="form-check-label" for="deduction_monthly">Monthly Installment</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="monthly_deduction_wrapper" style="display: none;">
                            <label class="form-label fw-semibold text-dark">Monthly Installment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="monthly_deduction" id="monthly_deduction" class="form-control form-control-lg" placeholder="Enter monthly deduction" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="4" placeholder="Why do you need an advance?" required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-send me-1"></i> Submit Request</button>
                            <a href="{{ route('admin.advances.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bx bx-info-circle text-primary me-1"></i> Policy</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> Advance will be deducted from next salary.</li>
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> Maximum advance: 50% of monthly base salary.</li>
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> Approval required from supervisor.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    function toggleMonthlyDeduction() {
        const isMonthly = document.getElementById('deduction_monthly').checked;
        const wrapper = document.getElementById('monthly_deduction_wrapper');
        const input = document.getElementById('monthly_deduction');
        
        if (isMonthly) {
            wrapper.style.display = 'block';
            input.required = true;
        } else {
            wrapper.style.display = 'none';
            input.required = false;
        }
    }
    
    // Run on load in case of old input
    document.addEventListener('DOMContentLoaded', toggleMonthlyDeduction);
</script>
@endsection
