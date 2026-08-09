@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Add Company Loan</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.loan.company-loan.index') }}">Company Loans</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.loan.company-loan.store') }}">
                @csrf
                <div class="row g-3">
                    @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-6">
                        <label class="form-label">Company <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @else
                    <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                    @endif
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bank <span class="text-danger">*</span></label>
                        <select name="bank_id" id="bank_id" class="form-select @error('bank_id') is-invalid @enderror" required>
                            <option value="">Select Bank</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        @error('bank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select Bank First</option>
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Loan ID <span class="text-danger">*</span></label>
                        <input type="text" name="loan_id" class="form-control @error('loan_id') is-invalid @enderror" value="{{ old('loan_id') }}" placeholder="Enter loan ID" required>
                        @error('loan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Loan Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="loan_amount" class="form-control @error('loan_amount') is-invalid @enderror" value="{{ old('loan_amount') }}" placeholder="Enter loan amount" required>
                        @error('loan_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Given Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="given_amount" class="form-control @error('given_amount') is-invalid @enderror" value="{{ old('given_amount') }}" placeholder="Enter given amount" required>
                        @error('given_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interest Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="interest_rate" class="form-control @error('interest_rate') is-invalid @enderror" value="{{ old('interest_rate') }}" placeholder="Enter interest rate" required>
                        @error('interest_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tenure (Months) <span class="text-danger">*</span></label>
                        <input type="number" name="tenure_months" class="form-control @error('tenure_months') is-invalid @enderror" value="{{ old('tenure_months') }}" placeholder="Enter tenure in months" required>
                        @error('tenure_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">EMI Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="emi_amount" id="emi_amount" class="form-control @error('emi_amount') is-invalid @enderror" value="{{ old('emi_amount') }}" placeholder="Auto-calculated" required>
                        @error('emi_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Interest</label>
                        <input type="text" id="total_interest_display" class="form-control" placeholder="Auto-calculated" readonly>
                        <input type="hidden" name="total_interest" id="total_interest" value="{{ old('total_interest', 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Remaining Amount</label>
                        <input type="text" id="remaining_amount_display" class="form-control" placeholder="Auto-calculated" readonly>
                        <input type="hidden" name="remaining_amount" id="remaining_amount" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Given EMI Count <span class="text-danger">*</span></label>
                        <input type="number" name="given_emi_count" class="form-control @error('given_emi_count') is-invalid @enderror" value="{{ old('given_emi_count', 0) }}" placeholder="Enter EMIs given" required>
                        @error('given_emi_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pending EMI Date</label>
                        <input type="date" max="9999-12-31" name="pending_emi_date" class="form-control @error('pending_emi_date') is-invalid @enderror" value="{{ old('pending_emi_date') }}">
                        @error('pending_emi_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Tenure Calculation / Notes</label>
                        <textarea name="tenure_calculation" class="form-control @error('tenure_calculation') is-invalid @enderror" rows="3" placeholder="Enter tenure calculation details or notes">{{ old('tenure_calculation') }}</textarea>
                        @error('tenure_calculation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('admin.loan.company-loan.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.getElementById('bank_id').addEventListener('change', function() {
        const bankId = this.value;
        const branchSelect = document.getElementById('branch_id');
        branchSelect.innerHTML = '<option value="">Loading...</option>';

        if (bankId) {
            fetch('{{ url("admin/loan/company-loan/get-branches") }}/' + bankId)
                .then(res => res.json())
                .then(data => {
                    branchSelect.innerHTML = '<option value="">Select Branch</option>';
                    data.forEach(branch => {
                        const selected = '{{ old("branch_id") }}' == branch.id ? 'selected' : '';
                        branchSelect.innerHTML += `<option value="${branch.id}" ${selected}>${branch.branch_name} (${branch.ifsc})</option>`;
                    });
                });
        } else {
            branchSelect.innerHTML = '<option value="">Select Bank First</option>';
        }
    });

    @if(old('bank_id'))
    document.getElementById('bank_id').dispatchEvent(new Event('change'));
    @endif

    function calculateEMI() {
        const P = parseFloat(document.querySelector('[name="loan_amount"]').value) || 0;
        const annualRate = parseFloat(document.querySelector('[name="interest_rate"]').value) || 0;
        const n = parseFloat(document.querySelector('[name="tenure_months"]').value) || 0;

        if (P > 0 && annualRate > 0 && n > 0) {
            const r = annualRate / 12 / 100;
            const emi = P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
            document.querySelector('[name="emi_amount"]').value = emi.toFixed(2);
        }
        calculateTotalInterest();
    }

    function calculateTotalInterest() {
        const emi = parseFloat(document.querySelector('[name="emi_amount"]').value) || 0;
        const n = parseFloat(document.querySelector('[name="tenure_months"]').value) || 0;
        const P = parseFloat(document.querySelector('[name="loan_amount"]').value) || 0;

        if (emi > 0 && n > 0 && P > 0) {
            const totalInterest = (emi * n) - P;
            document.getElementById('total_interest_display').value = totalInterest.toFixed(2);
            document.getElementById('total_interest').value = totalInterest.toFixed(2);
        } else {
            document.getElementById('total_interest_display').value = '';
            document.getElementById('total_interest').value = '0';
        }
        calculateRemaining();
    }

    function calculateRemaining() {
        const P = parseFloat(document.querySelector('[name="loan_amount"]').value) || 0;
        const totalInterest = parseFloat(document.getElementById('total_interest').value) || 0;
        const givenEmi = parseFloat(document.querySelector('[name="given_emi_count"]').value) || 0;
        const emi = parseFloat(document.querySelector('[name="emi_amount"]').value) || 0;
        const totalPayable = P + totalInterest;
        const paid = givenEmi * emi;
        const remaining = Math.max(0, totalPayable - paid);
        document.getElementById('remaining_amount_display').value = remaining.toFixed(2);
        document.getElementById('remaining_amount').value = remaining.toFixed(2);
    }

    function calculateTenure() {
        const P = parseFloat(document.querySelector('[name="loan_amount"]').value) || 0;
        const annualRate = parseFloat(document.querySelector('[name="interest_rate"]').value) || 0;
        const emi = parseFloat(document.querySelector('[name="emi_amount"]').value) || 0;

        if (P > 0 && annualRate > 0 && emi > 0 && emi > P * annualRate / 12 / 100) {
            const r = annualRate / 12 / 100;
            const n = Math.log(emi / (emi - P * r)) / Math.log(1 + r);
            document.querySelector('[name="tenure_months"]').value = Math.round(n);
        }
        calculateTotalInterest();
    }

    document.querySelector('[name="loan_amount"]').addEventListener('input', calculateEMI);
    document.querySelector('[name="interest_rate"]').addEventListener('input', calculateEMI);
    document.querySelector('[name="tenure_months"]').addEventListener('input', calculateEMI);
    document.querySelector('[name="emi_amount"]').addEventListener('input', function() {
        const P = parseFloat(document.querySelector('[name="loan_amount"]').value) || 0;
        const annualRate = parseFloat(document.querySelector('[name="interest_rate"]').value) || 0;
        if (P > 0 && annualRate > 0) {
            calculateTenure();
        } else {
            calculateTotalInterest();
        }
    });
    document.querySelector('[name="given_emi_count"]').addEventListener('input', calculateRemaining);
</script>
@endsection
