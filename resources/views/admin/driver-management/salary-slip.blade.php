@extends('admin.layouts.app')
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Monthly Salary Slip</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.driver-management.salary') }}">Driver Salary</a></li>
                    <li class="breadcrumb-item active">Salary Slip</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.driver-management.salary') }}" class="btn btn-outline-primary"><i class="bx bx-money me-1"></i> Salary Management</a>
            <a href="{{ route('admin.driver-management.advance') }}" class="btn btn-outline-primary"><i class="bx bx-coin me-1"></i> Advance Management</a>
            <a href="{{ route('admin.driver-management.salary-slip.list') }}" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i> All Salary Slips</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Driver <span class="text-danger">*</span></label>
                            <select name="driver_id" class="form-select" required>
                                <option value="">Select Driver</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->phone ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select">
                                @foreach($months as $m => $mName)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select">
                                @for($y = now()->year - 5; $y <= now()->year + 1; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Generate</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($slip)
            <div class="card" id="salary-slip-wrapper">
                <div class="card-header d-flex justify-content-between align-items-center py-3" style="background: #f8f9fa;">
                    <div>
                        <h5 class="mb-0">Salary Slip</h5>
                        <small class="text-muted">{{ $slip->monthName }} {{ $slip->year }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='{{ route('admin.driver-management.salary-slip') }}'"><i class="bx bx-save"></i> Save</button>
                        <form method="POST" action="{{ route('admin.driver-management.salary-slip.destroy', $slip->id) }}" class="d-inline" onsubmit="return confirm('Regenerate this slip? It will be deleted and re-created.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bx bx-refresh"></i> Regenerate</button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="salary-slip" class="salary-slip">
                        <div class="slip-header">
                            <div class="slip-title-row">
                                <div class="slip-company">
                                    <h2>{{ $slip->driver->company?->name ?? 'TRANSPORT ERP' }}</h2>
                                    <p>Driver Salary Slip</p>
                                </div>
                                <div class="slip-badge">
                                    <span class="slip-month-year">{{ $slip->monthName }} {{ $slip->year }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="slip-body">
                            <div class="slip-section">
                                <table class="slip-info-table">
                                    <tr>
                                        <td class="label">Driver Name</td>
                                        <td class="colon">:</td>
                                        <td class="value">{{ $slip->driver->name }} @if($slip->driver->driver_id) [{{ $slip->driver->driver_id }}] @endif</td>
                                        <td class="label">Month</td>
                                        <td class="colon">:</td>
                                        <td class="value">{{ $slip->monthName }} {{ $slip->year }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Phone</td>
                                        <td class="colon">:</td>
                                        <td class="value">{{ $slip->driver->phone ?? 'N/A' }}</td>
                                        <td class="label">Generated</td>
                                        <td class="colon">:</td>
                                        <td class="value">{{ $slip->generated_at ? $slip->generated_at->format('d-m-Y h:i A') : now()->format('d-m-Y h:i A') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="slip-section">
                                <table class="slip-amount-table">
                                    <tr>
                                        <td class="amt-label">Basic Salary</td>
                                        <td class="amt-sep">:</td>
                                        <td class="amt-value">₹ {{ number_format($slip->salary_amount, 2) }}</td>
                                    </tr>
                                </table>
                            </div>

                            @php $advances = $slip->advances ?? collect([]); @endphp
                            @if($advances->count() > 0)
                            <div class="slip-section">
                                <h5>Advance Deductions</h5>
                                <table class="slip-ded-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th class="text-end">Advance Amount</th>
                                            <th>Type</th>
                                            <th class="text-end">Deduction This Month</th>
                                            <th class="text-end">Balance</th>
                                            <th>Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($advances as $advance)
                                        @php
                                            $isModel = $advance instanceof \App\Models\DriverAdvance;
                                            $dedType = $isModel ? $advance->deduction_type : ($advance['deduction_type'] ?? 'full');
                                            $advAmount = $isModel ? $advance->amount : ($advance['amount'] ?? 0);
                                            $dedAmt = $isModel ? ($dedType === 'monthly' ? $advance->monthly_deduction : $advance->amount) : ($advance['deduction_amount'] ?? 0);
                                            $bal = $isModel ? null : ($advance['balance'] ?? null);
                                            $advDate = $isModel ? $advance->date->format('d-m-Y') : ($advance['date'] ?? '-');
                                            $advRemark = $isModel ? ($advance->remark ?? '-') : ($advance['remark'] ?? '-');
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $advDate }}</td>
                                            <td class="text-end">₹ {{ number_format($advAmount, 2) }}</td>
                                            <td>{{ $dedType === 'monthly' ? 'Monthly' : 'Full' }}</td>
                                            <td class="text-end">₹ {{ number_format($dedAmt, 2) }}</td>
                                            <td class="text-end">{{ $bal !== null ? '₹ ' . number_format($bal, 2) : '-' }}</td>
                                            <td>{{ $advRemark }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="slip-section">
                                <div class="slip-no-data">No advances recorded for this month.</div>
                            </div>
                            @endif

                            <div class="slip-section slip-summary">
                                <table class="slip-summary-table">
                                    <tr>
                                        <td class="sum-label">Basic Salary</td>
                                        <td class="sum-sep">:</td>
                                        <td class="sum-value">₹ {{ number_format($slip->salary_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="sum-label">Total Deductions</td>
                                        <td class="sum-sep">:</td>
                                        <td class="sum-value sum-ded">₹ {{ number_format($slip->total_deductions, 2) }}</td>
                                    </tr>
                                    <tr class="sum-total-row">
                                        <td class="sum-label">Net Payable</td>
                                        <td class="sum-sep">:</td>
                                        <td class="sum-value sum-net">₹ {{ number_format($slip->net_payable, 2) }}</td>
                                    </tr>
                                </table>
                                <div class="slip-amount-box">
                                    <span class="amount-in-words">Rupees {{ number_format($slip->net_payable, 2) }} Only</span>
                                </div>
                            </div>

                            <div class="slip-section slip-footer">
                                <div class="slip-signatures">
                                    <div class="sig-box">
                                        <span class="sig-line"></span>
                                        <span class="sig-label">Prepared By</span>
                                    </div>
                                    <div class="sig-box">
                                        <span class="sig-line"></span>
                                        <span class="sig-label">Checked By</span>
                                    </div>
                                    <div class="sig-box">
                                        <span class="sig-line"></span>
                                        <span class="sig-label">Driver Signature</span>
                                    </div>
                                </div>
                                <div class="slip-note">
                                    This is a computer-generated salary slip.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Salary Slip History</h5></div>
                <div class="card-body p-0">
                    @if($history->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($history as $h)
                        <a href="{{ route('admin.driver-management.salary-slip', ['driver_id' => $h->driver_id, 'month' => $h->month, 'year' => $h->year]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $slip && $slip->id === $h->id ? 'active' : '' }}">
                            <div>
                                <strong>{{ $h->driver?->name ?? 'N/A' }}</strong><br>
                                <small>{{ Carbon\Carbon::create()->month($h->month)->format('F') }} {{ $h->year }}</small>
                            </div>
                            <span class="fw-semibold">₹ {{ number_format($h->net_payable, 0) }}</span>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-receipt bx-lg mb-2 d-block"></i>
                        No salary slips generated yet
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<style>
.salary-slip {
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #222;
    padding: 0;
}
.slip-header {
    padding: 30px 35px 20px;
    border-bottom: 3px double #1a73e8;
}
.slip-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.slip-company h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: 2px;
    color: #1a73e8;
}
.slip-company p {
    margin: 4px 0 0;
    font-size: 14px;
    color: #666;
    letter-spacing: 1px;
}
.slip-badge .slip-month-year {
    display: inline-block;
    background: #1a73e8;
    color: #fff;
    padding: 8px 20px;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 1px;
}
.slip-body {
    padding: 25px 35px 30px;
}
.slip-section {
    margin-bottom: 24px;
}
.slip-section h5 {
    margin: 0 0 10px;
    font-size: 15px;
    font-weight: 700;
    color: #1a73e8;
    border-bottom: 1px solid #ddd;
    padding-bottom: 6px;
}
.slip-info-table {
    width: 100%;
    border-collapse: collapse;
}
.slip-info-table td {
    padding: 5px 8px;
    font-size: 14px;
}
.slip-info-table .label {
    font-weight: 600;
    color: #555;
    width: 120px;
}
.slip-info-table .colon {
    width: 12px;
    text-align: center;
    color: #999;
}
.slip-info-table .value {
    font-weight: 500;
    width: 200px;
}
.slip-amount-table {
    width: 100%;
    max-width: 320px;
    margin-left: auto;
    border-collapse: collapse;
}
.slip-amount-table td {
    padding: 6px 8px;
    font-size: 15px;
}
.slip-amount-table .amt-label {
    font-weight: 600;
    text-align: right;
    width: 140px;
}
.slip-amount-table .amt-sep {
    width: 16px;
    text-align: center;
}
.slip-amount-table .amt-value {
    font-weight: 700;
    font-size: 17px;
    text-align: right;
}
.slip-ded-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.slip-ded-table th {
    background: #f0f4ff;
    padding: 8px 10px;
    font-weight: 600;
    text-align: left;
    border: 1px solid #dde1e6;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #333;
}
.slip-ded-table td {
    padding: 7px 10px;
    border: 1px solid #dde1e6;
}
.slip-ded-table tbody tr:nth-child(even) {
    background: #fafbfc;
}
.slip-no-data {
    color: #888;
    font-style: italic;
    font-size: 14px;
    padding: 8px 0;
}
.slip-summary {
    border-top: 2px solid #1a73e8;
    padding-top: 20px;
    margin-top: 10px;
}
.slip-summary-table {
    width: 100%;
    max-width: 360px;
    margin-left: auto;
    border-collapse: collapse;
}
.slip-summary-table td {
    padding: 6px 8px;
    font-size: 15px;
}
.slip-summary-table .sum-label {
    font-weight: 600;
    text-align: right;
    width: 160px;
}
.slip-summary-table .sum-sep {
    width: 16px;
    text-align: center;
}
.slip-summary-table .sum-value {
    font-weight: 500;
    text-align: right;
}
.slip-summary-table .sum-ded {
    color: #d32f2f;
}
.slip-summary-table .sum-total-row td {
    padding-top: 10px;
    border-top: 2px solid #333;
    font-size: 18px;
}
.slip-summary-table .sum-total-row .sum-label {
    font-weight: 800;
}
.slip-summary-table .sum-net {
    font-weight: 800;
    font-size: 20px;
    color: #1a73e8;
}
.slip-amount-box {
    text-align: right;
    margin-top: 10px;
    font-size: 13px;
    font-style: italic;
    color: #666;
}
.slip-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px dashed #ccc;
}
.slip-signatures {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}
.sig-box {
    text-align: center;
    width: 30%;
}
.sig-line {
    display: block;
    height: 1px;
    background: #999;
    margin-bottom: 40px;
    width: 100%;
}
.sig-label {
    font-size: 13px;
    color: #666;
}
.slip-note {
    text-align: center;
    font-size: 12px;
    color: #aaa;
    border-top: 1px solid #eee;
    padding-top: 12px;
}
</style>
@endsection
