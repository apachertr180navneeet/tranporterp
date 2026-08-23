@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Generate Bill</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.transport.billing') }}">Generate Bill</a></li>
                    <li class="breadcrumb-item active">Create Bill</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.transport.billing') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Selected LRs Table -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Selected LRs ({{ $bulties->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>LR No</th>
                                    <th>Date</th>
                                    <th>Consignor</th>
                                    <th>Consignee</th>
                                    <th>From → To</th>
                                    <th class="text-end">Freight (₹)</th>
                                    <th class="text-end">GST (₹)</th>
                                    <th class="text-end">Other (₹)</th>
                                    <th class="text-end">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bulties as $bulty)
                                <tr data-bulty-id="{{ $bulty->id }}" data-original-gst="{{ $bulty->gst_amount }}" data-original-total="{{ $bulty->total_amount }}" data-freight="{{ $bulty->freight_charges }}" data-other="{{ $bulty->other_charges }}">
                                    <td><strong>{{ $bulty->lr_no }}</strong></td>
                                    <td>{{ $bulty->lr_date->format('d M Y') }}</td>
                                    <td>{{ $bulty->consignor->name ?? '-' }}</td>
                                    <td>{{ $bulty->consignee->name ?? '-' }}</td>
                                    <td>
                                        {{ $bulty->originCity->name ?? '-' }}
                                        <i class="bx bx-chevron-right bx-xs"></i>
                                        {{ $bulty->destinationCity->name ?? '-' }}
                                    </td>
                                    <td class="text-end col-freight">{{ number_format(floatval($bulty->total_amount), 2) }}</td>
                                    <td class="text-end col-gst">{{ number_format($bulty->gst_amount, 2) }}</td>
                                    <td class="text-end col-other">{{ number_format($bulty->other_charges, 2) }}</td>
                                    <td class="text-end col-total"><strong>{{ number_format(floatval($bulty->total_amount) + floatval($bulty->gst_amount) + floatval($bulty->other_charges), 2) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Total:</th>
                                    <th class="text-end" id="sum-selected-freight">{{ number_format($totals['freight'], 2) }}</th>
                                    <th class="text-end" id="sum-selected-gst">{{ number_format($totals['gst'], 2) }}</th>
                                    <th class="text-end" id="sum-selected-other">{{ number_format($totals['other'], 2) }}</th>
                                    <th class="text-end" id="sum-selected-total">{{ number_format($totals['grand'], 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Live Invoice Print Preview Card -->
            <div class="card mb-4 d-none" id="invoice-preview-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-printer me-1"></i> Live Invoice Print Preview</h6>
                    <span class="badge bg-label-info">Dynamic Layout Format</span>
                </div>
                <div class="card-body p-4" style="background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.4;">
                    <!-- SHEET 1: FREIGHT BILL -->
                    <div id="freight-preview-sheet" style="border: 2px solid #000; padding: 0;">
                        <!-- Top header details -->
                        <div class="d-flex justify-content-between align-items-center position-relative" style="border-bottom: 2px solid #000; padding: 4px 8px; font-weight: bold; font-size: 10px;">
                            <div>GSTIN: <span id="preview-company-gst"></span></div>
                            <div class="position-absolute top-50 start-50 translate-middle" style="font-size: 12px;">TAX INVOICE</div>
                            <div>PAN: <span id="preview-company-pan"></span></div>
                        </div>
                        
                        <!-- Main Header -->
                        <div class="text-center" style="border-bottom: 2px solid #000; padding: 8px;">
                            <h2 class="m-0 fw-bold" style="font-size: 20px; letter-spacing: 1px; text-transform: uppercase;" id="preview-company-name"></h2>
                            <div style="font-size: 10px; margin-top: 4px;" id="preview-company-address"></div>
                        </div>

                        <!-- Client details / Supply meta -->
                        <div class="row g-0" style="border-bottom: 2px solid #000;">
                            <div class="col-8 p-2" style="border-right: 2px solid #000; font-size: 10px;">
                                <div id="preview-party-address"></div>
                            </div>
                            <div class="col-4 p-2" style="font-size: 10px;">
                                <div><strong>HSN/SAC CODE:</strong> <span id="preview-company-hsn"></span></div>
                                <div class="mt-2"><strong>Date:</strong> - <span id="preview-bill-date">05-06-2026</span></div>
                                <div><strong>Bill No:</strong> - <span id="preview-bill-no">BW-N-28</span></div>
                                <div class="mt-1 d-none" id="preview-state-vendor-code-container"><strong>State Vendor Code:</strong> - <span id="preview-state-vendor-code"></span></div>
                                <div id="preview-vendor-code-container" class="d-none"><strong>Vendor Code:</strong> - <span id="preview-vendor-code"></span></div>
                                <div id="preview-vendor-name-container" class="d-none"><strong>Vendor Name:</strong> - <span id="preview-vendor-name"></span></div>
                                <div id="preview-epod-status-container" class="d-none"><strong>EPOD Status:</strong> - <span id="preview-epod-status">N</span></div>
                            </div>
                        </div>

                        <!-- RCM statement row -->
                        <div style="border-bottom: 2px solid #000; padding: 4px 8px; font-weight: bold; font-size: 10px; text-transform: uppercase;">
                            WHETHER TAX IS PAYABLE UNDER REVERSE CHARGE MECHANISM : - <span class="preview-rcm-text">YES</span>
                        </div>

                        <!-- Subtitle tag -->
                        <div id="preview-bill-type-title" class="text-center fw-bold" style="background: #f2f2f2; border-bottom: 2px solid #000; padding: 4px; font-size: 11px;">
                            Transportation Freight Bill
                        </div>

                        <!-- Main Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="preview-table" style="border-collapse: collapse; border: none; color: #000; width: 100%;">
                                <thead style="background: #f2f2f2;">
                                    <tr id="preview-thead-row">
                                        <!-- Injected Headers -->
                                    </tr>
                                </thead>
                                <tbody id="preview-tbody-rows">
                                    <!-- Injected Rows -->
                                </tbody>
                            </table>
                        </div>

                        <!-- GST Table Split -->
                        <div class="row g-0" style="border-top: 2px solid #000;">
                            <div class="col-7" style="border-right: 2px solid #000;">
                                <!-- Left side spacing -->
                            </div>
                            <div class="col-5">
                                <table class="w-100" style="color: #000; font-size: 10px; border-collapse: collapse;" id="preview-gst-split-table">
                                    <!-- Dynamic GST Rows -->
                                </table>
                            </div>
                        </div>

                        <!-- Amount in Words -->
                        <div class="p-2 fw-bold" style="border-top: 2px solid #000; border-bottom: 2px solid #000; font-size: 10px;">
                            AMOUNT IN WORD: <span id="preview-amount-in-words" style="text-transform: uppercase;">Forty-Seven Thousand Eight Hundred Eighty Rupees and Zero Paise Only.</span>
                        </div>

                        <!-- Declaration & Bank info / Signature -->
                        <div class="row g-0">
                            <div class="col-7 p-2" style="border-right: 2px solid #000; font-size: 9.5px;">
                                <div class="fw-bold preview-declaration-text">Declaration : {{ $defaultCompanyDeclaration ?? 'GST payable by recipient under Reverse Charge (RCM) on GTA services.' }}</div>
                                <table class="table table-bordered table-sm mt-2 mb-0" style="color: #000; font-size: 9.5px; border-collapse: collapse; border: 1px solid #000;">
                                    <tr>
                                        <td class="fw-bold p-1" style="width: 30%; border: 1px solid #000;">ACCOUNT NO.</td>
                                        <td class="p-1" style="border: 1px solid #000;" id="preview-bank-account"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold p-1" style="border: 1px solid #000;">IFC CODE</td>
                                        <td class="p-1" style="border: 1px solid #000;" id="preview-bank-ifsc"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold p-1" style="border: 1px solid #000;">HOLDER NAME</td>
                                        <td class="p-1" style="border: 1px solid #000;" id="preview-bank-holder"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-5 p-2 d-flex flex-column justify-content-between text-end" style="min-height: 110px;">
                                <div class="fw-bold" style="font-size: 10px;">For <span id="preview-footer-company-1"></span></div>
                                <div class="my-1 text-end preview-signature-container">
                                    <img src="{{ $defaultCompanySignatureUrl ?? '' }}" alt="Signature" class="preview-signature-img" style="max-height: 45px; max-width: 140px; object-fit: contain; {{ empty($defaultCompanySignatureUrl) ? 'display: none;' : '' }}">
                                    <div style="font-size: 8px; color: #333; font-weight: bold; line-height: 1.2;" class="preview-digitally-signed-by">
                                        Digitally signed by <span class="preview-company-owner-name">{{ strtoupper($defaultCompany->owner_name ?? '') }}</span>
                                    </div>
                                    <div style="font-size: 8px; color: #555; line-height: 1.2;">
                                        Date: {{ date('d-m-Y H:i:s') }}
                                    </div>
                                </div>
                                <div style="font-size: 10px;">
                                    <span style="border-top: 1px solid #000; padding-top: 3px; display: inline-block; width: 150px; text-align: center; font-weight: bold;">Authorized Signatory</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAGE BREAK DIVIDER -->
                    <div id="preview-page-break" class="d-none text-center my-4 text-muted" style="border-top: 2px dashed #888; padding-top: 8px; font-weight: bold;">
                        --- PAGE BREAK (GRN Details will print on next page) ---
                    </div>

                    <!-- SHEET 2: GRN DETAILS BILL -->
                    <div id="grn-preview-sheet" class="d-none" style="border: 2px solid #000; padding: 0;">


                        <!-- Subtitle tag -->
                        <div class="text-center fw-bold" style="background: #f2f2f2; border-bottom: 2px solid #000; padding: 4px; font-size: 11px;">
                            Transportation GRN Details
                        </div>

                        <!-- GRN Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="preview-grn-table" style="border-collapse: collapse; border: none; color: #000; width: 100%;">
                                <thead style="background: #f2f2f2;">
                                    <tr id="preview-grn-thead-row">
                                        <!-- Injected Headers -->
                                    </tr>
                                </thead>
                                <tbody id="preview-grn-tbody-rows">
                                    <!-- Injected Rows -->
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </div>

            <!-- NATHDWARA Print Preview Card -->
            <div class="card mb-4 d-none" id="nathdwara-preview-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-printer me-1"></i> Live Invoice Print Preview</h6>
                    <span class="badge bg-label-success">Nathdwara Format</span>
                </div>
                <div class="card-body p-4" style="background: #fff; color: #000; font-family: 'Times New Roman', Times, serif; font-size: 12px; line-height: 1.4;">
                    
                    <!-- NATHDWARA SHEET 1: SUMMARY -->
                    <div id="nathdwara-sheet-1" style="border: 2px solid #000; padding: 0; margin-bottom: 20px;">
                        <!-- Top header -->
                        <div class="d-flex justify-content-between p-2" style="border-bottom: 2px solid #000; font-weight: bold;">
                            <div>GSTN: <span id="nath-comp-gst"></span></div>
                            <div class="text-end">
                                <div>PAN: <span id="nath-comp-pan"></span></div>
                                {{--  <div id="nath-comp-owner-phone"></div>  --}}
                            </div>
                        </div>

                        <!-- Company Header -->
                        <div class="text-center p-2" style="border-bottom: 2px solid #000;">
                            <h2 class="m-0 fw-bold" style="font-size: 22px; letter-spacing: 1px; text-transform: uppercase;" id="nath-comp-name"></h2>
                        </div>
                        
                        <!-- Company Address -->
                        <div class="text-center p-1 fw-bold" style="border-bottom: 2px solid #000; font-size: 11px;" id="nath-comp-address"></div>

                        <!-- Client Details -->
                        <div class="row g-0" style="border-bottom: 2px solid #000;">
                            <div class="col-8 p-2" style="border-right: 2px solid #000; font-size: 11px; text-transform: uppercase;">
                                <div id="nath-party-address">UNIT - Birla White Cement<br>MANDIYANA, NATHDWARA</div>
                            </div>
                            <div class="col-4 p-2" style="font-size: 11px;">
                                <div><strong>HSN/SAC CODE:</strong> <span id="nath-party-hsn">996511</span></div>
                                <div class="mt-2"><strong>Date:</strong> - <span id="nath-bill-date"></span></div>
                                <div><strong>Bill No:</strong> - <span id="nath-bill-no"></span></div>
                                <div class="mt-1 d-none" id="nath-state-vendor-code-container"><strong>State Vendor Code:</strong> - <span id="nath-state-vendor-code"></span></div>
                                <div id="nath-vendor-code-container" class="d-none"><strong>Vendor Code:</strong> - <span id="nath-vendor-code"></span></div>
                                <div id="nath-vendor-name-container" class="d-none"><strong>Vendor Name:</strong> - <span id="nath-vendor-name"></span></div>
                                <div id="nath-epod-status-container" class="d-none"><strong>EPOD Status:</strong> - <span id="nath-epod-status">N</span></div>
                            </div>
                        </div>
                        <div class="p-1 fw-bold" style="border-bottom: 2px solid #000; text-transform: uppercase;">WHETHER TAX IS PAYABLE UNDER REVERSE CHARGE MECHANISM : - <span class="preview-rcm-text">YES</span></div>

                        <div id="nath-bill-type-title" class="text-center p-1 fw-bold" style="border-bottom: 2px solid #000;">Transportation Freight Bill</div>

                        <!-- Table Sheet 1 -->
                        <table class="w-100 text-center" style="border-collapse: collapse; border: none; font-size: 11px;" id="nathdwara-table-1">
                            <thead>
                                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                                    <td style="border-right: 2px solid #000; padding: 4px;">SR NO</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">DESCRIPTION</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">BILL NO</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">MN NO</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">NUMBER OF DI</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">MT</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">BILLING AMOUNT</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">LESS SHORTAGE</td>
                                    <td style="border-right: 2px solid #000; padding: 4px;">LESS DAMAGE</td>
                                    <td style="padding: 4px;">NET TOTAL</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                                    <td style="border-right: 2px solid #000; padding: 8px;">1</td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-desc"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-bill-no"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-mn-no"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-di-count"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-mt"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-bill-amt"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-shortage"></td>
                                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-damage"></td>
                                    <td style="padding: 8px;" id="nath-s1-net-total"></td>
                                </tr>
                                <!-- GST Rows -->
                                <tr>
                                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 1px solid #000; padding: 4px; text-align: right; font-weight: bold;">C GST 2.5%</td>
                                    <td style="padding: 4px; border-bottom: 1px solid #000; font-weight: bold;" id="nath-s1-cgst"></td>
                                </tr>
                                <tr>
                                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: right; font-weight: bold;">S GST 2.5%</td>
                                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-sgst"></td>
                                </tr>
                                <tr>
                                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">TOTAL GST</td>
                                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-total-gst"></td>
                                </tr>
                                <tr>
                                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">GRAND TOTAL</td>
                                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-grand-total"></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Amount in Words -->
                        <div class="p-2 fw-bold" style="border-bottom: 2px solid #000;">
                            AMOUNT IN WORD: <span id="nath-amount-words"></span>
                        </div>

                        <!-- Footer Block -->
                        <div class="row g-0">
                            <div class="col-7 p-2" style="border-right: 2px solid #000;">
                                <div class="fw-bold text-center mt-4 mb-4 preview-declaration-text">Declaration : {{ $defaultCompanyDeclaration ?? 'GST payable by recipient under Reverse Charge (RCM) on GTA services.' }}</div>
                                <table class="w-100" style="border-collapse: collapse;">
                                    <tr>
                                        <td class="fw-bold p-1" style="border: 2px solid #000; width: 40%;">ACCOUNT NO.</td>
                                        <td class="p-1 text-center fw-bold" style="border: 2px solid #000;" id="nath-bank-account"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold p-1" style="border: 2px solid #000;">IFC CODE</td>
                                        <td class="p-1 text-center fw-bold" style="border: 2px solid #000;" id="nath-bank-ifsc"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold p-1" style="border: 2px solid #000;">HOLDER NAME</td>
                                        <td class="p-1 text-center fw-bold" style="border: 2px solid #000;" id="nath-bank-holder"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-5 p-2 d-flex flex-column text-end position-relative">
                                <div class="fw-bold">For <span id="nath-footer-company"></span></div>
                                <div class="my-1 text-end preview-signature-container">
                                    <img src="{{ $defaultCompanySignatureUrl ?? '' }}" alt="Signature" class="preview-signature-img" style="max-height: 45px; max-width: 140px; object-fit: contain; {{ empty($defaultCompanySignatureUrl) ? 'display: none;' : '' }}">
                                    <div style="font-size: 8px; color: #333; font-weight: bold; line-height: 1.2;" class="preview-digitally-signed-by">
                                        Digitally signed by <span class="preview-company-owner-name">{{ strtoupper($defaultCompany->owner_name ?? '') }}</span>
                                    </div>
                                    <div style="font-size: 8px; color: #555; line-height: 1.2;">
                                        Date: {{ date('d-m-Y H:i:s') }}
                                    </div>
                                </div>
                                <div style="margin-top: auto;" class="fw-bold">Authorized Signatory</div>
                            </div>
                        </div>
                    </div>

                    <!-- NATHDWARA PAGE BREAK -->
                    <div id="nathdwara-page-break" class="text-center my-4 text-muted hide-on-print" style="border-top: 2px dashed #888; padding-top: 8px; font-weight: bold;">
                        --- PAGE BREAK ---
                    </div>

                    <!-- NATHDWARA SHEET 2: ANNEXURE -->
                    <div id="nathdwara-sheet-2" style="border: 2px solid #000; padding: 0; page-break-before: always; break-before: page;">
                        <div class="text-center p-2" style="border-bottom: 2px solid #000;">
                            <h3 class="m-0 fw-bold" id="nath-s2-comp-name" style="text-transform: uppercase;"></h3>
                            <div class="fw-bold" id="nath-s2-phone" style="font-size: 11px;"></div>
                        </div>
                        
                        <div class="text-end p-1 fw-bold" style="border-bottom: 2px solid #000;">
                            Service Tax Regn No : 
                        </div>

                        <div class="row g-0" style="border-bottom: 2px solid #000; font-weight: bold;">
                            <div class="col-3 p-1" style="border-right: 2px solid #000;">Bill No: <span id="nath-s2-bill-no"></span></div>
                            <div class="col-6 p-1 text-center" style="border-right: 2px solid #000;"><span id="nath-s2-party-address">ULTRATECH CEMENT LIMITED UNIT: BIRLA WHITE Rajashree Nagar Vill. Kharia Khangar-342606</span></div>
                            <div class="col-3 p-1">Dt: <span id="nath-s2-date"></span></div>
                        </div>

                        <div class="text-center fw-bold p-1" style="border-bottom: 2px solid #000;">Summary</div>

                        <table class="w-100" style="border-collapse: collapse; border-bottom: 2px solid #000;">
                            <tr style="border-bottom: 2px solid #000;">
                                <td class="p-1 fw-bold" style="border-right: 2px solid #000; width: 50%;">Charge Type</td>
                                <td class="p-1 fw-bold text-center" style="border-right: 2px solid #000; width: 25%;">QTY(MT)</td>
                                <td class="p-1 fw-bold text-center" style="width: 25%;">Amount</td>
                            </tr>
                            <tr>
                                <td class="p-1" style="border-right: 2px solid #000;">ROAD</td>
                                <td class="p-1 text-center" style="border-right: 2px solid #000;" id="nath-s2-summary-qty"></td>
                                <td class="p-1 text-center" id="nath-s2-summary-amt"></td>
                            </tr>
                        </table>

                        <!-- Detailed Table -->
                        <table class="w-100 text-center" style="border-collapse: collapse; font-size: 10px;" id="nathdwara-table-2">
                            <thead>
                                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                                    <td style="border-right: 1px solid #000; padding: 2px;">Truck No</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">From Place</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">To Place</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Consignment No</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Date</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Lane</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">QTY(MT)</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Rate</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Damage<br>Qty</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Damage<br>Rate</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Shortage<br>Qty</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Shortage<br>Rate</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">Amt.</td>
                                    <td style="border-right: 1px solid #000; padding: 2px;">S.Tax</td>
                                    <td style="padding: 2px;">S.B.Cess</td>
                                </tr>
                            </thead>
                            <tbody id="nath-s2-tbody">
                                <!-- Dynamic rows -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Gross Total:</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;" id="nath-s2-gross"></td>
                                    <td colspan="2" style="border-top: 2px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Less :Shortage</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;" id="nath-s2-less-shortage"></td>
                                    <td colspan="2" style="border-top: 2px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Less: Damage</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;" id="nath-s2-less-damage"></td>
                                    <td colspan="2" style="border-top: 2px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Net Total</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;" id="nath-s2-net-total"></td>
                                    <td colspan="2" style="border-top: 2px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Service Tax</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">0.00</td>
                                    <td colspan="2" style="border-top: 2px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Swachh Bharat cess</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">0.00</td>
                                    <td colspan="2" style="border-top: 2px solid #000;"></td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="text-end fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; padding: 2px;">Total Value :</td>
                                    <td class="text-center fw-bold" style="border-right: 1px solid #000; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 2px;" id="nath-s2-final-total"></td>
                                    <td colspan="2" style="border-top: 2px solid #000; border-bottom: 2px solid #000;"></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="p-2" style="font-size: 10px;">
                            <div>In words : <span id="nath-s2-amount-words"></span></div>
                            <div class="text-center mt-2 fw-bold">DECLARATION</div>
                            <div class="preview-declaration-text">Declaration : {{ $defaultCompanyDeclaration ?? 'GST payable by recipient under Reverse Charge (RCM) on GTA services.' }}</div>
                            
                            <div class="text-end mt-3 mb-1">
                                <div class="fw-bold" style="font-size: 12px;">For <span id="nath-s2-footer-company"></span></div>
                                <div class="my-1 text-end preview-signature-container">
                                    <img src="{{ $defaultCompanySignatureUrl ?? '' }}" alt="Signature" class="preview-signature-img" style="max-height: 45px; max-width: 140px; object-fit: contain; {{ empty($defaultCompanySignatureUrl) ? 'display: none;' : '' }}">
                                    <div style="font-size: 8px; color: #333; font-weight: bold; line-height: 1.2;" class="preview-digitally-signed-by">
                                        Digitally signed by <span class="preview-company-owner-name">{{ strtoupper($defaultCompany->owner_name ?? '') }}</span>
                                    </div>
                                    <div style="font-size: 8px; color: #555; line-height: 1.2;">
                                        Date: {{ date('d-m-Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div style="font-size: 10px; font-weight: bold;">Authorized Signatory</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GYPSUM SHEET 2: GRN DETAILS -->
                    <div id="gypsum-sheet-2" class="d-none" style="border: 2px solid #000; padding: 0; page-break-before: always; break-before: page;">
                        <div class="table-responsive">
                            <table class="w-100 text-center" style="border-collapse: collapse; font-size: 9px; border: none;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #000;">
                                        <td colspan="2" style="border-right: 2px solid #000;"></td>
                                        <td colspan="14" class="text-center p-2" style="border-right: 2px solid #000;">
                                            <h3 class="m-0 fw-bold" id="gypsum-s2-comp-name" style="text-transform: uppercase;">GRN DETAILS</h3>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr style="border-bottom: 2px solid #000; font-weight: bold; background-color: #ffff00 !important; -webkit-print-color-adjust: exact; color-adjust: exact;">
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Posting Date</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">PO No</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Mat Doc</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Gate Entry No</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Gate Out Date</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Supplier</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Supplier Name</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Vehicle No</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Challan No</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Challan Date</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">LR No</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Transporter</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Transporter Name</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">PO Item</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Material</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Material Name</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">Challan Qty</td>
                                        <td style="border-bottom: 1px solid #000; padding: 4px;">Final Wgt</td>
                                    </tr>
                                </thead>
                                <tbody id="gypsum-s2-tbody">
                                    <!-- Dynamic rows -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="16" class="text-center fw-bold" style="border-right: 1px solid #000; padding: 4px; font-size: 11px;">TOTAL</td>
                                        <td class="fw-bold" id="gypsum-s2-total-challan" style="border-right: 1px solid #000; padding: 4px; font-size: 11px;">0.000</td>
                                        <td class="fw-bold" id="gypsum-s2-total-final" style="padding: 4px; font-size: 11px;">0.000</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Bill Summary / Generation Panel -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary bg-gradient text-white d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-receipt fs-4"></i>
                        <h5 class="card-title mb-0 text-white fw-bold fs-6">Generate Invoice</h5>
                    </div>
                    <span class="badge bg-white text-primary rounded-pill px-3">{{ $bulties->count() }} LR{{ $bulties->count() > 1 ? 's' : '' }}</span>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('admin.transport.billing.generate') }}" id="generate-bill-form">
                        @csrf
                        <input type="hidden" name="ids" value="{{ $bulties->pluck('id')->join(',') }}">
                        <input type="hidden" name="invoice_type" value="freight">

                        <!-- Section: Invoice Header & Amount -->
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-uppercase text-muted small"><i class="bx bx-info-circle me-1"></i>Basic Info</span>
                                <span class="badge bg-label-success fw-bold">Freight Invoice</span>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Bill Number</label>
                                    <input type="text" name="bill_number" class="form-control form-control-sm fw-bold text-primary" id="bill-number-input" value="{{ $nextFreightInvoiceNo }}" placeholder="Enter bill number">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">No. of LRs</label>
                                    <input type="number" name="no_of_lrs" class="form-control form-control-sm text-center fw-bold" id="no-of-lrs-input" value="{{ $bulties->count() }}" placeholder="No of LRs">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Company Name</label>
                                @php
                                    $defaultCompany = $bulties->count() > 0 ? $bulties->first()->company : null;
                                    $defaultCompName = $defaultCompany ? $defaultCompany->name : '';
                                    $defaultCompanySignatureUrl = $defaultCompany ? $defaultCompany->digital_signature_url : null;
                                    $defaultCompanyDeclaration = $defaultCompany && !empty($defaultCompany->declaration)
                                        ? $defaultCompany->declaration
                                        : 'GST payable by recipient under Reverse Charge (RCM) on GTA services.';
                                @endphp
                                <input type="text" name="company_name" id="company-name-input" class="form-control form-control-sm" value="{{ $defaultCompName }}" placeholder="Enter Company Name">
                            </div>
                            <div>
                                <label class="form-label small fw-semibold mb-1 text-success">Total Bill Amount (₹)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-success text-white fw-bold" style="background-color: #28a745 !important; color: #ffffff !important;">₹</span>
                                    <input type="text" name="total_amount" class="form-control form-control-sm fw-bold text-success fs-6 bg-white" id="total-amount-input" value="{{ number_format($totals['grand'], 2) }}" placeholder="0.00" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Taxation & GST -->
                        <div class="border rounded p-3 mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2"><i class="bx bx-calculator me-1"></i>Taxation & GST</div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Tax Payable Under Reverse Charge (RCM)?</label>
                                <select name="rcm_payable" id="rcm-payable-select" class="form-select form-select-sm">
                                    <option value="1" selected>YES (Recipient pays GST under RCM)</option>
                                    <option value="0">NO (Tax payable under Forward Charge)</option>
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">GST Type</label>
                                    <select name="gst_type" id="gst-type-select" class="form-select form-select-sm">
                                        <option value="CGST_SGST" selected>CGST + SGST</option>
                                        <option value="IGST">IGST</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Total GST (₹)</label>
                                    <input type="number" step="any" name="total_gst" id="total-gst-input" class="form-control form-control-sm text-end" value="{{ number_format($totals['gst'], 2, '.', '') }}" placeholder="0.00">
                                </div>
                            </div>
                            <div class="row g-2" id="cgst-sgst-row">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">CGST (₹)</label>
                                    <input type="number" step="any" name="cgst_amount" id="cgst-amount-input" class="form-control form-control-sm text-end" value="{{ number_format($totals['gst'] / 2, 2, '.', '') }}" placeholder="0.00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">SGST (₹)</label>
                                    <input type="number" step="any" name="sgst_amount" id="sgst-amount-input" class="form-control form-control-sm text-end" value="{{ number_format($totals['gst'] / 2, 2, '.', '') }}" placeholder="0.00">
                                </div>
                            </div>
                            <div class="mb-2 d-none" id="igst-row">
                                <label class="form-label small fw-semibold mb-1">IGST (₹)</label>
                                <input type="number" step="any" name="igst_amount" id="igst-amount-input" class="form-control form-control-sm text-end" value="{{ number_format($totals['gst'], 2, '.', '') }}" placeholder="0.00">
                            </div>
                        </div>

                        <!-- Section: Vendor & EPOD Details -->
                        <div class="border rounded p-3 mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2"><i class="bx bx-store me-1"></i>Vendor & EPOD Details</div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">State Vendor Code</label>
                                    <input type="text" name="state_vendor_code" class="form-control form-control-sm" id="state-vendor-code-input" placeholder="State Vendor Code">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Vendor Code</label>
                                    <input type="text" name="vendor_code" class="form-control form-control-sm" id="vendor-code-input" placeholder="Vendor Code">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Vendor Name</label>
                                    <input type="text" name="vendor_name" class="form-control form-control-sm" id="vendor-name-input" placeholder="Vendor Name">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">EPOD Status</label>
                                    <select name="epod_status" class="form-select form-select-sm" id="epod-status-input">
                                        <option value="N">N</option>
                                        <option value="Y">Y</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Optional Details -->
                        <div class="border rounded p-3 mb-3">
                            <div class="fw-bold text-uppercase text-muted small mb-2"><i class="bx bx-map me-1"></i>Billing & Taxes (Optional)</div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Billing Address</label>
                                @php
                                    $defaultAddress = $bulties->count() > 0 && $bulties->first()->consignor ? ($bulties->first()->consignor->name . "\n" . $bulties->first()->consignor->address) : '';
                                @endphp
                                <textarea name="billing_address" class="form-control form-control-sm" rows="2" placeholder="Custom billing address">{{ $defaultAddress }}</textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Place of Supply</label>
                                    @php
                                        $defaultPlaceOfSupply = $bulties->count() > 0 && $bulties->first()->destinationCity ? $bulties->first()->destinationCity->state : 'RAJASTHAN';
                                    @endphp
                                    <input type="text" name="custom_place_of_supply" id="place-of-supply-input" class="form-control form-control-sm" placeholder="Place of Supply" value="{{ $defaultPlaceOfSupply }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">HSN/SAC Code</label>
                                    @php
                                        $defaultHsn = $bulties->count() > 0 && $bulties->first()->company ? $bulties->first()->company->hsn_code : '996511';
                                    @endphp
                                    <input type="text" name="custom_hsn_code" class="form-control form-control-sm" placeholder="HSN Code" value="{{ $defaultHsn }}">
                                </div>
                            </div>
                        </div>

                        <!-- Section: Format & Print Settings -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <div class="fw-bold text-uppercase text-muted small mb-2"><i class="bx bx-cog me-1"></i>Print & Format Settings</div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Print Template Type</label>
                                <select name="template_type" id="template-type-select" class="form-select form-select-sm">
                                    <option value="dynamic">Dynamic Format (From DB)</option>
                                    <option value="nathdwara">Nathdwara Format</option>
                                    <option value="gypsum">Gypsum Format</option>
                                </select>
                            </div>

                            <div id="dynamic-format-container">
                                <div class="mb-0">
                                    <label class="form-label small fw-semibold mb-1">Bill Format</label>
                                    <select name="bill_format_id" id="bill-format-select" class="form-select form-select-sm">
                                        <option value="">Select Bill Format</option>
                                        @foreach($billFormats as $format)
                                            <option value="{{ $format->id }}">{{ $format->format_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div id="nathdwara-fields-container" class="d-none">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold mb-1">MN NO</label>
                                    <input type="text" name="mn_number" id="nathdwara-mn-no" class="form-control form-control-sm" placeholder="Enter MN NO">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold mb-1">Description</label>
                                    <input type="text" name="nathdwara_description" id="nathdwara-description" class="form-control form-control-sm" value="WALL PUTTY TRANSPORATION" placeholder="Enter Description">
                                </div>
                                <div class="mb-0 d-none" id="nathdwara-rate-container">
                                    <label class="form-label small fw-semibold mb-1">Rate (per MT)</label>
                                    <input type="number" step="any" name="nathdwara_rate" id="nathdwara-rate" class="form-control form-control-sm" placeholder="Enter Rate">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="action" value="generate_print" class="btn btn-success btn-lg w-100 shadow-sm py-2 fw-bold">
                            <i class="bx bx-printer me-2 fs-5"></i> Generate & Print Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    #preview-table th, #preview-table td, #preview-grn-table th, #preview-grn-table td {
        border: 2px solid #000 !important;
        padding: 5px 4px !important;
        text-align: center !important;
        vertical-align: middle !important;
        font-size: 9.5px !important;
        font-weight: normal;
    }
    #preview-table th, #preview-grn-table th {
        font-weight: bold !important;
    }

    @media print {
        /* Hide everything by default */
        body * {
            visibility: hidden;
        }
        /* Show only the invoice preview container and its children */
        /* Show only the active invoice preview container and its children */
        #invoice-preview-card.print-active, #invoice-preview-card.print-active * {
            visibility: visible;
        }
        #nathdwara-preview-card.print-active, #nathdwara-preview-card.print-active * {
            visibility: visible;
        }
        #invoice-preview-card.print-active {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        #nathdwara-preview-card.print-active {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        /* Hide UI components inside card header */
        #invoice-preview-card.print-active .card-header, 
        #nathdwara-preview-card.print-active .card-header {
            display: none !important;
        }
        #invoice-preview-card .print-btn, 
        #invoice-preview-card .badge {
            display: none !important;
        }
        #invoice-preview-card .card-body {
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }
        #freight-preview-sheet, #grn-preview-sheet, #nathdwara-sheet-1, #nathdwara-sheet-2 {
            width: 100% !important;
            box-sizing: border-box;
            page-break-inside: avoid;
        }
        /* Hide the manual page break separator on print */
        .hide-on-print {
            display: none !important;
        }
    }
