@extends('admin.layouts.app')

@section('style')
<style>
    /* Premium visual styles for the Employee Salary Dashboard */
    .metric-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        position: relative;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
    }
    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }
    .card-primary::before { background: linear-gradient(90deg, #696cff, #8592ff); }
    .card-success::before { background: linear-gradient(90deg, #71dd37, #93ec5b); }
    .card-warning::before { background: linear-gradient(90deg, #ffab00, #ffc44d); }
    .card-danger::before  { background: linear-gradient(90deg, #ff3e1d, #ff6b52); }

    .icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 12px;
    }
    .bg-light-primary { background-color: rgba(105, 108, 255, 0.08); color: #696cff; }
    .bg-light-success { background-color: rgba(113, 221, 55, 0.08); color: #71dd37; }
    .bg-light-warning { background-color: rgba(255, 171, 0, 0.08); color: #ffab00; }
    .bg-light-danger  { background-color: rgba(255, 62, 29, 0.08); color: #ff3e1d; }

    /* Animated and glowing status badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 11px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-paid {
        background-color: rgba(113, 221, 55, 0.12);
        color: #71dd37;
        box-shadow: 0 0 10px rgba(113, 221, 55, 0.1);
    }
    .badge-pending {
        background-color: rgba(255, 171, 0, 0.12);
        color: #ffab00;
        box-shadow: 0 0 10px rgba(255, 171, 0, 0.1);
    }
    .badge-processing {
        background-color: rgba(255, 62, 29, 0.12);
        color: #ff3e1d;
        box-shadow: 0 0 10px rgba(255, 62, 29, 0.1);
        position: relative;
    }
    .badge-processing::after {
        content: '';
        display: inline-block;
        width: 6px;
        height: 6px;
        background-color: #ff3e1d;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.9); opacity: 1; }
        50% { transform: scale(1.4); opacity: 0.4; }
        100% { transform: scale(0.9); opacity: 1; }
    }

    /* Custom layout for avatar initials */
    .avatar-initial-custom {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    /* Live calculation field inside Modal */
    .live-calc-box {
        background: #f8f9fa;
        border: 1px dashed #696cff;
        border-radius: 8px;
        padding: 15px;
    }

    /* Interactive UI Elements */
    .hover-row:hover {
        background-color: rgba(105, 108, 255, 0.02) !important;
        cursor: pointer;
    }

    /* Toast system styles */
    .toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
    }
</style>
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="bx bx-check-circle me-2" style="font-size: 20px;"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Breadcrumbs and Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Employee Salary Dashboard</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Employee Salary</a></li>
                    <li class="breadcrumb-item active">Employee List</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
        </div>
    </div>



    <!-- Filters Panel -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.employee-salary.employees-list') }}" id="filterForm">
                <div class="row g-3">
                    <!-- Search Input -->
                    <div class="col-md-3">
                        <label class="form-label" for="search">Search Employee</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" id="search" name="search" class="form-control" placeholder="Search by name, ID, title..." value="{{ $search }}">
                        </div>
                    </div>
                    <!-- Company Filter -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold" for="company_id">Company</label>
                        <select id="company_id" name="company_id" class="form-select">
                            <option value="">All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ $selected_company_id == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i> Filter</button>
                        <a href="{{ route('admin.employee-salary.employees-list') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom-0 py-3">
            <h5 class="mb-0 fw-bold"><i class="bx bx-list-ol text-primary me-2"></i>Employee Payroll Directory</h5>
            <span class="badge bg-label-primary">{{ count($employees) }} Records Found</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Employee ID</th>
                        <th class="py-3">Employee Details</th>
                        <th class="py-3">Role</th>
                        <th class="py-3">Company</th>
                        <th class="py-3">Branch</th>
                        <th class="py-3 text-end">Base Salary</th>
                        @if(auth()->user()->can('view employee salary') || auth()->user()->can('edit employee salary') || auth()->user()->isSuperAdmin() || auth()->user()->isCompanyAdmin())
                        <th class="py-3 text-center">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($employees as $emp)
                    <tr class="hover-row">
                        <td class="fw-semibold text-primary">{{ $emp['employee_id'] }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-initial-custom {{ $emp['avatar_color'] }}">
                                    {{ substr($emp['name'], 0, 1) }}{{ substr(strstr($emp['name'], ' '), 1, 1) }}
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark">{{ $emp['name'] }}</span>
                                    <small class="text-muted">{{ $emp['email'] }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-medium text-dark">{{ $emp['designation'] }}</span>
                        </td>
                        <td><span class="badge bg-label-info">{{ $emp['company_name'] }}</span></td>
                        <td><span class="badge bg-label-secondary">{{ $emp['branch_name'] }}</span></td>
                        <td class="text-end fw-medium">₹{{ number_format($emp['base_salary'], 2) }}</td>
                        @if(auth()->user()->can('view employee salary') || auth()->user()->can('edit employee salary') || auth()->user()->isSuperAdmin() || auth()->user()->isCompanyAdmin() || auth()->id() == $emp['id'])
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('admin.employee-salary.details', $emp['id']) }}" class="btn btn-sm btn-label-primary px-3 shadow-none border-0" title="View Details">
                                    <i class="bx bx-show"></i>
                                </a>
                                @can('edit employee salary')
                                <button onclick="openSalaryModal({{ json_encode($emp) }})" class="btn btn-sm btn-label-success px-3 shadow-none border-0" type="button" title="Process Salary">
                                    <i class="bx bx-receipt"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() || auth()->user()->isCompanyAdmin() ? 7 : 6 }}" class="text-center py-5">
                            <div class="py-4">
                                <i class="bx bx-user-x text-muted mb-2" style="font-size: 64px;"></i>
                                <h6 class="text-muted">No employees match the filter criteria.</h6>
                                <p class="text-muted mb-0 small">Try removing search keywords or altering statuses.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sliding interactive Modal "Process Salary" -->
<div class="modal fade" id="salaryProcessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-dark" id="modalTitle">Process Salary</h5>
                    <p class="text-muted small mb-0" id="modalSubTitle">Compute payouts and adjustments</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <hr class="my-3">
            <div class="modal-body pt-0">
                <form id="salaryProcessForm" onsubmit="submitProcessSalary(event)">
                    <!-- Hidden IDs -->
                    <input type="hidden" id="emp_id">
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted">Employee ID</label>
                            <p class="form-control-plaintext text-dark fw-bold py-0" id="emp_code">-</p>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted">Designation</label>
                            <p class="form-control-plaintext text-dark fw-bold py-0" id="emp_designation">-</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold" for="modal_base_salary">Base Salary (₹)</label>
                        <input type="number" id="modal_base_salary" class="form-control fw-bold text-dark" readonly>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-success fw-semibold" for="modal_allowances">Allowances (₹)</label>
                            <input type="number" id="modal_allowances" class="form-control border-success text-success" oninput="calculateNet()" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-danger fw-semibold" for="modal_deductions">Deductions (₹)</label>
                            <input type="number" id="modal_deductions" class="form-control border-danger text-danger" oninput="calculateNet()" value="0" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-warning fw-semibold" for="modal_incentives">Pending Incentives (₹)</label>
                        <input type="number" id="modal_incentives" class="form-control border-warning text-warning fw-bold" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-info fw-semibold" for="modal_advance_deduction">Advance Deduction (₹)</label>
                        <input type="number" id="modal_advance_deduction" class="form-control border-info text-info fw-bold" readonly>
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="bx bx-calendar-check text-primary me-1"></i> Attendance Summary</h6>
                    <input type="hidden" id="modal_working_days">
                    <input type="hidden" id="modal_attended_days">
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <div class="border rounded p-2 text-center bg-light">
                                <small class="text-muted d-block">Working Days</small>
                                <span class="fw-bold text-dark fs-5" id="modal_working_days_label">0</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 text-center bg-light">
                                <small class="text-muted d-block">Attended</small>
                                <span class="fw-bold text-success fs-5" id="modal_attended_days_label">0</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 text-center bg-light">
                                <small class="text-muted d-block">Absent</small>
                                <span class="fw-bold text-danger fs-5" id="modal_absent_days_label">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="modal_month">Month</label>
                            <select id="modal_month" class="form-select">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m == date('m') ? 'selected' : '' }}>{{ Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="modal_year">Year</label>
                            <select id="modal_year" class="form-select">
                                <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                <option value="{{ date('Y')+1 }}">{{ date('Y')+1 }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="live-calc-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Per Day Rate (Base ÷ Working Days):</span>
                                <span class="fw-semibold text-dark" id="modal_per_day_label">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Attendance Salary (Per Day × Attended):</span>
                                <span class="fw-semibold text-primary" id="modal_att_salary_label">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">+ Allowances:</span>
                                <span class="fw-semibold text-success" id="modal_allowances_label">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">+ Incentives:</span>
                                <span class="fw-semibold text-warning" id="modal_incentives_label">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">- Deductions:</span>
                                <span class="fw-semibold text-danger" id="modal_deductions_label">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">- Advance Deduction:</span>
                                <span class="fw-semibold text-info" id="modal_advance_label">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                <span class="text-dark fw-bold" style="font-size: 16px;">Net Payout Amount:</span>
                                <span class="text-primary fw-extrabold" style="font-size: 20px;" id="modal_net_payable">₹ 0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-check-double me-1"></i> Disburse Salary</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container for Notifications -->
<div class="toast-container">
    <div id="actionToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bx bxs-check-circle" style="font-size: 20px;"></i>
                <span id="toastMessage">Action performed successfully!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    let currentEmployee = null;
    let toastBootstrap = null;

    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.getElementById('actionToast');
        if (toastEl) {
            toastBootstrap = new bootstrap.Toast(toastEl, { delay: 4000 });
        }
    });

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('actionToast');
        const toastMsg = document.getElementById('toastMessage');
        
        if (toastEl && toastMsg && toastBootstrap) {
            toastMsg.innerText = message;
            toastEl.className = 'toast align-items-center text-white border-0';
            if (type === 'success') toastEl.classList.add('bg-success');
            else if (type === 'info') toastEl.classList.add('bg-info');
            else if (type === 'danger') toastEl.classList.add('bg-danger');
            else toastEl.classList.add('bg-warning');
            toastBootstrap.show();
        } else {
            Swal.fire({ icon: type === 'danger' ? 'error' : type, title: type.charAt(0).toUpperCase() + type.slice(1), text: message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
    }

    function openSalaryModal(employee) {
        currentEmployee = employee;
        
        document.getElementById('emp_id').value = employee.id;
        document.getElementById('modalTitle').innerText = 'Process Salary - ' + employee.name;
        document.getElementById('emp_code').innerText = employee.employee_id;
        document.getElementById('emp_designation').innerText = employee.designation;
        document.getElementById('modal_base_salary').value = employee.base_salary;
        document.getElementById('modal_allowances').value = employee.allowances;
        document.getElementById('modal_deductions').value = employee.deductions;
        document.getElementById('modal_incentives').value = employee.pending_incentives || 0;
        var workDays = employee.working_days || 1;
        var attDays = employee.attended_days || 0;
        document.getElementById('modal_advance_deduction').value = employee.pending_advances || 0;
        document.getElementById('modal_working_days').value = workDays;
        document.getElementById('modal_attended_days').value = attDays;
        document.getElementById('modal_working_days_label').innerText = workDays;
        document.getElementById('modal_attended_days_label').innerText = attDays;
        document.getElementById('modal_absent_days_label').innerText = employee.absent_days !== undefined ? employee.absent_days : (workDays - attDays);
        document.getElementById('modal_base_salary').value = employee.base_salary;
        
        calculateNet();
        
        const modal = new bootstrap.Modal(document.getElementById('salaryProcessModal'));
        modal.show();
    }

    function calculateNet() {
        const base = parseFloat(document.getElementById('modal_base_salary').value) || 0;
        const workDays = parseFloat(document.getElementById('modal_working_days').value) || 1;
        const attDays = parseFloat(document.getElementById('modal_attended_days').value) || 0;
        const allowances = parseFloat(document.getElementById('modal_allowances').value) || 0;
        const deductions = parseFloat(document.getElementById('modal_deductions').value) || 0;
        const incentives = parseFloat(document.getElementById('modal_incentives').value) || 0;
        const advance = parseFloat(document.getElementById('modal_advance_deduction').value) || 0;
        
        const perDay = base / workDays;
        const attSalary = Math.round(perDay * attDays);
        const net = attSalary + allowances + incentives - deductions - advance;
        
        document.getElementById('modal_per_day_label').innerText = '₹' + perDay.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modal_att_salary_label').innerText = '₹' + attSalary.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modal_allowances_label').innerText = '+ ₹' + allowances.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modal_incentives_label').innerText = '+ ₹' + incentives.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modal_deductions_label').innerText = '- ₹' + deductions.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modal_advance_label').innerText = '- ₹' + advance.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modal_net_payable').innerText = '₹' + net.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function submitProcessSalary(e) {
        e.preventDefault();
        if (!currentEmployee) return;
        
        const base = parseFloat(document.getElementById('modal_base_salary').value) || 0;
        const allowances = parseFloat(document.getElementById('modal_allowances').value) || 0;
        const deductions = parseFloat(document.getElementById('modal_deductions').value) || 0;
        const incentivesTotal = parseFloat(document.getElementById('modal_incentives').value) || 0;
        const advanceDeduction = parseFloat(document.getElementById('modal_advance_deduction').value) || 0;
        const workingDays = parseInt(document.getElementById('modal_working_days').value) || 0;
        const attendedDays = parseFloat(document.getElementById('modal_attended_days').value) || 0;
        const month = document.getElementById('modal_month').value;
        const year = document.getElementById('modal_year').value;
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
        
        fetch('{{ route('admin.employee-salary.process-salary', '__ID__') }}'.replace('__ID__', currentEmployee.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                month: month,
                year: year,
                base_salary: base,
                allowances: allowances,
                deductions: deductions,
                incentives_total: incentivesTotal,
                advance_deduction: advanceDeduction,
                working_days: workingDays,
                attended_days: attendedDays
            })
        })
        .then(res => res.json())
        .then(data => {
            const modalEl = document.getElementById('salaryProcessModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.error || 'Failed to process salary.', 'danger');
            }
        })
        .catch(err => {
            showToast('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bx bx-check-double me-1"></i> Disburse Salary';
        });
    }
</script>
@endsection
