@extends('admin.layouts.app')

@section('style')
<style>
.table-sm-compact > :not(caption) > * > * { padding: .2rem .3rem; font-size: .75rem; white-space: nowrap; }
</style>
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y" style="overflow-x:hidden;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Create Trip</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.transport.trips.index') }}">Trips</a></li>
                    <li class="breadcrumb-item active">Create Trip</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.transport.trips.store') }}">
        @csrf
        <input type="hidden" name="builty_id" value="{{ $builty->id }}">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Bilty Information</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">LR No</label>
                        <p class="fw-semibold">{{ $builty->lr_no }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date</label>
                        <p class="fw-semibold">{{ $builty->lr_date ? date('d M Y', strtotime($builty->lr_date)) : '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Consignor</label>
                        <p class="fw-semibold">{{ $builty->consignor->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Consignee</label>
                        <p class="fw-semibold">{{ $builty->consignee->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">From</label>
                        <p class="fw-semibold">{{ $builty->originCity->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">To</label>
                        <p class="fw-semibold">{{ $builty->destinationCity->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Total Amount</label>
                        <p class="fw-semibold">₹{{ number_format($builty->total_amount, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Advance Amount</label>
                        <p class="fw-semibold">₹{{ number_format($builty->advance_amount, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Trip Summary</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Fast Tag Total Amount</label>
                        <input type="number" step="0.01" name="fasttag_total_amount" id="fasttag_total_amount" class="form-control" value="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fuel Amount</label>
                        <input type="number" step="0.01" name="fuel_amount" id="fuel_amount" class="form-control" value="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other Amount</label>
                        <input type="number" step="0.01" name="other_amount" id="other_amount" class="form-control" value="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">AdBlue Amount</label>
                        <input type="number" step="0.01" name="adblue_total_amount" id="adblue_total_amount" class="form-control" value="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Advance Amount</label>
                        <input type="number" step="0.01" name="advance_total_amount" id="advance_total_amount" class="form-control" value="0" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Fast Tag Details</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.transport.trips.fast-tag.download-template') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
                        <button type="button" class="btn btn-sm btn-outline-info" id="importFastTagBtn"><i class="bx bx-import me-1"></i> Import</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addFastTagRow"><i class="bx bx-plus me-1"></i> Add Row</button>
                    </div>
                </div>
                <input type="file" id="fastTagFileInput" accept=".xlsx,.xls,.csv" style="display:none">
                <div id="fastTagContainer">
                    <div class="detail-entry border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Transaction Time</label>
                                <input type="text" name="fast_tag_details[0][transaction_time]" class="form-control form-control-sm datetime-picker" placeholder="DD-Mon-YY hh:mm AM/PM">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Amount (₹)</label>
                                <input type="number" step="0.01" name="fast_tag_details[0][amount]" class="form-control form-control-sm fast-tag-amount" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Location</label>
                                <input type="text" name="fast_tag_details[0][location]" class="form-control form-control-sm" placeholder="Location">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">One Way (₹)</label>
                                <input type="number" step="0.01" name="fast_tag_details[0][one_way]" class="form-control form-control-sm fast-tag-oneway" value="0">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label small">Description</label>
                                <input type="text" name="fast_tag_details[0][description]" class="form-control form-control-sm" placeholder="Description">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Transaction ID</label>
                                <input type="text" name="fast_tag_details[0][transaction_id]" class="form-control form-control-sm" placeholder="Txn ID">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Return (₹)</label>
                                <input type="number" step="0.01" name="fast_tag_details[0][return]" class="form-control form-control-sm fast-tag-return" value="0">
                            </div>
                            <div class="col-md-3 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Fuel Details</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.transport.trips.fuel-detail.download-template') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importFuelModal"><i class="bx bx-import me-1"></i> Import</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addFuelRow"><i class="bx bx-plus me-1"></i> Add Row</button>
                    </div>
                </div>
                <div id="fuelContainer">
                    <div class="fuel-entry border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Date</label>
                                <input type="date" max="9999-12-31" name="fuel_details[0][date]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fuel Company</label>
                                <select name="fuel_details[0][fuel_company_id]" class="form-select form-select-sm fuel-company-select">
                                    <option value="">Select Company</option>
                                    @foreach($fuelCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fuel Pump</label>
                                <select name="fuel_details[0][fuel_pump_id]" class="form-select form-select-sm fuel-pump-select">
                                    <option value="">Select Pump</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Qty (L)</label>
                                <input type="number" step="0.01" name="fuel_details[0][quantity]" class="form-control form-control-sm fuel-quantity" value="0">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label small">Rate (₹)</label>
                                <input type="number" step="0.01" name="fuel_details[0][rate]" class="form-control form-control-sm fuel-rate" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Amount (₹)</label>
                                <input type="number" step="0.01" name="fuel_details[0][amount]" class="form-control form-control-sm fuel-amount" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Odometer (KM)</label>
                                <input type="number" step="0.01" name="fuel_details[0][km]" class="form-control form-control-sm fuel-km" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Payment Type</label>
                                <select name="fuel_details[0][payment_type]" class="form-select form-select-sm">
                                    <option value="">Select</option>
                                    <option value="credit">Credit</option>
                                    <option value="debit">Debit</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-9">
                                <label class="form-label small">Remark</label>
                                <input type="text" name="fuel_details[0][remark]" class="form-control form-control-sm" placeholder="Remark" maxlength="500">
                            </div>
                            <div class="col-md-3 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">AdBlue Details</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.transport.trips.adblue-detail.download-template') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importAdBlueModal"><i class="bx bx-import me-1"></i> Import</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addAdBlueRow"><i class="bx bx-plus me-1"></i> Add Row</button>
                    </div>
                </div>
                <div id="adblueContainer">
                    <div class="detail-entry border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Date</label>
                                <input type="date" max="9999-12-31" name="adblue_details[0][date]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">AdBlue Company</label>
                                <select name="adblue_details[0][adblue_company_id]" class="form-select form-select-sm adblue-company-select">
                                    <option value="">Select Company</option>
                                    @foreach($adblueCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Qty (L)</label>
                                <input type="number" step="0.01" name="adblue_details[0][quantity]" class="form-control form-control-sm adblue-quantity" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Rate (₹)</label>
                                <input type="number" step="0.01" name="adblue_details[0][rate]" class="form-control form-control-sm adblue-rate" value="0">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label small">Amount (₹)</label>
                                <input type="number" step="0.01" name="adblue_details[0][amount]" class="form-control form-control-sm adblue-amount" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">KM</label>
                                <input type="number" step="0.01" name="adblue_details[0][km]" class="form-control form-control-sm adblue-km" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Payment Type</label>
                                <select name="adblue_details[0][payment_type]" class="form-select form-select-sm">
                                    <option value="">Select</option>
                                    <option value="credit">Credit</option>
                                    <option value="debit">Debit</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Other Amount Details</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.transport.trips.other-amount-detail.download-template') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importOtherModal"><i class="bx bx-import me-1"></i> Import</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addOtherRow"><i class="bx bx-plus me-1"></i> Add Row</button>
                    </div>
                </div>
                <div id="otherContainer">
                    <div class="detail-entry border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Title</label>
                                <input type="text" name="other_details[0][title]" class="form-control form-control-sm" placeholder="Title">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Amount (₹)</label>
                                <input type="number" step="0.01" name="other_details[0][amount]" class="form-control form-control-sm other-amount" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Date</label>
                                <input type="date" max="9999-12-31" name="other_details[0][date]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark</label>
                                <input type="text" name="other_details[0][remark]" class="form-control form-control-sm" placeholder="Remark">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Advance Details</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.transport.trips.advance-detail.download-template') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i> Template</a>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importAdvanceModal"><i class="bx bx-import me-1"></i> Import</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addAdvanceRow"><i class="bx bx-plus me-1"></i> Add Row</button>
                    </div>
                </div>
                <div id="advanceContainer">
                    <div class="advance-entry border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Date</label>
                                <input type="date" max="9999-12-31" name="advance_details[0][date]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fuel Company</label>
                                <select name="advance_details[0][fuel_company_id]" class="form-select form-select-sm advance-company-select">
                                    <option value="">Select Company</option>
                                    @foreach($fuelCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fuel Pump</label>
                                <select name="advance_details[0][fuel_pump_id]" class="form-select form-select-sm advance-pump-select">
                                    <option value="">Select Pump</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Advance Amount (₹)</label>
                                <input type="number" step="0.01" name="advance_details[0][advance_amount]" class="form-control form-control-sm advance-amount" value="0">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label small">Payment Type</label>
                                <select name="advance_details[0][payment_type]" class="form-select form-select-sm">
                                    <option value="">Select</option>
                                    <option value="credit">Credit</option>
                                    <option value="debit">Debit</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Remark</label>
                                <input type="text" name="advance_details[0][remark]" class="form-control form-control-sm" placeholder="Remark" maxlength="500">
                            </div>
                            <div class="col-md-3 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.transport.trips.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save Trip</button>
        </div>
    </form>
</div>

<div class="modal fade" id="importFuelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import Fuel Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Download the template, fill it in, set defaults, then upload.</p>
                <div class="mb-3">
                    <label class="form-label">Fuel Company <span class="text-muted">(default for all rows)</span></label>
                    <select id="modalFuelCompany" class="form-select">
                        <option value="">Select Company</option>
                        @foreach($fuelCompanies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fuel Pump <span class="text-muted">(default for all rows)</span></label>
                    <select id="modalFuelPump" class="form-select">
                        <option value="">Select Pump</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Choose Excel File (xlsx, xls, csv) *</label>
                    <input type="file" id="modalFuelFile" name="modalFuelFile" class="form-control" accept=".xlsx,.xls,.csv">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitFuelImport"><i class="bx bx-upload me-1"></i> Import</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importAdBlueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import AdBlue Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Download the template, fill it in, select a default company, then upload.</p>
                <div class="mb-3">
                    <label class="form-label">AdBlue Company <span class="text-muted">(default for all rows)</span></label>
                    <select id="modalAdBlueCompany" class="form-select">
                        <option value="">Select Company</option>
                        @foreach($adblueCompanies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Choose Excel File (xlsx, xls, csv) *</label>
                    <input type="file" id="modalAdBlueFile" name="modalAdBlueFile" class="form-control" accept=".xlsx,.xls,.csv">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitAdBlueImport"><i class="bx bx-upload me-1"></i> Import</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importOtherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import Other Amount Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Download the template, fill it in, then upload.</p>
                <div class="mb-3">
                    <label class="form-label">Choose Excel File (xlsx, xls, csv) *</label>
                    <input type="file" id="modalOtherFile" name="modalOtherFile" class="form-control" accept=".xlsx,.xls,.csv">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitOtherImport"><i class="bx bx-upload me-1"></i> Import</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importAdvanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import Advance Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Download the template, fill it in, set defaults, then upload.</p>
                <div class="mb-3">
                    <label class="form-label">Fuel Company <span class="text-muted">(default for all rows)</span></label>
                    <select id="modalAdvanceCompany" class="form-select">
                        <option value="">Select Company</option>
                        @foreach($fuelCompanies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fuel Pump <span class="text-muted">(default for all rows)</span></label>
                    <select id="modalAdvancePump" class="form-select">
                        <option value="">Select Pump</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Choose Excel File (xlsx, xls, csv) *</label>
                    <input type="file" id="modalAdvanceFile" name="modalAdvanceFile" class="form-control" accept=".xlsx,.xls,.csv">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitAdvanceImport"><i class="bx bx-upload me-1"></i> Import</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function() {
        var pumpsUrl = '{{ route("admin.transport.trips.pumps-by-company", ":companyId") }}';

        function loadPumps(companySelect, selectedPumpId) {
            const companyId = companySelect.val();
            const context = companySelect.closest('.fuel-entry, .advance-entry');
            const pumpSelect = context.find('.fuel-pump-select, .advance-pump-select');
            pumpSelect.empty().append('<option value="">Select Pump</option>');
            if (companyId) {
                $.get(pumpsUrl.replace(':companyId', companyId), function(data) {
                    $.each(data, function(i, pump) {
                        pumpSelect.append('<option value="' + pump.id + '" ' + (selectedPumpId && pump.id == selectedPumpId ? 'selected' : '') + '>' + pump.name + '</option>');
                    });
                    pumpSelect.trigger('change');
                });
            } else {
                pumpSelect.trigger('change');
            }
        }

        $(document).on('change', '.fuel-company-select, .advance-company-select', function() {
            loadPumps($(this));
        });

        $('#modalFuelCompany').on('change', function() {
            const companyId = $(this).val();
            const pumpSelect = $('#modalFuelPump');
            pumpSelect.empty().append('<option value="">Select Pump</option>');
            if (companyId) {
                $.get(pumpsUrl.replace(':companyId', companyId), function(data) {
                    $.each(data, function(i, pump) {
                        pumpSelect.append('<option value="' + pump.id + '">' + pump.name + '</option>');
                    });
                    pumpSelect.trigger('change');
                });
            } else {
                pumpSelect.trigger('change');
            }
        });

        $('#modalAdvanceCompany').on('change', function() {
            const companyId = $(this).val();
            const pumpSelect = $('#modalAdvancePump');
            pumpSelect.empty().append('<option value="">Select Pump</option>');
            if (companyId) {
                $.get(pumpsUrl.replace(':companyId', companyId), function(data) {
                    $.each(data, function(i, pump) {
                        pumpSelect.append('<option value="' + pump.id + '">' + pump.name + '</option>');
                    });
                    pumpSelect.trigger('change');
                });
            } else {
                pumpSelect.trigger('change');
            }
        });

        $(document).on('input change', '.fuel-quantity, .fuel-rate', function() {
            const row = $(this).closest('.fuel-entry');
            const qty = parseFloat(row.find('.fuel-quantity').val()) || 0;
            const rate = parseFloat(row.find('.fuel-rate').val()) || 0;
            row.find('.fuel-amount').val((qty * rate).toFixed(2));
            recalcTotals();
        });

        $(document).on('input change', '.adblue-quantity, .adblue-rate', function() {
            const row = $(this).closest('.detail-entry, tr');
            const qty = parseFloat(row.find('.adblue-quantity').val()) || 0;
            const rate = parseFloat(row.find('.adblue-rate').val()) || 0;
            row.find('.adblue-amount').val((qty * rate).toFixed(2));
            recalcTotals();
        });

        $('#submitFuelImport').click(function() {
            const file = $('#modalFuelFile')[0].files[0];
            if (!file) { Swal.fire({ icon: 'warning', title: 'No File', text: 'Please select a file.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); return; }
            const formData = new FormData();
            formData.append('file', file);
            formData.append('fuel_company_id', $('#modalFuelCompany').val() || '');
            formData.append('fuel_pump_id', $('#modalFuelPump').val() || '');
            formData.append('_token', '{{ csrf_token() }}');
            $('#submitFuelImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing...');
            $.ajax({
                url: '{{ route("admin.transport.trips.fuel-detail.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success && res.data.length) {
                        const baseIdx = getIndex('#fuelContainer');
                        $.each(res.data, function(i, row) {
                            const idx = baseIdx + i;
                            const html = `<div class="fuel-entry border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Date</label>
                                        <input type="date" max="9999-12-31" name="fuel_details[${idx}][date]" class="form-control form-control-sm" value="${row.date || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Fuel Company</label>
                                        <select name="fuel_details[${idx}][fuel_company_id]" class="form-select form-select-sm fuel-company-select">
                                            <option value="">Select Company</option>
                                            @foreach($fuelCompanies as $company)
                                            <option value="{{ $company->id }}" ${row.fuel_company_id == {{ $company->id }} ? 'selected' : ''}>{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Fuel Pump</label>
                                        <select name="fuel_details[${idx}][fuel_pump_id]" class="form-select form-select-sm fuel-pump-select">
                                            <option value="">Select Pump</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Qty (L)</label>
                                        <input type="number" step="0.01" name="fuel_details[${idx}][quantity]" class="form-control form-control-sm fuel-quantity" value="${row.quantity || 0}">
                                    </div>
                                </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-3">
                                            <label class="form-label small">Rate (₹)</label>
                                            <input type="number" step="0.01" name="fuel_details[${idx}][rate]" class="form-control form-control-sm fuel-rate" value="${row.rate || 0}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Amount (₹)</label>
                                            <input type="number" step="0.01" name="fuel_details[${idx}][amount]" class="form-control form-control-sm fuel-amount" value="${row.amount || 0}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Odometer (KM)</label>
                                            <input type="number" step="0.01" name="fuel_details[${idx}][km]" class="form-control form-control-sm fuel-km" value="${row.km || 0}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Payment Type</label>
                                            <select name="fuel_details[${idx}][payment_type]" class="form-select form-select-sm">
                                                <option value="">Select</option>
                                                <option value="credit" ${row.payment_type == 'credit' ? 'selected' : ''}>Credit</option>
                                                <option value="debit" ${row.payment_type == 'debit' ? 'selected' : ''}>Debit</option>
                                                <option value="cash" ${row.payment_type == 'cash' ? 'selected' : ''}>Cash</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-9">
                                            <label class="form-label small">Remark</label>
                                            <input type="text" name="fuel_details[${idx}][remark]" class="form-control form-control-sm" placeholder="Remark" maxlength="500" value="${row.remark || ''}">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end justify-content-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                                        </div>
                                    </div>
                                </div>`;
                            $('#fuelContainer').append(html);
                        });
                        const newCnt = res.data.length;
                        $('#fuelContainer').find('.fuel-company-select').slice(-newCnt).each(function(i) {
                            if ($(this).val()) {
                                loadPumps($(this), res.data[i]?.fuel_pump_id);
                            }
                        });
                        recalcTotals();
                        $('#importFuelModal').modal('hide');
                    }
                    $('#modalFuelFile').val('');
                    $('#submitFuelImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Unknown error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    $('#modalFuelFile').val('');
                    $('#submitFuelImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                }
            });
        });

        $('#submitAdBlueImport').click(function() {
            const file = $('#modalAdBlueFile')[0].files[0];
            if (!file) { Swal.fire({ icon: 'warning', title: 'No File', text: 'Please select a file.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); return; }
            const formData = new FormData();
            formData.append('file', file);
            formData.append('adblue_company_id', $('#modalAdBlueCompany').val() || '');
            formData.append('_token', '{{ csrf_token() }}');
            $('#submitAdBlueImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing...');
            $.ajax({
                url: '{{ route("admin.transport.trips.adblue-detail.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success && res.data.length) {
                        const baseIdx = getIndex('#adblueContainer');
                        $.each(res.data, function(i, row) {
                            const idx = baseIdx + i;
                            const html = `<div class="detail-entry border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Date</label>
                                        <input type="date" max="9999-12-31" name="adblue_details[${idx}][date]" class="form-control form-control-sm" value="${row.date || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">AdBlue Company</label>
                                        <select name="adblue_details[${idx}][adblue_company_id]" class="form-select form-select-sm adblue-company-select">
                                            <option value="">Select Company</option>
                                            @foreach($adblueCompanies as $company)
                                            <option value="{{ $company->id }}" ${row.adblue_company_id == {{ $company->id }} ? 'selected' : ''}>{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Qty (L)</label>
                                        <input type="number" step="0.01" name="adblue_details[${idx}][quantity]" class="form-control form-control-sm adblue-quantity" value="${row.quantity || 0}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Rate (₹)</label>
                                        <input type="number" step="0.01" name="adblue_details[${idx}][rate]" class="form-control form-control-sm adblue-rate" value="${row.rate || 0}">
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label small">Amount (₹)</label>
                                        <input type="number" step="0.01" name="adblue_details[${idx}][amount]" class="form-control form-control-sm adblue-amount" value="${row.amount || 0}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">KM</label>
                                        <input type="number" step="0.01" name="adblue_details[${idx}][km]" class="form-control form-control-sm adblue-km" value="${row.km || 0}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Payment Type</label>
                                        <select name="adblue_details[${idx}][payment_type]" class="form-select form-select-sm">
                                            <option value="">Select</option>
                                            <option value="credit" ${row.payment_type && row.payment_type.toLowerCase() === 'credit' ? 'selected' : ''}>Credit</option>
                                            <option value="debit" ${row.payment_type && row.payment_type.toLowerCase() === 'debit' ? 'selected' : ''}>Debit</option>
                                            <option value="cash" ${row.payment_type && row.payment_type.toLowerCase() === 'cash' ? 'selected' : ''}>Cash</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                                    </div>
                                </div>
                            </div>`;
                            $('#adblueContainer').append(html);
                        });
                        recalcTotals();
                        $('#importAdBlueModal').modal('hide');
                    }
                    $('#modalAdBlueFile').val('');
                    $('#submitAdBlueImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Unknown error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    $('#modalAdBlueFile').val('');
                    $('#submitAdBlueImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                }
            });
        });

        function initDateTimePickers() {
            $('.datetime-picker').flatpickr({
                enableTime: true,
                dateFormat: 'Y-m-d H:i',
                altFormat: 'd-M-y h:i K',
                altInput: true,
                time_24hr: false,
            });
        }
        initDateTimePickers();

        function recalcTotals() {
            let fastTagTotal = 0;
            $('.fast-tag-amount').each(function() { fastTagTotal += parseFloat($(this).val()) || 0; });
            $('#fasttag_total_amount').val(fastTagTotal.toFixed(2));

            let fuelTotal = 0;
            $('.fuel-amount').each(function() { fuelTotal += parseFloat($(this).val()) || 0; });
            $('#fuel_amount').val(fuelTotal.toFixed(2));

            let adblueTotal = 0;
            $('.adblue-amount').each(function() { adblueTotal += parseFloat($(this).val()) || 0; });
            $('#adblue_total_amount').val(adblueTotal.toFixed(2));

            let otherTotal = 0;
            $('.other-amount').each(function() { otherTotal += parseFloat($(this).val()) || 0; });
            $('#other_amount').val(otherTotal.toFixed(2));

            let advanceTotal = 0;
            $('.advance-amount').each(function() { advanceTotal += parseFloat($(this).val()) || 0; });
            $('#advance_total_amount').val(advanceTotal.toFixed(2));
        }

        function getIndex(container) {
            if ($(container).is('#fuelContainer')) {
                return $(container).children('.fuel-entry').length;
            }
            if ($(container).is('#advanceContainer')) {
                return $(container).children('.advance-entry').length;
            }
            const entries = $(container).children('.detail-entry');
            if (entries.length) {
                return entries.length;
            }
            return $(container).children('tr').length;
        }

        $('#addFastTagRow').click(function() {
            const idx = getIndex('#fastTagContainer');
            const html = `<div class="detail-entry border rounded p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Transaction Time</label>
                        <input type="text" name="fast_tag_details[${idx}][transaction_time]" class="form-control form-control-sm datetime-picker" placeholder="DD-Mon-YY hh:mm AM/PM">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Amount (₹)</label>
                        <input type="number" step="0.01" name="fast_tag_details[${idx}][amount]" class="form-control form-control-sm fast-tag-amount" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Location</label>
                        <input type="text" name="fast_tag_details[${idx}][location]" class="form-control form-control-sm" placeholder="Location">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">One Way (₹)</label>
                        <input type="number" step="0.01" name="fast_tag_details[${idx}][one_way]" class="form-control form-control-sm fast-tag-oneway" value="0">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label small">Description</label>
                        <input type="text" name="fast_tag_details[${idx}][description]" class="form-control form-control-sm" placeholder="Description">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Transaction ID</label>
                        <input type="text" name="fast_tag_details[${idx}][transaction_id]" class="form-control form-control-sm" placeholder="Txn ID">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Return (₹)</label>
                        <input type="number" step="0.01" name="fast_tag_details[${idx}][return]" class="form-control form-control-sm fast-tag-return" value="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                    </div>
                </div>
            </div>`;
            $('#fastTagContainer').append(html);
            initDateTimePickers();
        });

        $('#importFastTagBtn').click(function() {
            $('#fastTagFileInput').click();
        });

        $('#fastTagFileInput').change(function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            $.ajax({
                url: '{{ route("admin.transport.trips.fast-tag.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success && res.data.length) {
                        const baseIdx = getIndex('#fastTagContainer');
                        $.each(res.data, function(i, row) {
                            const idx = baseIdx + i;
                            const html = `<div class="detail-entry border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Transaction Time</label>
                                        <input type="text" name="fast_tag_details[${idx}][transaction_time]" class="form-control form-control-sm datetime-picker" placeholder="DD-Mon-YY hh:mm AM/PM" value="${row.transaction_time || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Amount (₹)</label>
                                        <input type="number" step="0.01" name="fast_tag_details[${idx}][amount]" class="form-control form-control-sm fast-tag-amount" value="${row.amount || 0}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Location</label>
                                        <input type="text" name="fast_tag_details[${idx}][location]" class="form-control form-control-sm" placeholder="Location" value="${row.location || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">One Way (₹)</label>
                                        <input type="number" step="0.01" name="fast_tag_details[${idx}][one_way]" class="form-control form-control-sm fast-tag-oneway" value="${row.one_way || 0}">
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label small">Description</label>
                                        <input type="text" name="fast_tag_details[${idx}][description]" class="form-control form-control-sm" placeholder="Description" value="${row.description || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Transaction ID</label>
                                        <input type="text" name="fast_tag_details[${idx}][transaction_id]" class="form-control form-control-sm" placeholder="Txn ID" value="${row.transaction_id || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Return (₹)</label>
                                        <input type="number" step="0.01" name="fast_tag_details[${idx}][return]" class="form-control form-control-sm fast-tag-return" value="${row['return'] || 0}">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                                    </div>
                                </div>
                            </div>`;
                            $('#fastTagContainer').append(html);
                        });
                        initDateTimePickers();
                        recalcTotals();
                    }
                    $(this).val('');
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Unknown error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    $('#fastTagFileInput').val('');
                }
            });
        });

        $('#addFuelRow').click(function() {
            const idx = getIndex('#fuelContainer');
            const html = `<div class="fuel-entry border rounded p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Date</label>
                        <input type="date" max="9999-12-31" name="fuel_details[${idx}][date]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fuel Company</label>
                        <select name="fuel_details[${idx}][fuel_company_id]" class="form-select form-select-sm fuel-company-select">
                            <option value="">Select Company</option>
                            @foreach($fuelCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fuel Pump</label>
                        <select name="fuel_details[${idx}][fuel_pump_id]" class="form-select form-select-sm fuel-pump-select">
                            <option value="">Select Pump</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Qty (L)</label>
                        <input type="number" step="0.01" name="fuel_details[${idx}][quantity]" class="form-control form-control-sm fuel-quantity" value="0">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label small">Rate (₹)</label>
                        <input type="number" step="0.01" name="fuel_details[${idx}][rate]" class="form-control form-control-sm fuel-rate" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Amount (₹)</label>
                        <input type="number" step="0.01" name="fuel_details[${idx}][amount]" class="form-control form-control-sm fuel-amount" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Odometer (KM)</label>
                        <input type="number" step="0.01" name="fuel_details[${idx}][km]" class="form-control form-control-sm fuel-km" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Payment Type</label>
                        <select name="fuel_details[${idx}][payment_type]" class="form-select form-select-sm">
                            <option value="">Select</option>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-9">
                        <label class="form-label small">Remark</label>
                        <input type="text" name="fuel_details[${idx}][remark]" class="form-control form-control-sm" placeholder="Remark" maxlength="500">
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                    </div>
                </div>
            </div>`;
            $('#fuelContainer').append(html);
        });

        $('#addAdBlueRow').click(function() {
            const idx = getIndex('#adblueContainer');
            const html = `<div class="detail-entry border rounded p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Date</label>
                        <input type="date" max="9999-12-31" name="adblue_details[${idx}][date]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">AdBlue Company</label>
                        <select name="adblue_details[${idx}][adblue_company_id]" class="form-select form-select-sm adblue-company-select">
                            <option value="">Select Company</option>
                            @foreach($adblueCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Qty (L)</label>
                        <input type="number" step="0.01" name="adblue_details[${idx}][quantity]" class="form-control form-control-sm adblue-quantity" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Rate (₹)</label>
                        <input type="number" step="0.01" name="adblue_details[${idx}][rate]" class="form-control form-control-sm adblue-rate" value="0">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label small">Amount (₹)</label>
                        <input type="number" step="0.01" name="adblue_details[${idx}][amount]" class="form-control form-control-sm adblue-amount" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">KM</label>
                        <input type="number" step="0.01" name="adblue_details[${idx}][km]" class="form-control form-control-sm adblue-km" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Payment Type</label>
                        <select name="adblue_details[${idx}][payment_type]" class="form-select form-select-sm">
                            <option value="">Select</option>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                    </div>
                </div>
            </div>`;
            $('#adblueContainer').append(html);
        });

        $('#submitOtherImport').click(function() {
            const file = $('#modalOtherFile')[0].files[0];
            if (!file) { Swal.fire({ icon: 'warning', title: 'No File', text: 'Please select a file.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); return; }
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            $('#submitOtherImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing...');
            $.ajax({
                url: '{{ route("admin.transport.trips.other-amount-detail.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success && res.data.length) {
                        const baseIdx = getIndex('#otherContainer');
                        $.each(res.data, function(i, row) {
                            const idx = baseIdx + i;
                            const html = `<div class="detail-entry border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Title</label>
                                        <input type="text" name="other_details[${idx}][title]" class="form-control form-control-sm" placeholder="Title" value="${row.title || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Amount (₹)</label>
                                        <input type="number" step="0.01" name="other_details[${idx}][amount]" class="form-control form-control-sm other-amount" value="${row.amount || 0}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Date</label>
                                        <input type="date" max="9999-12-31" name="other_details[${idx}][date]" class="form-control form-control-sm" value="${row.date || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Remark</label>
                                        <input type="text" name="other_details[${idx}][remark]" class="form-control form-control-sm" placeholder="Remark" value="${row.remark || ''}">
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                                    </div>
                                </div>
                            </div>`;
                            $('#otherContainer').append(html);
                        });
                        recalcTotals();
                        $('#importOtherModal').modal('hide');
                    }
                    $('#modalOtherFile').val('');
                    $('#submitOtherImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Unknown error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    $('#modalOtherFile').val('');
                    $('#submitOtherImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                }
            });
        });

        $('#submitAdvanceImport').click(function() {
            const file = $('#modalAdvanceFile')[0].files[0];
            if (!file) { Swal.fire({ icon: 'warning', title: 'No File', text: 'Please select a file.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); return; }
            const companyId = $('#modalAdvanceCompany').val();
            const pumpId = $('#modalAdvancePump').val();
            const formData = new FormData();
            formData.append('file', file);
            formData.append('fuel_company_id', companyId);
            formData.append('fuel_pump_id', pumpId);
            formData.append('_token', '{{ csrf_token() }}');
            $('#submitAdvanceImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing...');
            $.ajax({
                url: '{{ route("admin.transport.trips.advance-detail.import") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success && res.data.length) {
                        const baseIdx = getIndex('#advanceContainer');
                        $.each(res.data, function(i, row) {
                            const idx = baseIdx + i;
                            const html = `<div class="advance-entry border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Date</label>
                                        <input type="date" max="9999-12-31" name="advance_details[${idx}][date]" class="form-control form-control-sm" value="${row.date || ''}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Fuel Company</label>
                                        <select name="advance_details[${idx}][fuel_company_id]" class="form-select form-select-sm advance-company-select">
                                            <option value="">Select Company</option>
                                            @foreach($fuelCompanies as $company)
                                            <option value="{{ $company->id }}" ${row.fuel_company_id == {{ $company->id }} ? 'selected' : ''}>{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Fuel Pump</label>
                                        <select name="advance_details[${idx}][fuel_pump_id]" class="form-select form-select-sm advance-pump-select">
                                            <option value="">Select Pump</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Advance Amount (₹)</label>
                                        <input type="number" step="0.01" name="advance_details[${idx}][advance_amount]" class="form-control form-control-sm advance-amount" value="${row.advance_amount || 0}">
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label small">Payment Type</label>
                                        <select name="advance_details[${idx}][payment_type]" class="form-select form-select-sm">
                                            <option value="">Select</option>
                                            <option value="credit" ${row.payment_type && row.payment_type.toLowerCase() === 'credit' ? 'selected' : ''}>Credit</option>
                                            <option value="debit" ${row.payment_type && row.payment_type.toLowerCase() === 'debit' ? 'selected' : ''}>Debit</option>
                                            <option value="cash" ${row.payment_type && row.payment_type.toLowerCase() === 'cash' ? 'selected' : ''}>Cash</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Remark</label>
                                        <input type="text" name="advance_details[${idx}][remark]" class="form-control form-control-sm" placeholder="Remark" maxlength="500" value="${row.remark || ''}">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                                    </div>
                                </div>
                            </div>`;
                            $('#advanceContainer').append(html);
                        });
                        const newCnt = res.data.length;
                        $('#advanceContainer').find('.advance-company-select').slice(-newCnt).each(function(i) {
                            if ($(this).val()) {
                                loadPumps($(this), res.data[i]?.fuel_pump_id);
                            }
                        });
                        recalcTotals();
                        $('#importAdvanceModal').modal('hide');
                    }
                    $('#modalAdvanceFile').val('');
                    $('#submitAdvanceImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Unknown error', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    $('#modalAdvanceFile').val('');
                    $('#submitAdvanceImport').prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Import');
                }
            });
        });

        $('#addOtherRow').click(function() {
            const idx = getIndex('#otherContainer');
            const html = `<div class="detail-entry border rounded p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Title</label>
                        <input type="text" name="other_details[${idx}][title]" class="form-control form-control-sm" placeholder="Title">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Amount (₹)</label>
                        <input type="number" step="0.01" name="other_details[${idx}][amount]" class="form-control form-control-sm other-amount" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Date</label>
                        <input type="date" max="9999-12-31" name="other_details[${idx}][date]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Remark</label>
                        <input type="text" name="other_details[${idx}][remark]" class="form-control form-control-sm" placeholder="Remark">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                    </div>
                </div>
            </div>`;
            $('#otherContainer').append(html);
        });

        $('#addAdvanceRow').click(function() {
            const idx = getIndex('#advanceContainer');
            const html = `<div class="advance-entry border rounded p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Date</label>
                        <input type="date" max="9999-12-31" name="advance_details[${idx}][date]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fuel Company</label>
                        <select name="advance_details[${idx}][fuel_company_id]" class="form-select form-select-sm advance-company-select">
                            <option value="">Select Company</option>
                            @foreach($fuelCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fuel Pump</label>
                        <select name="advance_details[${idx}][fuel_pump_id]" class="form-select form-select-sm advance-pump-select">
                            <option value="">Select Pump</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Advance Amount (₹)</label>
                        <input type="number" step="0.01" name="advance_details[${idx}][advance_amount]" class="form-control form-control-sm advance-amount" value="0">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label small">Payment Type</label>
                        <select name="advance_details[${idx}][payment_type]" class="form-select form-select-sm">
                            <option value="">Select</option>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Remark</label>
                        <input type="text" name="advance_details[${idx}][remark]" class="form-control form-control-sm" placeholder="Remark" maxlength="500">
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bx bx-trash me-1"></i> Remove</button>
                    </div>
                </div>
            </div>`;
            $('#advanceContainer').append(html);
        });

        $(document).on('click', '.remove-row', function() {
            const entry = $(this).closest('.fuel-entry');
            if (entry.length) {
                if ($('#fuelContainer').children('.fuel-entry').length > 1) {
                    entry.remove();
                    recalcTotals();
                }
                return;
            }
            const advanceEntry = $(this).closest('.advance-entry');
            if (advanceEntry.length) {
                if ($('#advanceContainer').children('.advance-entry').length > 1) {
                    advanceEntry.remove();
                    recalcTotals();
                }
                return;
            }
            const detailEntry = $(this).closest('.detail-entry');
            if (detailEntry.length) {
                const container = detailEntry.parent();
                if (container.children('.detail-entry').length > 1) {
                    detailEntry.remove();
                    recalcTotals();
                }
                return;
            }
            const row = $(this).closest('tr');
            const container = row.closest('tbody');
            if (container.children('tr').length > 1) {
                row.remove();
                recalcTotals();
            }
        });

        $(document).on('input change', '.fast-tag-amount, .fuel-amount, .adblue-amount, .other-amount, .advance-amount', recalcTotals);
        
        $(document).on('input', '.fast-tag-amount', function() {
            const entry = $(this).closest('.detail-entry');
            const amount = parseFloat($(this).val()) || 0;
            entry.find('.fast-tag-oneway').val((amount / 2).toFixed(2));
            entry.find('.fast-tag-return').val((amount / 2).toFixed(2));
        });

        $(document).on('input', '.fast-tag-oneway, .fast-tag-return', function() {
            const entry = $(this).closest('.detail-entry');
            const oneway = parseFloat(entry.find('.fast-tag-oneway').val()) || 0;
            const ret = parseFloat(entry.find('.fast-tag-return').val()) || 0;
            const amountField = entry.find('.fast-tag-amount');
            amountField.val((oneway + ret).toFixed(2)).trigger('change');
        });
    });
</script>
@endsection
