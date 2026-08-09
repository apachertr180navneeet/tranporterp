@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Bilty Advance Details</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Bilty Advance Details</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.bilty-advance-details.export', request()->except('page')) }}" class="btn btn-success btn-sm">
                <i class="bx bx-spreadsheet me-1"></i> Excel Export
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Record Entry / Edit Form Card -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ isset($editRecord) ? 'Edit Bilty Advance' : 'Add Bilty Advance Record' }}</h5>
            @if(isset($editRecord))
                <a href="{{ route('admin.reports.bilty-advance-details.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-x me-1"></i> Cancel Edit
                </a>
            @endif
        </div>
        <div class="card-body">
            <form method="POST" action="{{ isset($editRecord) ? route('admin.reports.bilty-advance-details.update', $editRecord->id) : route('admin.reports.bilty-advance-details.store') }}">
                @csrf
                @if(isset($editRecord))
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <!-- LR Selection -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="bulty_id">Select LR (Lorry Receipt) <span class="text-danger">*</span></label>
                        <select name="bulty_id" id="bulty_id" class="form-select @error('bulty_id') is-invalid @enderror" required>
                            <option value="">-- Select LR Number --</option>
                            @foreach($bulties as $bulty)
                                <option value="{{ $bulty->id }}"
                                    data-company="{{ $bulty->company?->name ?? 'N/A' }}"
                                    data-branch="{{ $bulty->branch?->name ?? 'N/A' }}"
                                    {{ (old('bulty_id', $editRecord->bulty_id ?? '') == $bulty->id) ? 'selected' : '' }}>
                                    {{ $bulty->lr_no }} ({{ $bulty->company?->name ?? 'No Co.' }} - {{ $bulty->branch?->name ?? 'No Br.' }})
                                </option>
                            @endforeach
                        </select>
                        @error('bulty_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Date -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" max="9999-12-31" name="date" id="date" class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', isset($editRecord->date) ? $editRecord->date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Auto-filled Company -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Company (Auto-fetched)</label>
                        <input type="text" id="company_display" class="form-control bg-light" readonly
                            placeholder="Select LR to fetch Company"
                            value="{{ isset($editRecord) ? ($editRecord->company?->name ?? '-') : '' }}">
                    </div>

                    <!-- Auto-filled Branch -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Branch (Auto-fetched)</label>
                        <input type="text" id="branch_display" class="form-control bg-light" readonly
                            placeholder="Select LR to fetch Branch"
                            value="{{ isset($editRecord) ? ($editRecord->branch?->name ?? '-') : '' }}">
                    </div>

                    <!-- Advance Amount (Manually Entered) -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="advance_amount">Advance Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="advance_amount" id="advance_amount"
                            class="form-control @error('advance_amount') is-invalid @enderror"
                            placeholder="Enter advance amount"
                            value="{{ old('advance_amount', isset($editRecord) ? $editRecord->advance_amount : '') }}" required>
                        @error('advance_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remarks Field -->
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" for="remarks">Remarks / Notes</label>
                        <input type="text" name="remarks" id="remarks"
                            class="form-control @error('remarks') is-invalid @enderror"
                            placeholder="Enter any free text comments or notes..."
                            value="{{ old('remarks', $editRecord->remarks ?? '') }}">
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx {{ isset($editRecord) ? 'bx-check-circle' : 'bx-save' }} me-1"></i>
                            {{ isset($editRecord) ? 'Update Record' : 'Save Bilty Advance' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Card & Data Table -->
    <div class="card">
        <div class="card-header border-bottom py-3">
            <h5 class="mb-0">Bilty Advance Records</h5>
        </div>
        <div class="card-body pt-3">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.reports.bilty-advance-details.index') }}" class="mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Date From</label>
                        <input type="date" max="9999-12-31" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date To</label>
                        <input type="date" max="9999-12-31" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">LR Number</label>
                        <select name="bulty_id" class="form-select form-select-sm">
                            <option value="">All LRs</option>
                            @foreach($bulties as $b)
                                <option value="{{ $b->id }}" {{ request('bulty_id') == $b->id ? 'selected' : '' }}>{{ $b->lr_no }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Company</label>
                        <select name="company_id" class="form-select form-select-sm">
                            <option value="">All Companies</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Branch</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $br)
                                <option value="{{ $br->id }}" {{ request('branch_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bx bx-filter me-1"></i> Filter</button>
                        <a href="{{ route('admin.reports.bilty-advance-details.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>LR Number</th>
                            <th>Date</th>
                            <th>Company</th>
                            <th>Branch</th>
                            <th class="text-end">Advance Amount</th>
                            <th>Remarks</th>
                            <th class="text-center" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $key => $row)
                            <tr>
                                <td>{{ $records->firstItem() + $key }}</td>
                                <td><span class="badge bg-label-primary fs-6">{{ $row->builty?->lr_no ?? '-' }}</span></td>
                                <td>{{ $row->date?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $row->company?->name ?? '-' }}</td>
                                <td>{{ $row->branch?->name ?? '-' }}</td>
                                <td class="text-end fw-bold text-success">₹ {{ number_format($row->advance_amount, 2) }}</td>
                                <td>
                                    @if($row->remarks)
                                        <span class="text-wrap" style="max-width: 250px; display: inline-block;">{{ $row->remarks }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.reports.bilty-advance-details.index', array_merge(request()->query(), ['edit' => $row->id])) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.reports.bilty-advance-details.destroy', $row->id) }}" onsubmit="return confirm('Are you sure you want to delete this bilty advance record?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 d-block mb-1"></i>
                                    No Bilty Advance records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end font-weight-bold">Total Filtered Advance Amount:</th>
                            <th class="text-end font-weight-bold text-success fs-6">₹ {{ number_format($totalAdvance, 2) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $records->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {

    function updateCompanyAndBranch() {
        var selectedOption = $('#bulty_id').find(':selected');
        var companyName = selectedOption.data('company');
        var branchName = selectedOption.data('branch');

        if (companyName) {
            $('#company_display').val(companyName);
        } else {
            $('#company_display').val('');
        }

        if (branchName) {
            $('#branch_display').val(branchName);
        } else {
            $('#branch_display').val('');
        }
    }

    // Trigger update on change
    $('#bulty_id').on('change', function() {
        updateCompanyAndBranch();
    });

    // Run on initial load if an option is pre-selected
    if ($('#bulty_id').val()) {
        updateCompanyAndBranch();
    }
});
</script>
@endsection
