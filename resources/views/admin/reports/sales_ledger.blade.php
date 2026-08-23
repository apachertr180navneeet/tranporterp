@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Sales Ledger</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Reports</a></li>
                    <li class="breadcrumb-item active">Sales Ledger</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.reports.sales-ledger.export-excel', request()->all()) }}" class="btn btn-success btn-sm me-1">
                <i class="bx bx-file me-1"></i> Export Excel
            </a>
            @if(auth()->user()->can('edit sales ledger') || auth()->user()->isSuperAdmin())
            <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#receiveAmountModal">
                <i class="bx bx-rupee me-1"></i> Receive Amount
            </button>
            @endif
            <a href="{{ route('admin.reports.sales-ledger.history') }}" class="btn btn-outline-secondary btn-sm me-1">
                <i class="bx bx-history me-1"></i> Receiving History
            </a>
            <a href="{{ route('admin.reports.tds-report') }}" class="btn btn-outline-info btn-sm">
                <i class="bx bx-receipt me-1"></i> TDS Report
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0">Filter Records</h5>
        </div>
        <div class="card-body mt-3">
            <form method="GET" action="{{ route('admin.reports.sales-ledger') }}" class="row g-3">
                @if(auth()->user()->isSuperAdmin())
                <div class="col-md-3">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="all">All Companies</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Consigner</label>
                    <select name="consignor_id" class="form-select">
                        <option value="">All Consigners</option>
                        @foreach($consignors as $consignor)
                        <option value="{{ $consignor->id }}" {{ request('consignor_id') == $consignor->id ? 'selected' : '' }}>{{ $consignor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bill Number</label>
                    <input type="text" name="bill_number" class="form-control" value="{{ request('bill_number') }}" placeholder="Search by Bill No">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bill To</label>
                    <input type="text" name="bill_to" class="form-control" value="{{ request('bill_to') }}" placeholder="Search by Bill To">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Receiving Amount</label>
                    <select name="receiving_amount_status" class="form-select">
                        <option value="">All</option>
                        <option value="paid" {{ request('receiving_amount_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('receiving_amount_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Receiving GST</label>
                    <select name="receiving_gst_status" class="form-select">
                        <option value="">All</option>
                        <option value="paid" {{ request('receiving_gst_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('receiving_gst_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary w-50"><i class="bx bx-search me-1"></i> Search</button>
                        <a href="{{ route('admin.reports.sales-ledger') }}" class="btn btn-outline-secondary w-50">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>S.No</th>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Bill No</th>
                        <th>Bill To</th>
                        <th class="text-end">Total Amt<br><small class="text-muted">(w/o GST)</small></th>
                        <th class="text-end">GST</th>
                        <th class="text-end">TDS</th>
                        <th class="text-end">Deduction</th>
                        <th class="text-end">Net Payable</th>
                        <th class="text-end">Recv. Amt</th>
                        <th class="text-end">Recv. GST</th>
                        <th class="text-end">Total Recv.</th>
                        <th class="text-end">Outstanding</th>
                        <th>Status</th>
                        @if(auth()->user()->can('edit sales ledger') || auth()->user()->isSuperAdmin())
                        <th class="text-center">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($invoices as $index => $invoice)
                        @php
                            $amountWithoutGst = $invoice->total_freight + $invoice->total_other;
                            $netPayable = $invoice->net_payable_amount;
                            $totalReceived = $invoice->total_received_amount;
                            $outstanding = $invoice->outstanding_amount;
                        @endphp
                        <tr>
                            <td>{{ $invoices->firstItem() + $index }}</td>
                            <td>{{ $invoice->invoice_date?->format('d-m-Y') }}</td>
                            <td>{{ $invoice->company?->name ?? 'N/A' }}</td>
                            <td>{{ $invoice->branch?->name ?? 'N/A' }}</td>
                            <td><strong class="text-primary">{{ !empty($invoice->bill_number) ? $invoice->bill_number : ($invoice->invoice_no ?? ('INV-' . $invoice->id)) }}</strong></td>
                            <td>{{ $invoice->consignor_name }}</td>
                            <td class="text-end">₹ {{ number_format($amountWithoutGst, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($invoice->total_gst, 2) }}</td>
                            <td class="text-end text-danger">₹ {{ number_format($invoice->tds, 2) }}</td>
                            <td class="text-end text-danger">₹ {{ number_format($invoice->deduction, 2) }}</td>
                            <td class="text-end fw-bold">₹ {{ number_format($netPayable, 2) }}</td>
                            <td class="text-end text-success">₹ {{ number_format($invoice->receiving_amount, 2) }}</td>
                            <td class="text-end text-success">₹ {{ number_format($invoice->receiving_gst, 2) }}</td>
                            <td class="text-end fw-bold text-success">₹ {{ number_format($totalReceived, 2) }}</td>
                            <td class="text-end fw-bold {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">₹ {{ number_format($outstanding, 2) }}</td>
                            <td>
                                @if($invoice->status == 'paid')
                                    <span class="badge bg-label-success">Paid</span>
                                @elseif($invoice->status == 'pending')
                                    <span class="badge bg-label-warning">Pending</span>
                                @else
                                    <span class="badge bg-label-danger">{{ ucfirst($invoice->status) }}</span>
                                @endif
                            </td>
                            @if(auth()->user()->can('edit sales ledger') || auth()->user()->isSuperAdmin())
                            <td class="text-center">
                                @if($invoice->billReceivings->isNotEmpty())
                                    @if($invoice->billReceivings->count() === 1)
                                        @php $singleRec = $invoice->billReceivings->first(); @endphp
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-receiving" data-id="{{ $singleRec->id }}" title="Edit Receive Amount">
                                                <i class="bx bx-edit me-1"></i> Edit Recv
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm btn-add-receiving" data-invoice-id="{{ $invoice->id }}" title="Add Another Receiving">
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bx bx-edit me-1"></i> Edit ({{ $invoice->billReceivings->count() }})
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($invoice->billReceivings as $rec)
                                                    <li>
                                                        <a class="dropdown-item btn-edit-receiving" href="javascript:void(0);" data-id="{{ $rec->id }}">
                                                            <i class="bx bx-receipt me-1 text-primary"></i> {{ $rec->date?->format('d-m-Y') }}: ₹{{ number_format($rec->receiving_amount + $rec->receiving_gst, 2) }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <button type="button" class="btn btn-outline-success btn-sm btn-add-receiving" data-invoice-id="{{ $invoice->id }}" title="Add Another Receiving">
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <button type="button" class="btn btn-outline-success btn-sm btn-add-receiving" data-invoice-id="{{ $invoice->id }}" title="Receive Amount">
                                        <i class="bx bx-rupee me-1"></i> Receive
                                    </button>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (auth()->user()->can('edit sales ledger') || auth()->user()->isSuperAdmin()) ? 17 : 16 }}" class="text-center py-4 text-muted">No records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $invoices->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

@if(auth()->user()->can('edit sales ledger') || auth()->user()->isSuperAdmin())
<!-- Receive Amount Modal -->
<div class="modal fade" id="receiveAmountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('admin.reports.sales-ledger.receive') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bill Number <span class="text-danger">*</span></label>
                            <select name="invoice_id" id="invoice_id" class="form-select" required>
                                <option value="">Select Bill Number</option>
                                @foreach($allBills as $bill)
                                    @php
                                        $displayBillNo = !empty($bill->bill_number) ? $bill->bill_number : ($bill->invoice_no ?? ('INV-' . $bill->id));
                                    @endphp
                                    <option value="{{ $bill->id }}">{{ $displayBillNo }} - {{ $bill->consignor_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Auto Filled Fields -->
                        <div class="col-md-12">
                            <label class="form-label">Bill To</label>
                            <input type="text" id="auto_bill_to" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <input type="text" id="auto_company" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <input type="text" id="auto_branch" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bill Amount (₹)</label>
                            <input type="text" id="auto_bill_amount" class="form-control bg-light fw-bold" readonly value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Outstanding (₹)</label>
                            <input type="text" id="auto_outstanding" class="form-control bg-light fw-bold" readonly value="0.00">
                        </div>
                        
                        <!-- Amount Inputs -->
                        <div class="col-md-6">
                            <label class="form-label">Receiving Amount (₹)</label>
                            <input type="number" step="0.01" name="receiving_amount" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receiving GST (₹)</label>
                            <input type="number" step="0.01" name="receiving_gst" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TDS (%)</label>
                            <input type="number" step="0.01" id="tds_percentage" class="form-control" value="1.00" placeholder="1.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TDS Amount (₹)</label>
                            <input type="number" step="0.01" name="tds" id="auto_tds" class="form-control" value="0.00" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deduction (₹)</label>
                            <input type="number" step="0.01" name="deduction" id="modal_deduction" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deduction Reason</label>
                            <input type="text" name="deduction_reason" class="form-control" placeholder="Enter reason if deduction applied">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Entry</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Receiving Modal -->
<div class="modal fade" id="editReceivingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="editReceivingForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-edit me-1 text-primary"></i> Edit Receive Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="edit_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bill Number</label>
                            <input type="text" id="edit_bill_number" class="form-control bg-light" readonly>
                        </div>
                        
                        <!-- Auto Filled Fields -->
                        <div class="col-md-12">
                            <label class="form-label">Bill To</label>
                            <input type="text" id="edit_bill_to" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <input type="text" id="edit_company" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <input type="text" id="edit_branch" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Net Payable Amount (₹)</label>
                            <input type="text" id="edit_net_payable" class="form-control bg-light fw-bold" readonly value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Outstanding (₹)</label>
                            <input type="text" id="edit_outstanding" class="form-control bg-light fw-bold" readonly value="0.00">
                        </div>
                        
                        <!-- Amount Inputs -->
                        <div class="col-md-6">
                            <label class="form-label">Receiving Amount (₹)</label>
                            <input type="number" step="0.01" name="receiving_amount" id="edit_receiving_amount" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receiving GST (₹)</label>
                            <input type="number" step="0.01" name="receiving_gst" id="edit_receiving_gst" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TDS (%)</label>
                            <input type="number" step="0.01" id="edit_tds_percentage" class="form-control" value="1.00" placeholder="1.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TDS Amount (₹)</label>
                            <input type="number" step="0.01" name="tds" id="edit_auto_tds" class="form-control" value="0.00" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deduction (₹)</label>
                            <input type="number" step="0.01" name="deduction" id="edit_modal_deduction" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deduction Reason</label>
                            <input type="text" name="deduction_reason" id="edit_deduction_reason" class="form-control" placeholder="Enter reason if deduction applied">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Update Entry</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@if(auth()->user()->can('edit sales ledger') || auth()->user()->isSuperAdmin())
@section('script')
<script>
    $(document).ready(function() {
        var currentGrossBaseAmount = 0;
        var editGrossBaseAmount = 0;

        function calculateTdsFromPercentage() {
            var deduction = parseFloat($('#modal_deduction').val()) || 0;
            var baseAmount = currentGrossBaseAmount - deduction;
            if (baseAmount < 0) baseAmount = 0;

            var percentage = parseFloat($('#tds_percentage').val()) || 0;
            var tdsAmount = (baseAmount * percentage) / 100;
            $('#auto_tds').val(tdsAmount.toFixed(2));
        }

        function calculatePercentageFromTds() {
            var deduction = parseFloat($('#modal_deduction').val()) || 0;
            var baseAmount = currentGrossBaseAmount - deduction;
            var tdsAmount = parseFloat($('#auto_tds').val()) || 0;

            if (baseAmount > 0) {
                var percentage = (tdsAmount / baseAmount) * 100;
                $('#tds_percentage').val(percentage.toFixed(2));
            }
        }

        function fetchInvoiceDetails(invoiceId) {
            if(invoiceId) {
                $.ajax({
                    url: "{{ url('admin/reports/sales-ledger/invoice-details') }}/" + invoiceId,
                    type: "GET",
                    success: function(response) {
                        if(response.success) {
                            $('#auto_bill_to').val(response.data.bill_to);
                            $('#auto_company').val(response.data.company_name);
                            $('#auto_branch').val(response.data.branch_name);
                            $('#auto_bill_amount').val('₹ ' + parseFloat(response.data.net_payable_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            $('#auto_outstanding').val('₹ ' + parseFloat(response.data.outstanding_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                            currentGrossBaseAmount = parseFloat(response.data.gross_base_amount) || 0;
                            $('#tds_percentage').val('1.00');
                            calculateTdsFromPercentage();
                        } else {
                            alert('Failed to fetch invoice details.');
                        }
                    },
                    error: function() {
                        alert('Error fetching invoice details.');
                    }
                });
            } else {
                currentGrossBaseAmount = 0;
                $('#auto_bill_to').val('');
                $('#auto_company').val('');
                $('#auto_branch').val('');
                $('#auto_bill_amount').val('₹ 0.00');
                $('#auto_outstanding').val('₹ 0.00');
                $('#tds_percentage').val('1.00');
                $('#auto_tds').val('0.00');
            }
        }

        $(document).on('change', '#invoice_id', function() {
            fetchInvoiceDetails($(this).val());
        });

        $(document).on('input change', '#tds_percentage', function() {
            calculateTdsFromPercentage();
        });

        $(document).on('input change', '#auto_tds', function() {
            calculatePercentageFromTds();
        });

        $(document).on('input change', '#modal_deduction', function() {
            calculateTdsFromPercentage();
        });

        $('#receiveAmountModal').on('hidden.bs.modal', function () {
            currentGrossBaseAmount = 0;
            $('#invoice_id').val('');
            $('#auto_bill_to').val('');
            $('#auto_company').val('');
            $('#auto_branch').val('');
            $('#auto_bill_amount').val('₹ 0.00');
            $('#auto_outstanding').val('₹ 0.00');
            $('#tds_percentage').val('1.00');
            $('#auto_tds').val('0.00');
            $('#modal_deduction').val('0.00');
        });

        // Edit Receiving Handler
        function calculateEditTdsFromPercentage() {
            var deduction = parseFloat($('#edit_modal_deduction').val()) || 0;
            var baseAmount = editGrossBaseAmount - deduction;
            if (baseAmount < 0) baseAmount = 0;

            var percentage = parseFloat($('#edit_tds_percentage').val()) || 0;
            var tdsAmount = (baseAmount * percentage) / 100;
            $('#edit_auto_tds').val(tdsAmount.toFixed(2));
        }

        function calculateEditPercentageFromTds() {
            var deduction = parseFloat($('#edit_modal_deduction').val()) || 0;
            var baseAmount = editGrossBaseAmount - deduction;
            var tdsAmount = parseFloat($('#edit_auto_tds').val()) || 0;

            if (baseAmount > 0) {
                var percentage = (tdsAmount / baseAmount) * 100;
                $('#edit_tds_percentage').val(percentage.toFixed(2));
            }
        }

        $(document).on('click', '.btn-edit-receiving', function() {
            var receivingId = $(this).data('id');
            if (!receivingId) return;

            $.ajax({
                url: "{{ url('admin/reports/sales-ledger/receiving') }}/" + receivingId,
                type: "GET",
                success: function(response) {
                    if (response.success) {
                        var d = response.data;
                        $('#editReceivingForm').attr('action', "{{ url('admin/reports/sales-ledger/receiving') }}/" + receivingId);
                        $('#edit_date').val(d.date);
                        $('#edit_bill_number').val(d.bill_number);
                        $('#edit_bill_to').val(d.bill_to);
                        $('#edit_company').val(d.company_name);
                        $('#edit_branch').val(d.branch_name);
                        $('#edit_net_payable').val('₹ ' + parseFloat(d.net_payable_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#edit_outstanding').val('₹ ' + parseFloat(d.outstanding_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        
                        $('#edit_receiving_amount').val(parseFloat(d.receiving_amount).toFixed(2));
                        $('#edit_receiving_gst').val(parseFloat(d.receiving_gst).toFixed(2));
                        $('#edit_auto_tds').val(parseFloat(d.tds).toFixed(2));
                        $('#edit_modal_deduction').val(parseFloat(d.deduction).toFixed(2));
                        $('#edit_deduction_reason').val(d.deduction_reason);

                        editGrossBaseAmount = parseFloat(d.gross_base_amount) || 0;
                        calculateEditPercentageFromTds();

                        $('#editReceivingModal').modal('show');
                    } else {
                        alert(response.message || 'Failed to fetch receiving details.');
                    }
                },
                error: function() {
                    alert('Error fetching receiving details.');
                }
            });
        });

        $(document).on('click', '.btn-add-receiving', function() {
            var invoiceId = $(this).data('invoice-id');
            $('#receiveAmountModal').modal('show');
            if (invoiceId) {
                $('#invoice_id').val(invoiceId).trigger('change');
            }
        });

        $(document).on('input change', '#edit_tds_percentage', function() {
            calculateEditTdsFromPercentage();
        });

        $(document).on('input change', '#edit_auto_tds', function() {
            calculateEditPercentageFromTds();
        });

        $(document).on('input change', '#edit_modal_deduction', function() {
            calculateEditTdsFromPercentage();
        });
    });
</script>
@endsection
@endif