</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulties = @json($bulties);
    const formats = @json($billFormats);

    const fieldLabels = {
        'lr_no': 'LR NO.',
        'lr_date': 'DISP. DATE',
        'from_city': 'FROM',
        'to_city': 'DESTINATION',
        'consignee_id': 'CONSIGNEE',
        'vehicle_id': 'VEHICLE NO.',
        'driver_id': 'DRIVER',
        'payment_type': 'PAYMENT TYPE',
        'gst_type': 'GST TYPE',
        'declared_value': 'DEC. VALUE',
        'freight_charges': 'FREIGHT',
        'gst_amount': 'GST AMOUNT',
        'other_charges': 'OTHER CHARGES',
        'total_amount': 'TOTAL AMOUNT',
        'advance_amount': 'ADVANCE',
        'remaining_amount': 'REMAINING',
        'bilty_commission': 'COMMISSION',
        'order_number': 'ORDER NO.',
        'delivery_number': 'DILIVERY NO.',
        'invoice_number': 'INVOICE NO.',
        'invoice_date': 'INVOICE DATE',
        'eway_bill_no': 'E-WAY BILL NO.',
        'generation_date': 'GEN DATE',
        'expiry_date': 'EXP DATE',
        'mode': 'MODE',
        'damage_amount': 'DAMAGE AMOUNT',
        'shortage_amount': 'SHORTAGE AMOUNT',
        'e_lr_no': 'E-LR NO.',
        'ul_date': 'U/L DATE',
        'ul_rate': 'U/L RATE',
        'ul_amount': 'UL AMOUNT',
        'bag_ld': 'BAG LOAD',
        'bag_ul': 'BAG UNLOAD',
        'bag_short': 'BAG SHORT',
        'rate_mt': 'RATE M/T',
        'qty_mt': 'QTY/MT',
        'description_services': 'DESCRIPTION OF SERVICES',
        'posting_date': 'POSTING DATE',
        'po_no': 'PO NO.',
        'po_item': 'PO ITEM',
        'mat_doc': 'MAT DOC',
        'gate_entry_no': 'GATE ENTRY NO.',
        'challan_no': 'CHALAAN NO.',
        'challan_date': 'CHALLAN DATE',
        'transporter_code': 'TRANSPORTER CODE',
        'transporter_name': 'TRANSPOSTER NAME',
        'gate_out_date': 'GATE OUT DATE',
        'invoice_doc': 'INVOICE DOC',
        'bilty_detail_invoice_date': 'INVOICE DATE',
        'invoice_time': 'INVOICE TIME',
        'grn_no': 'GRN NO. (RECD. QTY)',
        'grn_date': 'GRN DATE (RECD QTY)',
        'grn_time': 'GRN TIME (RECD QTY)',
        'recd_qty': 'RECD. QTY',
        'arrival_time': 'ARRIVAL TIME',
        'shortage_grn_no': 'GRN NO. (SHORTAGE)',
        'shortage_grn_date': 'GRN DATE (SHORTAGE)',
        'short_qty': 'SHORT QTY',
        'item_name': 'ITEM NAME',
        'packaging_type': 'PACKAGING TYPE',
        'articles': 'ARTICLES',
        'weight': 'WEIGHT',
        'unit': 'UNIT',
        'freight_per_mt': 'FREIGHT/MT',
        'freight_per_kg': 'FREIGHT/MT',
        'item_amount': 'ITEM AMOUNT',
        'pod_file': 'POD FILE',
        'bill_no': 'BILL NO.',
        'supplier_no': 'SUPPLIER NO.',
        'material_name': 'MATERIAL NAME',
        'material_no': 'MATERIAL NO.',
        'depot_name': 'DEPOT NAME',
        'billed_qty': 'BILLED QTY',
        'mn_no': 'MN NO',
        'no_of_lr': 'NO. OF DI'
    };

    function numberToWords(num) {
        const a = ['', 'one ', 'two ', 'three ', 'four ', 'five ', 'six ', 'seven ', 'eight ', 'nine ', 'ten ', 'eleven ', 'twelve ', 'thirteen ', 'fourteen ', 'fifteen ', 'sixteen ', 'seventeen ', 'eighteen ', 'nineteen '];
        const b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        
        if ((num = num.toString()).length > 9) return 'overflow';
        let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
        if (!n) return ''; 
        let str = '';
        str += (Number(n[1]) != 0) ? (a[Number(n[1])] || b[Number(n[1][0])] + ' ' + a[Number(n[1][1])]) + 'crore ' : '';
        str += (Number(n[2]) != 0) ? (a[Number(n[2])] || b[Number(n[2][0])] + ' ' + a[Number(n[2][1])]) + 'lakh ' : '';
        str += (Number(n[3]) != 0) ? (a[Number(n[3])] || b[Number(n[3][0])] + ' ' + a[Number(n[3][1])]) + 'thousand ' : '';
        str += (Number(n[4]) != 0) ? (a[Number(n[4])] || b[Number(n[4][0])] + ' ' + a[Number(n[4][1])]) + 'hundred ' : '';
        str += (Number(n[5]) != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[Number(n[5][0])] + ' ' + a[Number(n[5][1])]) : '';
        return str.trim() ? str.trim() + ' Rupees' : 'Zero Rupees';
    }

    function formatComma(num, decimals = 3) {
        let parts = num.toFixed(decimals).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function convertNumberToWords(amount) {
        let tempAmount = Math.floor(amount);
        let paise = Math.round((amount - tempAmount) * 100);
        let rupeesStr = numberToWords(tempAmount);
        if (paise > 0) {
            let paiseStr = numberToWords(paise).replace(' Rupees', '');
            return rupeesStr + ' and ' + paiseStr + ' Paise Only.';
        }
        return rupeesStr + ' Only.';
    }

    function getFieldValue(bulty, fieldKey) {
        if (!bulty) return '-';
        
        const detailFields = [
            'posting_date', 'po_no', 'po_item', 'mat_doc', 'gate_entry_no', 'challan_no', 'challan_date',
            'transporter_code', 'transporter_name', 'gate_out_date', 'invoice_doc', 'bilty_detail_invoice_date',
            'invoice_time', 'grn_no', 'grn_date', 'grn_time', 'recd_qty', 'arrival_time', 'shortage_grn_no',
            'shortage_grn_date', 'short_qty', 'ul_date', 'ul_rate', 'ul_amount', 'bag_ld', 'bag_ul', 'bag_short',
            'rate_mt', 'qty_mt', 'description_services', 'challan_qty', 'final_wgt', 'supplier_id',
            'bill_no', 'supplier_no', 'material_name', 'material_no', 'depot_name', 'billed_qty'
        ];
        
        const itemFields = [
            'item_name', 'packaging_type', 'articles', 'weight', 'unit', 'freight_per_mt', 'freight_per_kg', 'item_amount', 'pod_file'
        ];
        
        if (detailFields.includes(fieldKey)) {
            const detail = bulty.bulty_detail || bulty.bultyDetail;
            if (!detail) return '-';
            
            if (fieldKey === 'supplier_id') {
                return detail.supplier ? detail.supplier.name : (detail.supplier_id || '-');
            }
            
            if (fieldKey === 'ul_amount') {
                let weight = (bulty.bulty_items || bulty.bultyItems || []).reduce((acc, it) => acc + parseFloat(it.weight || 0), 0);
                let ulRate = parseFloat(detail.ul_rate || 0);
                return (weight * ulRate).toFixed(2);
            }

            if (fieldKey === 'qty_mt') {
                const qtyMtVal = parseFloat(detail.qty_mt);
                if (qtyMtVal > 0) return qtyMtVal;
            }
            
            let valKey = fieldKey;
            if (fieldKey === 'bilty_detail_invoice_date') valKey = 'invoice_date';
            
            const val = detail[valKey];
            if (val === null || val === undefined) return '-';
            
            if (fieldKey.includes('date') && val) {
                try {
                    const date = new Date(val);
                    return isNaN(date.getTime()) ? val : date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
                } catch (e) {
                    return val;
                }
            }
            return val;
        }
        
        if (itemFields.includes(fieldKey)) {
            const items = bulty.bulty_items || bulty.bultyItems || [];
            if (items.length === 0) return '-';
            if (fieldKey === 'item_name') {
                return items.map(function(it) { return it.item ? it.item.name : (it.item_name || ''); }).filter(Boolean).join(', ') || '-';
            }
            if (fieldKey === 'packaging_type') {
                return items.map(function(it) { return it.packaging ? it.packaging.name : (it.packaging_type || ''); }).filter(Boolean).join(', ') || '-';
            }
            if (fieldKey === 'unit') {
                return items.map(function(it) { return it.unit ? it.unit.name : (it.unit || ''); }).filter(Boolean).join(', ') || '-';
            }
            if (fieldKey === 'pod_file') {
                return items.some(function(it) { return it.pod_file; }) ? 'Yes' : 'No';
            }
            if (fieldKey === 'item_amount') {
                var sum = items.reduce(function(acc, it) { return acc + parseFloat(it.amount || 0); }, 0) - parseFloat(bulty.advance_amount || 0);
                return sum > 0 ? sum : '-';
            }
            if (fieldKey === 'weight') {
                const detail = bulty.bulty_detail || bulty.bultyDetail;
                const qtyMt = detail ? parseFloat(detail.qty_mt) : 0;
                if (qtyMt > 0) return qtyMt;
                var wSum = items.reduce(function(acc, it) { return acc + parseFloat(it.weight || 0); }, 0);
                return wSum || '-';
            }
            if (fieldKey === 'freight_per_mt' || fieldKey === 'freight_per_kg') {
                const first = items.find(function(it) { return it.freight_per_mt !== undefined && it.freight_per_mt !== null; });
                return first ? first.freight_per_mt : '-';
            }
            var vals = items.map(function(it) { return it[fieldKey]; }).filter(function(v) { return v !== undefined && v !== null; });
            return vals.length > 0 ? vals.join(', ') : '-';
        }
        
        if (fieldKey === 'from_city') return (bulty.origin_city || bulty.originCity) ? (bulty.origin_city || bulty.originCity).name : '-';
        if (fieldKey === 'to_city') return (bulty.destination_city || bulty.destinationCity) ? (bulty.destination_city || bulty.destinationCity).name : '-';
        if (fieldKey === 'consignee_id') return bulty.consignee ? bulty.consignee.name : '-';
        if (fieldKey === 'vehicle_id') return bulty.vehicle ? bulty.vehicle.vehicle_number : '-';
        if (fieldKey === 'driver_id') return bulty.driver ? bulty.driver.name : '-';
        
        if (fieldKey === 'lr_date') {
            try {
                const date = new Date(bulty.lr_date);
                return isNaN(date.getTime()) ? bulty.lr_date : date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
            } catch (e) {
                return bulty.lr_date;
            }
        }
        
        if (fieldKey === 'remaining_amount') {
            return (parseFloat(bulty.freight_charges || 0) - parseFloat(bulty.advance_amount || 0)).toFixed(2);
        }

        if (fieldKey === 'no_of_lr') {
            return document.getElementById('no-of-lrs-input') ? document.getElementById('no-of-lrs-input').value : '-';
        }

        const val = bulty[fieldKey];
        return val !== null && val !== undefined ? val : '-';
    }

    let currentCompanyDeclaration = @json($defaultCompanyDeclaration ?? 'GST payable by recipient under Reverse Charge (RCM) on GTA services.');
    let currentCompanySignature = @json($defaultCompanySignatureUrl ?? '');

    function updateRcmAndDeclaration() {
        const rcmVal = $('#rcm-payable-select').val();
        const isRcm = (rcmVal === '1');
        const rcmText = isRcm ? 'YES' : 'NO';
        
        $('.preview-rcm-text').text(rcmText);

        let decText = '';
        if (isRcm) {
            decText = 'Declaration : ' + (currentCompanyDeclaration || 'GST payable by recipient under Reverse Charge (RCM) on GTA services.');
        } else {
            decText = 'Declaration : ' + (currentCompanyDeclaration || 'Tax payable under Forward Charge.');
        }
        
        $('.preview-declaration-text').text(decText);

        if (currentCompanySignature) {
            $('.preview-signature-img').attr('src', currentCompanySignature).show();
        } else {
            $('.preview-signature-img').attr('src', '').hide();
        }
    }

    $(document).on('change', '#rcm-payable-select', function() {
        updateRcmAndDeclaration();
    });

    const safeSetText = function(id, text) { const el = document.getElementById(id); if (el) el.textContent = text; };
    const safeSetHTML = function(id, html) { const el = document.getElementById(id); if (el) el.innerHTML = html; };

    // Company Name Input handler
    $(document).on('input', '#company-name-input', function() {
        const val = this.value;
        safeSetText('preview-company-name', val);
        safeSetText('preview-footer-company-1', val);
        safeSetText('preview-grn-company-name', val);
        safeSetText('preview-footer-company-2', val);
        safeSetText('nath-comp-name', val);
        safeSetText('nath-footer-company', val);
        safeSetText('nath-s2-comp-name', val);
        safeSetText('nath-s2-footer-company', val);
        safeSetText('gypsum-s2-comp-name', val);
    });

    // GST Type and Amounts handler
    let userManuallyEditedGst = false;

    $(document).on('input', '#total-gst-input, #cgst-amount-input, #sgst-amount-input, #igst-amount-input', function() {
        userManuallyEditedGst = true;
    });

    $(document).on('change', '#gst-type-select', function() {
        const type = $(this).val();
        if (type === 'IGST') {
            $('#cgst-sgst-row').addClass('d-none');
            $('#igst-row').removeClass('d-none');
            const totalGst = parseFloat($('#total-gst-input').val()) || 0;
            $('#igst-amount-input').val(totalGst.toFixed(2));
            $('#cgst-amount-input').val('0.00');
            $('#sgst-amount-input').val('0.00');
        } else {
            $('#cgst-sgst-row').removeClass('d-none');
            $('#igst-row').addClass('d-none');
            const totalGst = parseFloat($('#total-gst-input').val()) || 0;
            const half = (totalGst / 2).toFixed(2);
            $('#cgst-amount-input').val(half);
            $('#sgst-amount-input').val(half);
            $('#igst-amount-input').val('0.00');
        }
        $('select[name="bill_format_id"]').trigger('change');
    });

    $(document).on('input', '#total-gst-input', function() {
        const totalGst = parseFloat(this.value) || 0;
        const type = $('#gst-type-select').val();
        if (type === 'IGST') {
            $('#igst-amount-input').val(totalGst.toFixed(2));
        } else {
            const half = (totalGst / 2).toFixed(2);
            $('#cgst-amount-input').val(half);
            $('#sgst-amount-input').val(half);
        }
        $('select[name="bill_format_id"]').trigger('change');
    });

    $(document).on('input', '#cgst-amount-input, #sgst-amount-input', function() {
        const cgst = parseFloat($('#cgst-amount-input').val()) || 0;
        const sgst = parseFloat($('#sgst-amount-input').val()) || 0;
        const total = cgst + sgst;
        $('#total-gst-input').val(total.toFixed(2));
        $('select[name="bill_format_id"]').trigger('change');
    });

    $(document).on('input', '#igst-amount-input', function() {
        const igst = parseFloat($('#igst-amount-input').val()) || 0;
        $('#total-gst-input').val(igst.toFixed(2));
        $('select[name="bill_format_id"]').trigger('change');
    });

    // Track manual edits to bill number
    $(document).on('input', '#bill-number-input', function() {
        this.dataset.userEdited = '1';
        safeSetText('preview-bill-no', this.value);
        safeSetText('preview-grn-bill-no', this.value);
    });
    
    $(document).on('input', '#state-vendor-code-input', function() {
        safeSetText('preview-state-vendor-code', this.value);
        safeSetText('nath-state-vendor-code', this.value);
        const hasVal = this.value.trim() !== '';
        document.getElementById('preview-state-vendor-code-container').classList.toggle('d-none', !hasVal);
        document.getElementById('nath-state-vendor-code-container').classList.toggle('d-none', !hasVal);
    });

    $(document).on('input', '#vendor-code-input', function() {
        safeSetText('preview-vendor-code', this.value);
        safeSetText('nath-vendor-code', this.value);
        const hasVal = this.value.trim() !== '';
        document.getElementById('preview-vendor-code-container').classList.toggle('d-none', !hasVal);
        document.getElementById('nath-vendor-code-container').classList.toggle('d-none', !hasVal);
    });

    $(document).on('input', '#vendor-name-input', function() {
        safeSetText('preview-vendor-name', this.value);
        safeSetText('nath-vendor-name', this.value);
        const hasVal = this.value.trim() !== '';
        document.getElementById('preview-vendor-name-container').classList.toggle('d-none', !hasVal);
        document.getElementById('nath-vendor-name-container').classList.toggle('d-none', !hasVal);
    });

    $(document).on('change', '#epod-status-input', function() {
        safeSetText('preview-epod-status', this.value);
        safeSetText('nath-epod-status', this.value);
        const hasVal = this.value.trim() !== '';
        document.getElementById('preview-epod-status-container').classList.toggle('d-none', !hasVal);
        document.getElementById('nath-epod-status-container').classList.toggle('d-none', !hasVal);
    });



    $(document).on('input', 'textarea[name="billing_address"]', function() {
        const val = this.value;
        const partyName = bulties.length > 0 && bulties[0].consignor ? bulties[0].consignor.name.toUpperCase() : '';
        const fallbackAddress = '<div class="fw-bold" style="font-size: 11px;">' + partyName + '</div>' + (bulties.length > 0 && bulties[0].consignor ? (bulties[0].consignor.address || '').replace(/\n/g, '<br>') : '');
        const displayVal = val ? val.replace(/\n/g, '<br>') : fallbackAddress;
        
        safeSetHTML('preview-party-address', displayVal);
        safeSetHTML('preview-grn-party-address', displayVal);
        safeSetHTML('nath-party-address', displayVal);
    });

    $(document).on('input', 'input[name="custom_hsn_code"]', function() {
        const val = this.value;
        const fallback = bulties.length > 0 && bulties[0].company ? (bulties[0].company.hsn_code || '996511') : '996511';
        safeSetText('preview-company-hsn', val || fallback);
        safeSetText('nath-party-hsn', val || fallback);
    });

    // Auto check GST Type based on Company State vs Place of Supply
    function autoCheckGstType() {
        const companyState = bulties.length > 0 && bulties[0].company ? (bulties[0].company.state || 'RAJASTHAN').toString() : 'RAJASTHAN';
        const placeOfSupply = ($('#place-of-supply-input').val() || '').toString();
        
        if (!placeOfSupply.trim()) return;

        function cleanState(st) {
            return st.replace(/^\d+[-_\s]*/, '').replace(/[^a-zA-Z]/g, '').toUpperCase();
        }

        const cState = cleanState(companyState);
        const pSupply = cleanState(placeOfSupply);

        if (cState && pSupply && cState === pSupply) {
            if ($('#gst-type-select').val() !== 'CGST_SGST') {
                $('#gst-type-select').val('CGST_SGST').trigger('change');
            }
        } else if (cState && pSupply && cState !== pSupply) {
            if ($('#gst-type-select').val() !== 'IGST') {
                $('#gst-type-select').val('IGST').trigger('change');
            }
        }
    }

    $(document).on('input change', '#place-of-supply-input', function() {
        autoCheckGstType();
        $('select[name="bill_format_id"]').trigger('change');
    });

    // Run initial auto check on load
    autoCheckGstType();

    $(document).on('change', 'select[name="bill_format_id"]', function(e) {
        if (e && e.originalEvent) {
            userManuallyEditedGst = false;
        }
        const formatId = $(this).val();
        const previewCard = document.getElementById('invoice-preview-card');
        
        if (!formatId) {
            previewCard.classList.add('d-none');
            return;
        }
        
        const format = formats.find(f => f.id == formatId);
        if (!format) {
            previewCard.classList.add('d-none');
            return;
        }

            const isMaiharUnloading = format.format_name && format.format_name.toUpperCase().includes('MAIHAR') && format.format_name.toUpperCase().includes('UNLOADING');
            const billTypeTitle = isMaiharUnloading ? 'Transportation Unloading Bill' : 'Transportation Freight Bill';
            const el1 = document.getElementById('preview-bill-type-title');
            if (el1) el1.textContent = billTypeTitle;
            const el2 = document.getElementById('nath-bill-type-title');
            if (el2) el2.textContent = billTypeTitle;

            const invoiceType = document.querySelector('input[name="invoice_type"]').value;

            // Set default bill number based on type
            const billNoInput = document.getElementById('bill-number-input');
            if (!billNoInput.dataset.userEdited) {
                billNoInput.value = invoiceType === 'toll' ? '{{ $nextTollInvoiceNo }}' : '{{ $nextFreightInvoiceNo }}';
            }

            // Populating company details from first bulty
            if (bulties.length > 0) {
                const b = bulties[0];
                const compName = b.company ? b.company.name : '';
                const compAdd = b.company ? b.company.address : '';
                const compGst = b.company ? b.company.gst_number : '';
                const compPan = b.company && b.company.pan_number ? b.company.pan_number : '';
                const compPh = b.company ? b.company.phone : '';
                const compOwner = b.company && b.company.owner_name ? b.company.owner_name.toUpperCase() : '';
                const compHsn = b.company && b.company.hsn_code ? b.company.hsn_code : '';
                const bankAccountNo = b.company && b.company.bank_account_no ? b.company.bank_account_no : '';
                const bankIfsc = b.company && b.company.bank_ifsc ? b.company.bank_ifsc : '';
                const bankHolder = b.company && b.company.bank_holder_name ? b.company.bank_holder_name.toUpperCase() : '';

                const partyName = b.consignor ? b.consignor.name.toUpperCase() : '';
                const customBillingAddress = $('textarea[name="billing_address"]').val();
                
                const fallbackAddress = '<div class="fw-bold" style="font-size: 11px;">' + partyName + '</div>' + (b.consignor ? (b.consignor.address || '').replace(/\n/g, '<br>') : '');
                
                const partyAdd = customBillingAddress ? customBillingAddress.replace(/\n/g, '<br>') : fallbackAddress;
                const partyGst = b.consignor ? (b.consignor.gst_no || '-') : '-';
                const partyState = (b.destination_city || b.destinationCity) ? (b.destination_city || b.destinationCity).state || 'RAJASTHAN' : 'RAJASTHAN';

                const billDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
                const billNo = $('input[name="bill_number"]').val() || (invoiceType === 'toll' ? '{{ $nextTollInvoiceNo }}' : '{{ $nextFreightInvoiceNo }}');

                // Populate Sheet 1
                safeSetText('preview-company-name', compName);
                safeSetText('preview-company-address', compAdd);
                safeSetText('preview-company-gst', compGst);
                safeSetText('preview-company-pan', compPan);
                safeSetText('preview-company-phone', compPh);
                safeSetText('preview-company-owner', compOwner);
                $('.preview-company-owner-name').text(compOwner);
                const customHsn = $('input[name="custom_hsn_code"]').val();
                safeSetText('preview-company-hsn', customHsn || (b.company ? (b.company.hsn_code || '996511') : '996511'));
                safeSetText('preview-footer-company-1', compName);
                safeSetText('preview-bank-account', bankAccountNo);
                safeSetText('preview-bank-ifsc', bankIfsc);
                safeSetText('preview-bank-holder', bankHolder);
                safeSetHTML('preview-party-address', partyAdd);
                safeSetText('preview-party-gst', partyGst);
                safeSetText('preview-party-state', partyState);
                safeSetText('preview-place-of-supply', partyState);
                safeSetText('preview-bill-date', billDate);
                safeSetText('preview-bill-no', billNo);

                // Populate Nathdwara Client Details
                safeSetText('nath-party-name', partyName);
                safeSetHTML('nath-party-address', partyAdd);
                safeSetText('nath-party-gst', partyGst);
                safeSetText('nath-party-state', partyState);
                safeSetText('nath-party-place-of-supply', partyState);
                safeSetText('nath-party-hsn', customHsn || (b.company ? (b.company.hsn_code || '996511') : '996511'));

                // Populate Sheet 2
                safeSetText('preview-grn-company-name', compName);
                safeSetText('preview-grn-company-address', compAdd);
                safeSetText('preview-grn-company-gst', compGst);
                safeSetText('preview-grn-company-pan', compPan);
                safeSetText('preview-grn-company-phone', compPh);
                safeSetText('preview-grn-company-owner', compOwner);
                safeSetText('preview-grn-company-hsn', compHsn);
                safeSetText('preview-footer-company-2', compName);
                safeSetText('preview-grn-party-name', partyName);
                safeSetHTML('preview-grn-party-address', partyAdd);
                safeSetText('preview-grn-party-gst', partyGst);
                safeSetText('preview-grn-party-state', partyState);
                safeSetText('preview-grn-place-of-supply', partyState);
                safeSetText('preview-grn-bill-date', billDate);
                safeSetText('preview-grn-bill-no', billNo);

                if (b.company) {
                    currentCompanyDeclaration = b.company.declaration || '';
                    currentCompanySignature = b.company.digital_signature_url || '';
                }
                updateRcmAndDeclaration();
            }

            previewCard.classList.remove('d-none');
            
            const visibleFields = (format.field_order && format.field_order.length > 0) ? format.field_order : (format.visible_fields || []);
            
            let freightFields = [...visibleFields];
            let grnFields = (format.grn_field_order && format.grn_field_order.length > 0) ? format.grn_field_order : (format.grn_fields || []);
            
            const isGrnNewPage = !!format.grn_new_page;

            if (!isGrnNewPage) {
                grnFields = [];
            }

            // Display page break and second page depending on split status
            const pageBreakDiv = document.getElementById('preview-page-break');
            const grnSheetDiv = document.getElementById('grn-preview-sheet');

            if (isGrnNewPage && grnFields.length > 0) {
                pageBreakDiv.classList.remove('d-none');
                grnSheetDiv.classList.remove('d-none');
            } else {
                pageBreakDiv.classList.add('d-none');
                grnSheetDiv.classList.add('d-none');
            }
            
            // Determine GST percentage from format
            let gstPercentage = (format.gst_master && format.gst_master.percentage) ? parseFloat(format.gst_master.percentage) : null;
            if (invoiceType === 'toll' && gstPercentage === null) {
                gstPercentage = 18;
            }
            
            // --- RENDER TABLE 1 (Freight Table) ---
            const theadRow = document.getElementById('preview-thead-row');
            theadRow.innerHTML = '';
            
            const thSrNoFrt = document.createElement('th');
            thSrNoFrt.textContent = 'SR. NO.';
            theadRow.appendChild(thSrNoFrt);
            
            freightFields.forEach(fieldKey => {
                const th = document.createElement('th');
                th.textContent = fieldLabels[fieldKey] || fieldKey.toUpperCase().replace(/_/g, ' ');
                theadRow.appendChild(th);
            });
            
            if (freightFields.length === 0) {
                const th = document.createElement('th');
                th.textContent = 'No columns selected in this format';
                theadRow.appendChild(th);
            }
            
            const tbodyRows = document.getElementById('preview-tbody-rows');
            tbodyRows.innerHTML = '';
            

            let totalAmountSum = 0;
            let freightSum = 0;
            let gstSum = 0;
            let otherSum = 0;
            let damageSum = 0;
            let shortageSum = 0;
            
            bulties.forEach((bulty, idx) => {
                let freight = 0;
                let other = 0;
                if (invoiceType === 'toll') {
                    let tollSum = 0;
                    const ftDetails = bulty.trip ? (bulty.trip.fast_tag_details || bulty.trip.fastTagDetails || []) : [];
                    ftDetails.forEach(ft => {
                        tollSum += parseFloat(ft.amount || 0);
                    });
                    freight = tollSum;
                    other = 0;
                } else if (isMaiharUnloading) {
                    let weight = (bulty.bulty_items || bulty.bultyItems || []).reduce((acc, it) => acc + parseFloat(it.weight || 0), 0);
                    let ulRate = parseFloat((bulty.bulty_detail || bulty.bultyDetail || {}).ul_rate || 0);
                    freight = weight * ulRate;
                    other = parseFloat(bulty.other_charges || 0);
                } else {
                    freight = parseFloat(bulty.freight_charges || 0) - parseFloat(bulty.advance_amount || 0);
                    other = parseFloat(bulty.other_charges || 0);
                }

                let bultyGst = parseFloat(bulty.gst_amount || 0);
                
                if (gstPercentage !== null) {
                    bultyGst = freight * (gstPercentage / 100);
                }
                
                const totalWithoutGst = freight + other;
                const total = totalWithoutGst + bultyGst;
                const damageAmount = parseFloat(bulty.damage_amount || 0);
                const shortageAmount = parseFloat(bulty.shortage_amount || 0);
                const netTotal = total - damageAmount - shortageAmount;
                const totalAmountWithoutGst = totalWithoutGst - damageAmount - shortageAmount;
                
                totalAmountSum += netTotal;
                freightSum += freight;
                gstSum += bultyGst;
                otherSum += other;
                damageSum += damageAmount;
                shortageSum += shortageAmount;
                
                // Update Selected LRs Table Row
                const selectedTr = document.querySelector(`tr[data-bulty-id="${bulty.id}"]`);
                if (selectedTr) {
                    selectedTr.querySelector('.col-freight').textContent = freight.toFixed(2);
                    selectedTr.querySelector('.col-gst').textContent = bultyGst.toFixed(2);
                    selectedTr.querySelector('.col-total').innerHTML = `<strong>${netTotal.toFixed(2)}</strong>`;
                }
                
                const tr = document.createElement('tr');
                const tdSrNo = document.createElement('td');
                tdSrNo.textContent = idx + 1;
                tr.appendChild(tdSrNo);
                
                freightFields.forEach(fieldKey => {
                    const td = document.createElement('td');
                    let displayVal = getFieldValue(bulty, fieldKey);
                    
                    if (fieldKey === 'freight_charges') {
                        displayVal = total;
                    } else if (fieldKey === 'other_charges' && invoiceType === 'toll') {
                        displayVal = 0;
                    } else if (fieldKey === 'gst_amount' && gstPercentage !== null) {
                        displayVal = bultyGst;
                    } else if (fieldKey === 'total_amount') {
                        displayVal = totalAmountWithoutGst;
                    }
                    
                    if (['freight_charges', 'gst_amount', 'other_charges', 'total_amount', 'advance_amount', 'remaining_amount', 'damage_amount', 'shortage_amount', 'item_amount'].includes(fieldKey)) {
                        const parsedNum = parseFloat(displayVal);
                        displayVal = !isNaN(parsedNum) ? parsedNum.toFixed(2) : displayVal;
                    } else if (['weight', 'qty_mt', 'recd_qty', 'short_qty', 'billed_qty', 'challan_qty', 'final_wgt'].includes(fieldKey)) {
                        const parsedNum = parseFloat(displayVal);
                        displayVal = !isNaN(parsedNum) ? formatComma(parsedNum, 3) : displayVal;
                    }
                    
                    td.textContent = displayVal;
                    tr.appendChild(td);
                });
                
                if (freightFields.length === 0) {
                    const td = document.createElement('td');
                    td.textContent = 'Configure visible fields in Bill Format Master.';
                    tr.appendChild(td);
                }
                
                tbodyRows.appendChild(tr);
            });

            // Update Selected LRs table totals
            const netFreightSum = freightSum - damageSum - shortageSum;
            const sumSelFreight = document.getElementById('sum-selected-freight');
            if (sumSelFreight) {
                sumSelFreight.textContent = netFreightSum.toFixed(2);
                document.getElementById('sum-selected-gst').textContent = gstSum.toFixed(2);
                document.getElementById('sum-selected-other').textContent = otherSum.toFixed(2);
                document.getElementById('sum-selected-total').textContent = totalAmountSum.toFixed(2);
            }

            // Update Bill Summary totals
            const sumTotalFreight = document.getElementById('summary-total-freight');
            if (sumTotalFreight) {
                sumTotalFreight.textContent = '₹ ' + netFreightSum.toFixed(2);
                document.getElementById('summary-total-gst').textContent = '₹ ' + gstSum.toFixed(2);
                document.getElementById('summary-total-other').textContent = '₹ ' + otherSum.toFixed(2);
                document.getElementById('summary-grand-total').textContent = '₹ ' + totalAmountSum.toFixed(2);
            }

            // Update Total Amount input
            const totalAmountInput = document.getElementById('total-amount-input');
            if (totalAmountInput) {
                totalAmountInput.value = totalAmountSum.toFixed(2);
            }

            // --- RENDER TABLE 2 (GRN Table) ---
            if (isGrnNewPage && grnFields.length > 0) {
                const grnTheadRow = document.getElementById('preview-grn-thead-row');
                grnTheadRow.innerHTML = '';
                
                const thSrNoGrn = document.createElement('th');
                thSrNoGrn.textContent = 'SR. NO.';
                grnTheadRow.appendChild(thSrNoGrn);
                
                grnFields.forEach(fieldKey => {
                    const th = document.createElement('th');
                    th.textContent = fieldLabels[fieldKey] || fieldKey.toUpperCase().replace(/_/g, ' ');
                    grnTheadRow.appendChild(th);
                });
                
                const grnTbodyRows = document.getElementById('preview-grn-tbody-rows');
                grnTbodyRows.innerHTML = '';
                
                bulties.forEach((bulty, idx) => {
                    const tr = document.createElement('tr');
                    
                    const tdSrNo = document.createElement('td');
                    tdSrNo.textContent = idx + 1;
                    tr.appendChild(tdSrNo);
                    
                    grnFields.forEach(fieldKey => {
                        const td = document.createElement('td');
                        let displayVal = getFieldValue(bulty, fieldKey);
                        
                        if (['challan_qty', 'final_wgt', 'recd_qty', 'short_qty'].includes(fieldKey)) {
                            const parsedNum = parseFloat(displayVal);
                            displayVal = !isNaN(parsedNum) ? formatComma(parsedNum, 3) : displayVal;
                        }
                        
                        td.textContent = displayVal;
                        tr.appendChild(td);
                    });
                    
                    grnTbodyRows.appendChild(tr);
                });
            }

            // If user hasn't manually edited GST amounts or if format changed, auto-populate inputs with format-calculated GST
            if (!userManuallyEditedGst) {
                $('#total-gst-input').val(gstSum.toFixed(2));
                const curType = $('#gst-type-select').val() || 'CGST_SGST';
                if (curType === 'IGST') {
                    $('#igst-amount-input').val(gstSum.toFixed(2));
                    $('#cgst-amount-input').val('0.00');
                    $('#sgst-amount-input').val('0.00');
                } else {
                    const half = (gstSum / 2).toFixed(2);
                    $('#cgst-amount-input').val(half);
                    $('#sgst-amount-input').val(half);
                    $('#igst-amount-input').val('0.00');
                }
            }

            // Calculate GST values and render split table rows based on user GST Type selection
            const selectedGstType = $('#gst-type-select').val() || 'CGST_SGST';
            const userTotalGst = parseFloat($('#total-gst-input').val());
            const userCgst = (selectedGstType === 'CGST_SGST') ? parseFloat($('#cgst-amount-input').val()) : 0;
            const userSgst = (selectedGstType === 'CGST_SGST') ? parseFloat($('#sgst-amount-input').val()) : 0;
            const userIgst = (selectedGstType === 'IGST') ? parseFloat($('#igst-amount-input').val()) : 0;

            const activeGstPercentage = gstPercentage !== null ? gstPercentage : 5;

            const gstSplitTable = document.getElementById('preview-gst-split-table');
            gstSplitTable.innerHTML = '';

            let gstHtml = '';
            if (selectedGstType === 'CGST_SGST') {
                const halfRate = (activeGstPercentage / 2).toFixed(1).replace('.0', '');
                gstHtml += `
                    <tr style="border-bottom: 1px solid #000;">
                        <td class="p-2 fw-bold" style="border-right: 1px solid #000; width: 60%;">C GST ${halfRate}%</td>
                        <td class="p-2 text-end" style="width: 40%;">${userCgst.toFixed(2)}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #000;">
                        <td class="p-2 fw-bold" style="border-right: 1px solid #000;">S GST ${halfRate}%</td>
                        <td class="p-2 text-end">${userSgst.toFixed(2)}</td>
                    </tr>
                `;
            } else {
                const fullRate = activeGstPercentage.toFixed(1).replace('.0', '');
                gstHtml += `
                    <tr style="border-bottom: 1px solid #000;">
                        <td class="p-2 fw-bold" style="border-right: 1px solid #000; width: 60%;">I GST ${fullRate}%</td>
                        <td class="p-2 text-end" style="width: 40%;">${userIgst.toFixed(2)}</td>
                    </tr>
                `;
            }

            const grandTotalCalc = freightSum + otherSum - damageSum - shortageSum + userTotalGst;
            $('#total-amount-input').val(grandTotalCalc.toFixed(2));

            gstHtml += `
                <tr style="border-bottom: 2px solid #000;">
                    <td class="p-2 fw-bold" style="border-right: 1px solid #000;">TOTAL GST</td>
                    <td class="p-2 text-end fw-bold">${userTotalGst.toFixed(2)}</td>
                </tr>
                <tr style="background: #f2f2f2; font-weight: bold;">
                    <td class="p-2 text-center" style="border-right: 1px solid #000; font-size: 11px;">GRAND TOTAL</td>
                    <td class="p-2 text-end" style="font-size: 11px;">${grandTotalCalc.toFixed(2)}</td>
                </tr>
            `;
            gstSplitTable.innerHTML = gstHtml;
            
            // Amount in words
            document.getElementById('preview-amount-in-words').textContent = convertNumberToWords(grandTotalCalc);
        });

    $(document).on('change', '#template-type-select', function() {
        const val = $(this).val();
        if (val === 'dynamic') {
            $('#dynamic-format-container').removeClass('d-none');
            $('#nathdwara-fields-container').addClass('d-none');
            $('#bill-format-select').attr('required', true);
            
            $('#invoice-preview-card').removeClass('d-none');
            $('#invoice-preview-card').addClass('print-active');
            $('#nathdwara-preview-card').removeClass('print-active');
            $('#nathdwara-preview-card').addClass('d-none');
            
            $('select[name="bill_format_id"]').trigger('change');
        } else if (val === 'nathdwara' || val === 'gypsum') {
            $('#dynamic-format-container').addClass('d-none');
            $('#nathdwara-fields-container').removeClass('d-none');
            $('#bill-format-select').attr('required', false);
            
            if (val === 'gypsum') {
                $('#nathdwara-mn-label').text('PO NO');
                $('#nathdwara-desc-label').text('Description of Services');
                $('#nathdwara-description').attr('placeholder', 'Enter Description of Services');
                if (!$('#nathdwara-description').val() || $('#nathdwara-description').val() === 'WALL PUTTY TRANSPORATION') {
                    $('#nathdwara-description').val('TRANSPORTATION OF GYPSUM');
                }
                $('#nathdwara-preview-card .badge').text('Gypsum Format');
                $('#nathdwara-rate-container').removeClass('d-none');
            } else {
                $('#nathdwara-mn-label').text('MN NO');
                $('#nathdwara-desc-label').text('Description');
                $('#nathdwara-description').attr('placeholder', 'Enter Description');
                if (!$('#nathdwara-description').val() || $('#nathdwara-description').val() === 'TRANSPORTATION OF GYPSUM') {
                    $('#nathdwara-description').val('WALL PUTTY TRANSPORATION');
                }
                $('#nathdwara-preview-card .badge').text('Nathdwara Format');
                $('#nathdwara-rate-container').addClass('d-none');
            }
            
            $('#invoice-preview-card').removeClass('print-active');
            $('#invoice-preview-card').addClass('d-none');
            $('#nathdwara-preview-card').removeClass('d-none');
            $('#nathdwara-preview-card').addClass('print-active');
            
            renderNathdwaraPreview();
        } else {
            $('#dynamic-format-container').addClass('d-none');
            $('#nathdwara-fields-container').addClass('d-none');
            $('#bill-format-select').attr('required', false);
            
            $('#invoice-preview-card').removeClass('print-active');
            $('#invoice-preview-card').addClass('d-none');
            $('#nathdwara-preview-card').removeClass('print-active');
            $('#nathdwara-preview-card').addClass('d-none');
        }
    });

    $(document).on('input', '#nathdwara-mn-no, #nathdwara-description, #nathdwara-rate', function() {
        const type = $('#template-type-select').val();
        if (type === 'nathdwara' || type === 'gypsum') {
            renderNathdwaraPreview();
        }
    });

    $(document).on('input', '#bill-number-input', function() {
        const type = $('#template-type-select').val();
        if (type === 'nathdwara' || type === 'gypsum') {
            renderNathdwaraPreview();
        }
    });

    function renderNathdwaraPreview() {
        const previewCard = document.getElementById('nathdwara-preview-card');
        previewCard.classList.remove('d-none');
        
        const type = $('#template-type-select').val();

        let compName = '', compAdd = '', compGst = '', compPan = '', compPh = '', compOwner = '';
        let bankAccountNo = '', bankIfsc = '', bankHolder = '';
        if (bulties.length > 0) {
            const b = bulties[0];
            compName = b.company ? b.company.name : '';
            compAdd = b.company ? b.company.address : '';
            compGst = b.company ? b.company.gst_number : '';
            compPan = b.company && b.company.pan_number ? b.company.pan_number : '';
            compPh = b.company ? b.company.phone : '';
            compOwner = b.company && b.company.owner_name ? b.company.owner_name.toUpperCase() : '';
            bankAccountNo = b.company && b.company.bank_account_no ? b.company.bank_account_no : '';
            bankIfsc = b.company && b.company.bank_ifsc ? b.company.bank_ifsc : '';
            bankHolder = b.company && b.company.bank_holder_name ? b.company.bank_holder_name.toUpperCase() : '';
        }

        const billDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        const invoiceType = document.querySelector('input[name="invoice_type"]').value;
        
        const billNoInput = document.getElementById('bill-number-input');
        if (!billNoInput.dataset.userEdited && !billNoInput.value) {
            billNoInput.value = invoiceType === 'toll' ? '{{ $nextTollInvoiceNo }}' : '{{ $nextFreightInvoiceNo }}';
        }
        const billNo = billNoInput.value;

        safeSetText('nath-comp-name', compName);
        safeSetText('nath-comp-gst', compGst);
        safeSetText('nath-comp-pan', compPan);
        safeSetText('nath-comp-owner-phone', compOwner + ' ' + compPh);
        safeSetText('nath-comp-address', compAdd);
        safeSetText('nath-bill-date', billDate);
        safeSetText('nath-bill-no', billNo);
        safeSetText('nath-bank-account', bankAccountNo);
        safeSetText('nath-bank-ifsc', bankIfsc);
        safeSetText('nath-bank-holder', bankHolder);
        safeSetText('nath-footer-company', compName);

        safeSetText('nath-s2-comp-name', compName + '.');
        safeSetText('nath-s2-phone', 'Off Phone No ' + compPh + ', Mobile No Res.Tel');
        safeSetText('nath-s2-bill-no', billNo);
        safeSetText('nath-s2-date', billDate);
        safeSetText('nath-s2-footer-company', compName);

        const s2Tbody = document.getElementById('nath-s2-tbody');
        s2Tbody.innerHTML = '';
        const gypsumS2Tbody = document.getElementById('gypsum-s2-tbody');
        if (gypsumS2Tbody) gypsumS2Tbody.innerHTML = '';
        safeSetText('gypsum-s2-comp-name', compName + ' GRN DETAILS');
        let totalQty = 0, totalAmt = 0, totalDamageQty = 0, totalDamageAmt = 0, totalShortageQty = 0, totalShortageAmt = 0;
        let totalGypsumChallanQty = 0, totalGypsumFinalWgt = 0;

        bulties.forEach(bulty => {
            const tr = document.createElement('tr');
            
            const fromCity = (bulty.origin_city || bulty.originCity) ? (bulty.origin_city || bulty.originCity).name : '-';
            const toCity = (bulty.destination_city || bulty.destinationCity) ? (bulty.destination_city || bulty.destinationCity).name : '-';
            let formattedLrDate = bulty.lr_date;
            try {
                const lrDate = new Date(bulty.lr_date);
                if (!isNaN(lrDate.getTime())) {
                    formattedLrDate = lrDate.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '.');
                }
            } catch (e) {}
            
            let qtyMt = parseFloat(getFieldValue(bulty, 'qty_mt')) || 0;
            let rateMt = parseFloat(getFieldValue(bulty, 'rate_mt')) || 0;
            
            let amt = 0;
            if (type === 'gypsum') {
                qtyMt = parseFloat(getFieldValue(bulty, 'final_wgt')) || 0;
                amt = qtyMt * rateMt;
            } else if (invoiceType === 'toll') {
                let tollSum = 0;
                const ftDetails = bulty.trip ? (bulty.trip.fast_tag_details || bulty.trip.fastTagDetails || []) : [];
                ftDetails.forEach(ft => {
                    tollSum += parseFloat(ft.amount || 0);
                });
                amt = tollSum;
            } else {
                if (type === 'nathdwara') {
                    amt = parseFloat(bulty.freight_charges || 0);
                } else {
                    amt = parseFloat(bulty.freight_charges || 0) - parseFloat(bulty.advance_amount || 0);
                }
                other = parseFloat(bulty.other_charges || 0);
            }
            
            if (type !== 'gypsum') {
                if (qtyMt === 0 && rateMt > 0 && amt > 0) {
                    qtyMt = amt / rateMt;
                } else if (rateMt === 0 && qtyMt > 0 && amt > 0) {
                    rateMt = amt / qtyMt;
                }
            }

            let damageAmt = parseFloat(bulty.damage_amount || 0);
            let shortageAmt = parseFloat(bulty.shortage_amount || 0);
            
            let damageQty = parseFloat(getFieldValue(bulty, 'damage_qty')) || (rateMt > 0 ? (damageAmt / rateMt) : 0);
            let shortageQty = parseFloat(getFieldValue(bulty, 'short_qty')) || (rateMt > 0 ? (shortageAmt / rateMt) : 0);

            totalQty += qtyMt;
            totalAmt += amt;
            totalDamageQty += damageQty;
            totalDamageAmt += damageAmt;
            totalShortageQty += shortageQty;
            totalShortageAmt += shortageAmt;

            tr.innerHTML = `
                <td style="border-right: 1px solid #000; padding: 2px;">${bulty.vehicle ? bulty.vehicle.vehicle_number : '-'}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${fromCity}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${toCity}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${bulty.lr_no}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${formattedLrDate}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">ROAD</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${qtyMt.toFixed(3)}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${rateMt.toFixed(2)}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${damageQty.toFixed(3)}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${(damageQty > 0 ? (damageAmt/damageQty).toFixed(2) : '0.00')}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${shortageQty.toFixed(3)}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${(shortageQty > 0 ? (shortageAmt/shortageQty).toFixed(2) : '0.00')}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">${amt.toFixed(2)}</td>
                <td style="border-right: 1px solid #000; padding: 2px;">0.00</td>
                <td style="padding: 2px;">0.00</td>
            `;
            s2Tbody.appendChild(tr);

            // Gypsum GRN row
            const detail = bulty.bulty_detail || bulty.bultyDetail;
            
            const pDateRaw = detail && detail.posting_date ? new Date(detail.posting_date) : null;
            const pDate = pDateRaw && !isNaN(pDateRaw.getTime()) ? pDateRaw.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-';
            
            const gOutDateRaw = detail && detail.gate_out_date ? new Date(detail.gate_out_date) : null;
            const gOutDate = gOutDateRaw && !isNaN(gOutDateRaw.getTime()) ? gOutDateRaw.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-';
            
            const chDateRaw = detail && detail.challan_date ? new Date(detail.challan_date) : null;
            const chDate = chDateRaw && !isNaN(chDateRaw.getTime()) ? chDateRaw.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-') : '-';
            
            const challanQtyVal = parseFloat(getFieldValue(bulty, 'challan_qty')) || 0;
            const finalWgtVal = parseFloat(getFieldValue(bulty, 'final_wgt')) || 0;
            
            totalGypsumChallanQty += challanQtyVal;
            totalGypsumFinalWgt += finalWgtVal;
            
            if (gypsumS2Tbody) {
                const gTr = document.createElement('tr');
                gTr.innerHTML = `
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${pDate}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.po_no || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.mat_doc || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.gate_entry_no || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${gOutDate}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.supplier_no || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail && detail.supplier ? detail.supplier.name : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${bulty.vehicle ? bulty.vehicle.vehicle_number : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.challan_no || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${chDate}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${bulty.lr_no || '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.transporter_code || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.transporter_name || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.po_item || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.material_no || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${detail ? (detail.material_name || '-') : '-'}</td>
                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px;">${challanQtyVal.toFixed(2)}</td>
                    <td style="border-bottom: 1px solid #000; padding: 2px;">${finalWgtVal.toFixed(2)}</td>
                `;
                gypsumS2Tbody.appendChild(gTr);
            }
        });

        safeSetText('gypsum-s2-total-challan', totalGypsumChallanQty.toFixed(3));
        safeSetText('gypsum-s2-total-final', totalGypsumFinalWgt.toFixed(3));
        
        const netTotalS2 = totalAmt - totalDamageAmt - totalShortageAmt;
        safeSetText('nath-s2-summary-qty', totalQty.toFixed(3));
        safeSetText('nath-s2-summary-amt', totalAmt.toFixed(2));
        safeSetText('nath-s2-gross', totalAmt.toFixed(2));
        safeSetText('nath-s2-less-shortage', totalShortageAmt.toFixed(2));
        safeSetText('nath-s2-less-damage', totalDamageAmt.toFixed(2));
        safeSetText('nath-s2-net-total', netTotalS2.toFixed(2));
        safeSetText('nath-s2-final-total', netTotalS2.toFixed(2));
        safeSetText('nath-s2-amount-words', convertNumberToWords(netTotalS2));

        const displayMt = totalQty.toFixed(3);
        const mnNo = $('#nathdwara-mn-no').val() || '-';
        const desc = $('#nathdwara-description').val() || '';
        const userRate = parseFloat($('#nathdwara-rate').val()) || 0;
        
        let titleName = 'NATHDWARA';
        let addressText = 'MANDIYANA, NATHDWARA';
        let partyNameS2 = 'ULTRATECH CEMENT LIMITED UNIT: BIRLA WHITE Rajashree Nagar Vill. Kharia Khangar-342606';
        let descToUse = desc || 'WALL PUTTY TRANSPORATION';
        
        if (type === 'gypsum') {
            titleName = 'J K CEMENT LIMITED';
            addressText = 'TEHSIL- BARA, Bara Khas, Prayagraj, Uttar Pradesh, 212107';
            partyNameS2 = 'JK CEMENT WORKS PRAYAGRAJ';
            descToUse = desc || 'TRANSPORTATION OF GYPSUM';
            $('#nathdwara-page-break').removeClass('d-none');
            $('#nathdwara-sheet-2').addClass('d-none');
            $('#gypsum-sheet-2').removeClass('d-none');
        } else {
            $('#nathdwara-page-break').removeClass('d-none');
            $('#nathdwara-sheet-2').removeClass('d-none');
            $('#gypsum-sheet-2').addClass('d-none');
        }
        $('#nath-party-address').html('UNIT - ' + titleName + '<br>' + addressText);
        $('#nath-s2-party-address').html(partyNameS2);

        let displayRate = totalQty > 0 ? (totalAmt / totalQty) : 0;
        let displayTotalAmt = totalAmt;

        if (type === 'gypsum' && userRate > 0) {
            displayRate = userRate;
            displayTotalAmt = totalQty * userRate;
        }

        const selectedNathGstType = $('#gst-type-select').val() || 'CGST_SGST';
        let cgst = 0, sgst = 0, igst = 0, totalGst = 0;
        let gypsumGstRowsHtml = '';
        let nathGstRowsHtml = '';

        if (selectedNathGstType === 'IGST') {
            igst = displayTotalAmt * 0.05;
            totalGst = igst;
            gypsumGstRowsHtml = `
                <tr>
                    <td colspan="4" style="border-right: 2px solid #000;"></td>
                    <td style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">I GST 5%</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-igst">` + igst.toFixed(4) + `</td>
                </tr>
            `;
            
            const nathIgst = netTotalS2 * 0.05;
            nathGstRowsHtml = `
                <tr>
                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">I GST 5%</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-igst">` + nathIgst.toFixed(4) + `</td>
                </tr>
            `;
        } else {
            cgst = displayTotalAmt * 0.025;
            sgst = displayTotalAmt * 0.025;
            totalGst = cgst + sgst;
            gypsumGstRowsHtml = `
                <tr>
                    <td colspan="4" style="border-right: 2px solid #000;"></td>
                    <td style="border-right: 2px solid #000; border-bottom: 1px solid #000; padding: 4px; text-align: right; font-weight: bold;">C GST 2.5%</td>
                    <td style="padding: 4px; border-bottom: 1px solid #000; font-weight: bold;" id="nath-s1-cgst">` + cgst.toFixed(4) + `</td>
                </tr>
                <tr>
                    <td colspan="4" style="border-right: 2px solid #000;"></td>
                    <td style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: right; font-weight: bold;">S GST 2.5%</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-sgst">` + sgst.toFixed(4) + `</td>
                </tr>
            `;

            const nathCgst = netTotalS2 * 0.025;
            const nathSgst = netTotalS2 * 0.025;
            nathGstRowsHtml = `
                <tr>
                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 1px solid #000; padding: 4px; text-align: right; font-weight: bold;">C GST 2.5%</td>
                    <td style="padding: 4px; border-bottom: 1px solid #000; font-weight: bold;" id="nath-s1-cgst">` + nathCgst.toFixed(4) + `</td>
                </tr>
                <tr>
                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: right; font-weight: bold;">S GST 2.5%</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-sgst">` + nathSgst.toFixed(4) + `</td>
                </tr>
            `;
        }

        const grandTotal = (type === 'gypsum') ? displayTotalAmt : netTotalS2;
        
        safeSetText('nath-s1-desc', descToUse);
        
        if (type === 'gypsum') {
            // Gypsum specific headers
            $('#nathdwara-table-1 thead').html(`
                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                    <td style="border-right: 2px solid #000; padding: 4px;">S.R No</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">Description of Services</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">Quantity</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">Rate</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">UoM</td>
                    <td style="padding: 4px;">Total Amount</td>
                </tr>
            `);
            $('#nathdwara-table-1 tbody').html(`
                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                    <td style="border-right: 2px solid #000; padding: 8px;">1</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-desc">` + descToUse + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-qty">` + displayMt + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-rate">` + displayRate.toFixed(3) + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;"></td>
                    <td style="padding: 8px;" id="nath-s1-total">` + displayTotalAmt.toFixed(2) + `</td>
                </tr>
                ` + gypsumGstRowsHtml + `
                <tr>
                    <td colspan="4" style="border-right: 2px solid #000;"></td>
                    <td style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">TOTAL GST</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-total-gst">` + totalGst.toFixed(3) + `</td>
                </tr>
                <tr>
                    <td colspan="4" style="border-right: 2px solid #000;"></td>
                    <td style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">GRAND TOTAL</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-grand-total">` + grandTotal.toFixed(2) + `</td>
                </tr>
            `);
            // Insert PO NO above Transportation Freight Bill
            if ($('#gypsum-po-row').length === 0) {
                $('<div id="gypsum-po-row" class="row g-0" style="border-bottom: 2px solid #000; font-weight: bold; font-size: 11px;"><div class="col-6 p-2" style="border-right: 2px solid #000;">PO NO. - <span id="gypsum-po-no"></span></div><div class="col-6 p-2 text-end">DETAILS: AS PER ANNEXURE ATTACHED</div></div>').insertBefore($('#nathdwara-table-1').prev());
            }
            $('#gypsum-po-no').text(mnNo);
        } else {
            $('#nathdwara-table-1 thead').html(`
                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                    <td style="border-right: 2px solid #000; padding: 4px;">SR NO</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">DESCRIPTION</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">BILL NO</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">MN NO</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">NUMBER OF DI</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">MT</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">BILLING AMOUNT</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">LESS SHORTAGE</td>
                    <td style="border-right: 2px solid #000; padding: 4px;">LESS DAMAGE</td>
                    <td style="padding: 4px;">NET TOTAL</td>
                </tr>
            `);
            $('#nathdwara-table-1 tbody').html(`
                <tr style="border-bottom: 2px solid #000; font-weight: bold;">
                    <td style="border-right: 2px solid #000; padding: 8px;">1</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-desc">` + descToUse + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-bill-no">` + billNo + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-mn-no">` + mnNo + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-di-count">` + bulties.length + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-mt">` + displayMt + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-bill-amt">` + totalAmt.toFixed(2) + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-shortage">` + totalShortageAmt.toFixed(2) + `</td>
                    <td style="border-right: 2px solid #000; padding: 8px;" id="nath-s1-damage">` + totalDamageAmt.toFixed(2) + `</td>
                    <td style="padding: 8px;" id="nath-s1-net-total">` + netTotalS2.toFixed(2) + `</td>
                </tr>
                ` + nathGstRowsHtml + `
                <tr>
                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">TOTAL GST</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-total-gst">` + totalGst.toFixed(3) + `</td>
                </tr>
                <tr>
                    <td colspan="7" style="border-right: 2px solid #000;"></td>
                    <td colspan="2" style="border-right: 2px solid #000; border-bottom: 2px solid #000; padding: 4px; text-align: center; font-weight: bold;">GRAND TOTAL</td>
                    <td style="padding: 4px; border-bottom: 2px solid #000; font-weight: bold;" id="nath-s1-grand-total">` + grandTotal.toFixed(2) + `</td>
                </tr>
            `);
            $('#gypsum-po-row').remove();
        }
        
        safeSetText('nath-amount-words', convertNumberToWords(grandTotal));

        const totalAmountInput = document.getElementById('total-amount-input');
        if (totalAmountInput) {
            totalAmountInput.value = grandTotal.toFixed(2);
        }
    }

    // Initialize template selection
    $('#template-type-select').trigger('change');

    $('form').on('submit', function() {
        try {
            sessionStorage.removeItem('selected_billing_lrs');
        } catch (e) {}
    });
});
</script>
@endsection

