@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    @php
        $openParties = old('consignor_id') || old('consignee_id');
        $openItems = old('items');
        $openVehicle = old('vehicle_id') || old('driver_id');
        $openRef = old('order_number') || old('delivery_number') || old('from_no') || old('invoice_number') || old('eway_bill_no');
        $openOther = old('posting_date') || old('po_no') || old('mat_doc') || old('gate_entry_no') || old('challan_no') || old('inv_date') || old('grn_no');
    @endphp
    <!-- Animated Hero Header -->
    <div class="card bilty-hero mb-4 shadow-lg">
        <div class="card-body py-4 px-4 position-relative">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-sm-7 text-white">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge text-white px-3 py-2" style="background-color: rgba(255, 255, 255, 0.15); font-size:0.7rem; font-weight:700; letter-spacing:1px; border-radius:8px;">NEW BILTY</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-white" style="font-size:1.75rem;">Generate New Bilty</h3>
                    <p class="mb-0 text-white" style="opacity:0.7; font-size:0.9rem;">Create a professional transport receipt in a few simple steps.</p>
                </div>
                <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                    <a href="{{ route('admin.transport.bulties.index') }}" class="btn btn-hero shadow-sm">
                        <i class="bx bx-list-ul me-1"></i> View All Bilties
                    </a>
                </div>
            </div>
            <i class="bx bx-package hero-icon"></i>
        </div>
    </div>



    <form method="POST" action="{{ route('admin.transport.bulties.store') }}" id="biltyForm" class="bilty-form" novalidate enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <!-- Main Form Content -->
            <div class="col-xl-10 col-lg-12 mx-auto">
                <!-- Section 1: Basic Information -->
                <div id="section-basic" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="card-body p-4">
                        <div class="section-header">
                            <div class="section-icon icon-basic">
                                <i class="bx bx-info-circle"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Basic Information</h5>
                                <p class="section-subtitle">Bilty number, date, route & payment details</p>
                            </div>
                        </div>
                        
                        <div class="row g-4">
                            @if(auth()->user()->isSuperAdmin() && !empty($companies))
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                @error('company_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Select Branch</option>
                                </select>
                                <div class="invalid-feedback @error('branch_id') d-block @enderror">
                                    @error('branch_id') {{ $message }} @else Branch is required @enderror
                                </div>
                            </div>
                            @else
                                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                            @endif

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Bilty Number</label>
                                <div class="bilty-number-box">
                                    <i class="bx bx-hash"></i>
                                    <input type="text" name="lr_no" class="form-control" value="{{ old('lr_no', $nextBultyNo ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">LR Date <span class="text-danger">*</span></label>
                                <input type="date" max="9999-12-31" name="lr_date" class="form-control custom-input @error('lr_date') is-invalid @enderror" value="{{ old('lr_date', date('Y-m-d')) }}" required>
                                <div class="invalid-feedback">
                                    @error('lr_date') {{ $message }} @else Date is required @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                                <select name="payment_type" class="form-select @error('payment_type') is-invalid @enderror" required>
                                    <option value="paid" {{ old('payment_type') == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="topay" {{ old('payment_type', 'topay') == 'topay' ? 'selected' : '' }}>To Pay</option>
                                    <option value="tobill" {{ old('payment_type') == 'tobill' ? 'selected' : '' }}>To Bill</option>
                                </select>
                                <div class="invalid-feedback @error('payment_type') d-block @enderror">
                                    @error('payment_type') {{ $message }} @else Payment type is required @enderror
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0">Origin City <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-pill-add" data-bs-toggle="modal" data-bs-target="#addCityModal">
                                        <i class="bx bx-plus-circle me-1"></i> Add New
                                    </button>
                                </div>
                                <select name="from_city" class="form-select select2-ajax-city @error('from_city') is-invalid @enderror" data-placeholder="Select Origin" required>
                                    <option value="">Select Origin</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" {{ old('from_city') == $city->id ? 'selected' : '' }}>{{ $city->name }} ({{ $city->state }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback @error('from_city') d-block @enderror">
                                    @error('from_city') {{ $message }} @else Origin city is required @enderror
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end justify-content-center pb-2">
                                <div class="route-visual w-100">
                                    <div class="route-dot"></div>
                                    <div class="route-line"></div>
                                    <div class="route-dot destination"></div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0">Destination City <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-pill-add" data-bs-toggle="modal" data-bs-target="#addCityModal">
                                        <i class="bx bx-plus-circle me-1"></i> Add New
                                    </button>
                                </div>
                                <select name="to_city" class="form-select select2-ajax-city @error('to_city') is-invalid @enderror" data-placeholder="Select Destination" required>
                                    <option value="">Select Destination</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->id }}" {{ old('to_city') == $city->id ? 'selected' : '' }}>{{ $city->name }} ({{ $city->state }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback @error('to_city') d-block @enderror">
                                    @error('to_city') {{ $message }} @else Destination city is required @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Consignor & Consignee -->
                <div id="section-parties" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="section-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-parties" aria-expanded="{{ $openParties ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon icon-parties">
                                <i class="bx bx-user-pin"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Parties Details</h5>
                                <p class="section-subtitle">Sender & receiver information</p>
                            </div>
                        </div>
                        <i class="bx bx-chevron-down fs-4 collapse-chevron"></i>
                    </div>
                    <div id="collapse-parties" class="card-body p-4 collapse {{ $openParties ? 'show' : '' }}">
                        
                        <!-- Consignor -->
                        <div class="party-card consignor-card mb-4">
                            <div class="party-badge"><i class="bx bx-upload"></i> Consignor (Sender) <button type="button" class="btn btn-pill-add ms-2" data-bs-toggle="modal" data-bs-target="#addConsignorModal"><i class="bx bx-plus-circle me-1"></i> Add New</button></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="position-relative">
                                        <input type="text" name="consignor_name" id="consignor_name" class="form-control pe-5 @error('consignor_name') is-invalid @enderror" placeholder="Search by name or phone" autocomplete="off" value="{{ old('consignor_name') }}">
                                        <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                        <div id="consignor_suggestions" class="vehicle-suggestions"></div>
                                    </div>
                                    @error('consignor_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <input type="hidden" name="consignor_id" id="consignor_id" value="{{ old('consignor_id') }}">
                                <div id="consignor_loader" class="spinner-border spinner-border-sm text-primary d-none mt-1" role="status"></div>
                                <div class="col-md-6">
                                    <input type="text" name="consignor_phone" id="consignor_phone" class="form-control @error('consignor_phone') is-invalid @enderror" placeholder="Contact Number" value="{{ old('consignor_phone') }}">
                                    @error('consignor_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="consignor_gstin" id="consignor_gstin" class="form-control @error('consignor_gstin') is-invalid @enderror" placeholder="GSTIN (Optional)" value="{{ old('consignor_gstin') }}">
                                    @error('consignor_gstin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <textarea name="consignor_address" id="consignor_address" class="form-control @error('consignor_address') is-invalid @enderror" rows="2" placeholder="Full Address">{{ old('consignor_address') }}</textarea>
                                    @error('consignor_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Consignee -->
                        <div class="party-card consignee-card">
                            <div class="party-badge"><i class="bx bx-download"></i> Consignee (Receiver) <button type="button" class="btn btn-pill-add ms-2" data-bs-toggle="modal" data-bs-target="#addConsigneeModal"><i class="bx bx-plus-circle me-1"></i> Add New</button></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="position-relative">
                                        <input type="text" name="consignee_name" id="consignee_name" class="form-control pe-5 @error('consignee_name') is-invalid @enderror" placeholder="Search by name or phone" autocomplete="off" value="{{ old('consignee_name') }}">
                                        <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                        <div id="consignee_suggestions" class="vehicle-suggestions"></div>
                                    </div>
                                    @error('consignee_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <input type="hidden" name="consignee_id" id="consignee_id" value="{{ old('consignee_id') }}">
                                <div id="consignee_loader" class="spinner-border spinner-border-sm text-primary d-none mt-1" role="status"></div>
                                <div class="col-md-6">
                                    <input type="text" name="consignee_phone" id="consignee_phone" class="form-control @error('consignee_phone') is-invalid @enderror" placeholder="Contact Number" value="{{ old('consignee_phone') }}">
                                    @error('consignee_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="consignee_gstin" id="consignee_gstin" class="form-control @error('consignee_gstin') is-invalid @enderror" placeholder="GSTIN (Optional)" value="{{ old('consignee_gstin') }}">
                                    @error('consignee_gstin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <textarea name="consignee_address" id="consignee_address" class="form-control @error('consignee_address') is-invalid @enderror" rows="2" placeholder="Full Address">{{ old('consignee_address') }}</textarea>
                                    @error('consignee_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Items / Goods -->
                <div id="section-items" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="section-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon icon-items">
                                <i class="bx bx-package"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Items / Goods Details</h5>
                                <p class="section-subtitle">Add items, quantity, weight & rate</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-pill-add" id="addItemRow">
                                <i class="bx bx-plus-circle me-1"></i> Add More Item
                            </button>
                            <i class="bx bx-chevron-down fs-4 collapse-chevron" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-items" aria-expanded="{{ $openItems ? 'true' : 'false' }}"></i>
                        </div>
                    </div>
                    <div id="collapse-items" class="card-body p-4 collapse {{ $openItems ? 'show' : '' }}">

                        <div id="itemsContainer">
                            <div class="item-card item-row" data-index="0">
                                <div class="item-card-header">
                                    <span class="item-number">Item #<span class="row-num">1</span></span>
                                    <button type="button" class="btn btn-sm text-danger border-0 remove-item" disabled style="opacity:0.3;"><i class="bx bx-trash"></i> Remove</button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label class="form-label fw-bold small text-uppercase opacity-75 mb-0">Item Name</label>
                                            <button type="button" class="btn btn-pill-add btn-xs" data-bs-toggle="modal" data-bs-target="#addItemMasterModal"><i class="bx bx-plus-circle me-1"></i> Add New</button>
                                        </div>
                                        <div class="position-relative">
                                            <input type="text" name="items[0][item_name]" class="form-control item-name pe-5" placeholder="Search item..." autocomplete="off">
                                            <input type="hidden" name="items[0][item_id]" class="item-id">
                                            <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                            <div class="item-suggestions vehicle-suggestions"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-uppercase opacity-75">Packaging Type</label>
                                        <select name="items[0][packaging_type]" class="form-select item-packaging">
                                            <option value="">Select</option>
                                            @foreach($packagings as $p)
                                            <option value="{{ $p->name }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-uppercase opacity-75">No of Articles</label>
                                        <input type="number" step="1" min="0" name="items[0][articles]" class="form-control item-articles" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-uppercase opacity-75">Total Weight</label>
                                        <input type="number" step="0.01" min="0" name="items[0][weight]" class="form-control item-weight" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-uppercase opacity-75">Unit</label>
                                        <select name="items[0][unit]" class="form-select item-unit">
                                            <option value="">Select</option>
                                            @foreach($units as $u)
                                            <option value="{{ $u->name }}">{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-uppercase opacity-75 freight-label">Freight per <span class="freight-unit">mt</span></label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" min="0" name="items[0][freight_per_mt]" class="form-control item-freight" value="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-uppercase opacity-75">Amount</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text">₹</span>
                                            <input type="text" name="items[0][amount]" class="form-control item-amount" value="0.00" readonly style="font-weight:700;color:var(--bilty-primary);background:#f8fafc;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="items-summary d-flex flex-wrap gap-3 mt-4 pt-3 border-top">
                            <div class="item-summary-badge">
                                <span class="summary-label">Items</span>
                                <span class="summary-value" id="totalItemsCount">1</span>
                            </div>
                            <div class="item-summary-badge">
                                <span class="summary-label">Articles</span>
                                <span class="summary-value" id="totalArticles">0</span>
                            </div>
                            <div class="item-summary-badge">
                                <span class="summary-label">Weight</span>
                                <span class="summary-value" id="totalWeight">0</span>
                            </div>
                            <div class="item-summary-badge">
                                <span class="summary-label">Total Amount</span>
                                <span class="summary-value" id="totalAmount">₹ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="pickup_location" value="Standard">
                <input type="hidden" name="delivery_location" value="Standard">
                <input type="hidden" name="declared_value" value="0">

                <!-- Section 4: Vehicle & Driver -->
                <div id="section-vehicle" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="section-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-vehicle" aria-expanded="{{ $openVehicle ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon icon-vehicle">
                                <i class="bx bx-bus"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Vehicle & Driver Details</h5>
                                <p class="section-subtitle">Truck information, ownership & driver assignment</p>
                            </div>
                        </div>
                        <i class="bx bx-chevron-down fs-4 collapse-chevron"></i>
                    </div>
                    <div id="collapse-vehicle" class="card-body p-4 collapse {{ $openVehicle ? 'show' : '' }}">

                        <div class="row g-3">
                            <!-- Vehicle Number Search -->
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold small text-uppercase opacity-75 mb-0">Vehicle Number</label>
                                    <button type="button" class="btn btn-pill-add" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                                        <i class="bx bx-plus-circle me-1"></i> Add New
                                    </button>
                                </div>
                                <div class="position-relative">
                                    <input type="text" name="vehicle_number" id="vehicle_number" class="form-control pe-5" placeholder="GJ 01 AB 1234" autocomplete="off" value="{{ old('vehicle_number') }}">
                                    <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                    <div id="vehicle_suggestions" class="vehicle-suggestions"></div>
                                </div>
                                <input type="hidden" name="vehicle_id" id="vehicle_id" value="{{ old('vehicle_id') }}">
                                <div id="vehicle_loader" class="spinner-border spinner-border-sm text-primary d-none mt-1" role="status"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Vehicle Type</label>
                                <input type="text" name="vehicle_type" id="vehicle_type" class="form-control" placeholder="e.g. Truck, Trailer">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Make / Model</label>
                                <input type="text" name="make_model" id="make_model" class="form-control" placeholder="e.g. Tata 2518">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Capacity (Tons)</label>
                                <input type="number" step="0.01" name="capacity_tons" id="capacity_tons" class="form-control" placeholder="0.00">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Owner Name</label>
                                <input type="text" name="owner_name" id="owner_name" class="form-control" placeholder="Owner Name">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Owner Phone</label>
                                <input type="text" name="owner_phone" id="owner_phone" class="form-control" placeholder="Owner Contact">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Insurance Expiry</label>
                                <input type="date" max="9999-12-31" name="insurance_expiry" id="insurance_expiry" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Fitness Expiry</label>
                                <input type="date" max="9999-12-31" name="fitness_expiry" id="fitness_expiry" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Permit Expiry</label>
                                <input type="date" max="9999-12-31" name="permit_expiry" id="permit_expiry" class="form-control">
                            </div>

                            <div class="col-12">
                                <div class="driver-separator">
                                    <i class="bx bx-user"></i> Driver Information
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold small text-uppercase opacity-75 mb-0">Driver Name</label>
                                    <button type="button" class="btn btn-pill-add" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                                        <i class="bx bx-plus-circle me-1"></i> Add New
                                    </button>
                                </div>
                                <div class="position-relative">
                                    <input type="text" name="driver_name" id="driver_name" class="form-control pe-5 @error('driver_id') is-invalid @enderror" placeholder="Search by name, mobile or driver ID" autocomplete="off" required>
                                    <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                    <div id="driver_suggestions" class="vehicle-suggestions"></div>
                                </div>
                                <div class="invalid-feedback @error('driver_id') d-block @enderror">
                                    @error('driver_id') {{ $message }} @else Driver name is required @enderror
                                </div>
                                <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id') }}">
                                <div id="driver_loader" class="spinner-border spinner-border-sm text-primary d-none mt-1" role="status"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Driver Mobile</label>
                                <div class="position-relative">
                                    <input type="text" name="driver_mobile" id="driver_mobile" class="form-control pe-5 @error('driver_id') is-invalid @enderror" placeholder="Search by mobile number" autocomplete="off" required>
                                    <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                    <div id="driver_mobile_suggestions" class="vehicle-suggestions"></div>
                                </div>
                                <div class="invalid-feedback @error('driver_id') d-block @enderror">
                                    @error('driver_id') {{ $message }} @else Mobile number is required @enderror
                                </div>
                                <div id="driver_mobile_loader" class="spinner-border spinner-border-sm text-primary d-none mt-1" role="status"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase opacity-75">License Number</label>
                                <input type="text" name="driver_license_no" id="driver_license_no" class="form-control @error('driver_license_no') is-invalid @enderror" placeholder="License No." value="{{ old('driver_license_no') }}">
                                @error('driver_license_no')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase opacity-75">License Expiry</label>
                                <input type="date" max="9999-12-31" name="driver_license_expiry" id="driver_license_expiry" class="form-control @error('driver_license_expiry') is-invalid @enderror" value="{{ old('driver_license_expiry') }}">
                                @error('driver_license_expiry')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Driver ID</label>
                                <input type="text" name="driver_driver_id" id="driver_driver_id" class="form-control" placeholder="Driver ID" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Driver Address</label>
                                <textarea name="driver_address" id="driver_address" class="form-control @error('driver_address') is-invalid @enderror" rows="2" placeholder="Full Address">{{ old('driver_address') }}</textarea>
                                @error('driver_address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Section: Reference & Invoice -->
                <div id="section-reference" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="section-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-reference" aria-expanded="{{ $openRef ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon icon-document">
                                <i class="bx bx-receipt"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Reference & Invoice</h5>
                                <p class="section-subtitle">Order, delivery, invoice & e-way bill details</p>
                            </div>
                        </div>
                        <i class="bx bx-chevron-down fs-4 collapse-chevron"></i>
                    </div>
                    <div id="collapse-reference" class="card-body p-4 collapse {{ $openRef ? 'show' : '' }}">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Order Number</label>
                                <input type="text" name="order_number" class="form-control" value="{{ old('order_number') }}" placeholder="Order ref no.">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Delivery Number</label>
                                <input type="text" name="delivery_number" class="form-control" value="{{ old('delivery_number') }}" placeholder="Delivery ref no.">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">From No.</label>
                                <input type="text" name="from_no" class="form-control" value="{{ old('from_no') }}" placeholder="From number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Invoice Number</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}" placeholder="Invoice no.">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Invoice Date</label>
                                <input type="date" max="9999-12-31" name="invoice_date" class="form-control" value="{{ old('invoice_date') }}">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">E-Way Bill No.</label>
                                <input type="text" name="eway_bill_no" class="form-control" value="{{ old('eway_bill_no') }}" placeholder="E-way bill number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Generation Date</label>
                                <input type="date" max="9999-12-31" name="generation_date" class="form-control" value="{{ old('generation_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Expiry Date</label>
                                <input type="date" max="9999-12-31" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">E-LR No.</label>
                                <input type="text" name="e_lr_no" class="form-control" value="{{ old('e_lr_no') }}" placeholder="E-LR number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Transit Mode</label>
                                <input type="text" name="mode" class="form-control" value="{{ old('mode') }}" placeholder="e.g. AC CORRUGATED SHEET">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">MN No.</label>
                                <input type="text" name="mn_no" class="form-control" value="{{ old('mn_no') }}" placeholder="MN number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Bill No.</label>
                                <input type="text" name="bill_no" class="form-control" value="{{ old('bill_no') }}" placeholder="Bill number">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Other Entry -->
                <div id="section-other-entry" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="section-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-other-entry" aria-expanded="{{ $openOther ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon icon-document">
                                <i class="bx bx-spreadsheet"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Other Entry</h5>
                                <p class="section-subtitle">Posting, PO, gate entry, GRN & shortage details</p>
                            </div>
                        </div>
                        <i class="bx bx-chevron-down fs-4 collapse-chevron"></i>
                    </div>
                    <div id="collapse-other-entry" class="card-body p-4 collapse {{ $openOther ? 'show' : '' }}">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Posting Date</label>
                                <input type="date" max="9999-12-31" name="posting_date" class="form-control" value="{{ old('posting_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Supplier No.</label>
                                <input type="text" name="supplier_no" class="form-control" value="{{ old('supplier_no') }}" placeholder="Supplier number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Depot Name</label>
                                <input type="text" name="depot_name" class="form-control" value="{{ old('depot_name') }}" placeholder="Depot name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">PO No.</label>
                                <input type="text" name="po_no" class="form-control" value="{{ old('po_no') }}" placeholder="PO number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">PO Item</label>
                                <input type="text" name="po_item" class="form-control" value="{{ old('po_item') }}" placeholder="PO item index">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Mat Doc</label>
                                <input type="text" name="mat_doc" class="form-control" value="{{ old('mat_doc') }}" placeholder="Material document">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Gate Entry No.</label>
                                <input type="text" name="gate_entry_no" class="form-control" value="{{ old('gate_entry_no') }}" placeholder="Gate entry number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Challan No.</label>
                                <input type="text" name="challan_no" class="form-control" value="{{ old('challan_no') }}" placeholder="Challan number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Challan Date</label>
                                <input type="date" max="9999-12-31" name="challan_date" class="form-control" value="{{ old('challan_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Transporter Code</label>
                                <input type="text" name="transporter_code" class="form-control" value="{{ old('transporter_code') }}" placeholder="Transporter code">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Transporter Name</label>
                                <input type="text" name="transporter_name" class="form-control" value="{{ old('transporter_name') }}" placeholder="Transporter name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Gate Out Date</label>
                                <input type="date" max="9999-12-31" name="gate_out_date" class="form-control" value="{{ old('gate_out_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Invoice Doc</label>
                                <input type="text" name="invoice_doc" class="form-control" value="{{ old('invoice_doc') }}" placeholder="Invoice doc">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Invoice Time</label>
                                <input type="time" name="invoice_time" class="form-control" value="{{ old('invoice_time') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">GRN No.</label>
                                <input type="text" name="grn_no" class="form-control" value="{{ old('grn_no') }}" placeholder="GRN number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">GRN Date</label>
                                <input type="date" max="9999-12-31" name="grn_date" class="form-control" value="{{ old('grn_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">GRN Time</label>
                                <input type="time" name="grn_time" class="form-control" value="{{ old('grn_time') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Challan Qty</label>
                                <input type="number" step="0.001" name="challan_qty" class="form-control" value="{{ old('challan_qty', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Final Wgt</label>
                                <input type="number" step="0.001" name="final_wgt" class="form-control" value="{{ old('final_wgt', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Recd Qty</label>
                                <input type="number" step="0.01" min="0" name="recd_qty" class="form-control" value="{{ old('recd_qty', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Material Name</label>
                                <input type="text" name="material_name" class="form-control" value="{{ old('material_name') }}" placeholder="Material name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Material No.</label>
                                <input type="text" name="material_no" class="form-control" value="{{ old('material_no') }}" placeholder="Material number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Billed Qty.</label>
                                <input type="number" step="0.01" min="0" name="billed_qty" class="form-control" value="{{ old('billed_qty', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Arrival Time</label>
                                <input type="text" name="arrival_time" class="form-control" value="{{ old('arrival_time') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Unloading Date</label>
                                <input type="date" max="9999-12-31" name="ul_date" class="form-control" value="{{ old('ul_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Unloading Rate</label>
                                <input type="number" step="0.01" name="ul_rate" class="form-control" value="{{ old('ul_rate', 0.00) }}">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Shortage GRN No.</label>
                                <input type="text" name="shortage_grn_no" class="form-control" value="{{ old('shortage_grn_no') }}" placeholder="Shortage GRN number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Shortage GRN Date</label>
                                <input type="date" max="9999-12-31" name="shortage_grn_date" class="form-control" value="{{ old('shortage_grn_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Short Qty</label>
                                <input type="number" step="0.01" min="0" name="short_qty" class="form-control" value="{{ old('short_qty', 0) }}">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Bags Loaded</label>
                                <input type="number" name="bag_ld" class="form-control" value="{{ old('bag_ld', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Bags Unloaded</label>
                                <input type="number" name="bag_ul" class="form-control" value="{{ old('bag_ul', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Bags Short</label>
                                <input type="number" name="bag_short" class="form-control" value="{{ old('bag_short', 0) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Rate/MT</label>
                                <input type="number" step="0.01" name="rate_mt" class="form-control" value="{{ old('rate_mt', 0.00) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Qty/MT</label>
                                <input type="number" step="0.01" name="qty_mt" class="form-control" value="{{ old('qty_mt', 0.00) }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Description of Services</label>
                                <textarea name="description_services" class="form-control" rows="2" placeholder="Description of services...">{{ old('description_services') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Charges & Payment (Moved from Sidebar) -->
                <div id="section-charges" class="card shadow-sm border-0 mb-4 section-card">
                    <div class="card-body p-4">
                        <div class="section-header">
                            <div class="section-icon icon-charges">
                                <i class="bx bx-wallet"></i>
                            </div>
                            <div>
                                <h5 class="section-title">Charges & Payment</h5>
                                <p class="section-subtitle">Freight & total calculation</p>
                            </div>
                        </div>

                        <div class="row g-4 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Freight Charges</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" name="freight_charges" id="freight_charges" class="form-control form-control-lg fw-bold" value="{{ old('freight_charges', '0.00') }}">
                                </div>
                            </div>


                            <div class="col-md-4">
                                <label class="form-label fw-bold">Other Charges</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control form-control-lg fw-bold" value="{{ old('other_charges', '0.00') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Bilty Commission</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" name="bilty_commission" id="bilty_commission" class="form-control fw-bold" value="{{ old('bilty_commission', '0.00') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Advance Amount</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" name="advance_amount" id="advance_amount" class="form-control fw-bold" value="{{ old('advance_amount', '0.00') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Damage Amount</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" name="damage_amount" id="damage_amount" class="form-control fw-bold" value="{{ old('damage_amount', '0.00') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Shortage Amount</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" name="shortage_amount" id="shortage_amount" class="form-control fw-bold" value="{{ old('shortage_amount', '0.00') }}">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Remark</label>
                                <textarea name="remark" class="form-control" rows="2" placeholder="Any notes or remarks...">{{ old('remark') }}</textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="total-summary-box p-4 rounded-4 bg-primary text-white shadow-lg d-flex flex-column flex-md-row justify-content-between align-items-center">
                                    <div class="mb-3 mb-md-0 text-center text-md-start">
                                        <span class="d-block opacity-75 small text-uppercase fw-bold text-white">Total Amount</span>
                                        <h1 class="mb-0 fw-black text-white display-5" id="total_amount_view">₹ 0.00</h1>
                                    </div>
                                    <div class="d-flex gap-3 flex-column align-items-end">
                                        <div class="text-end">
                                            <span class="d-block opacity-75 small text-uppercase fw-bold text-white">Remaining Amount</span>
                                            <h3 class="mb-0 fw-black text-white" id="remaining_amount_view">₹ 0.00</h3>
                                        </div>
                                        <button type="submit" class="btn btn-generate btn-lg">
                                            <i class="bx bx-check-double me-1"></i> GENERATE BILTY
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="total_amount" id="total_amount" value="{{ old('total_amount', '0.00') }}">
                                <input type="hidden" name="remaining_amount" id="remaining_amount" value="{{ old('remaining_amount', '0.00') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Removed Sidebar Summary Column -->
         </div>
     </form>
 </div>

<!-- Modals -->
@include('admin.transport.bulties.partials.modals')

    <!-- Add Consignor Modal -->
    <div class="modal fade" id="addConsignorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-premium py-3">
                    <h5 class="modal-title text-white fw-bold"><i class="bx bx-user-plus me-2"></i>Quick Add Consignor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddConsignorForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Sender full name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Contact number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">GSTIN</label>
                                <input type="text" name="gstin" class="form-control" placeholder="GSTIN (optional)">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea>
                            </div>
                        </div>
                        <div id="consignorAddError" class="alert alert-danger d-none mt-3"></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Consignor</button>
                    </div>
                 </form>
             </div>
         </div>
     </div>

    <!-- Add Consignee Modal -->
    <div class="modal fade" id="addConsigneeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-premium py-3">
                    <h5 class="modal-title text-white fw-bold"><i class="bx bx-user-check me-2"></i>Quick Add Consignee</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddConsigneeForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Receiver full name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Contact number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">GSTIN</label>
                                <input type="text" name="gstin" class="form-control" placeholder="GSTIN (optional)">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea>
                            </div>
                        </div>
                        <div id="consigneeAddError" class="alert alert-danger d-none mt-3"></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Consignee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Item Master Modal -->
    <div class="modal fade" id="addItemMasterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-premium py-3">
                    <h5 class="modal-title text-white fw-bold"><i class="bx bx-cube me-2"></i>Quick Add Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddItemMasterForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter item name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                            </div>
                        </div>
                        <div id="itemAddError" class="alert alert-danger d-none mt-3"></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-premium py-3">
                    <h5 class="modal-title text-white fw-bold" style="font-family:'Inter',sans-serif;"><i class="bx bx-bus me-2"></i>Quick Add Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddVehicleForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle Number <span class="text-danger">*</span></label>
                                <input type="text" name="vehicle_number" class="form-control" placeholder="GJ 01 AB 1234" >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle Type</label>
                                <input type="text" name="vehicle_type" class="form-control" placeholder="Truck, Trailer, etc.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Make / Model</label>
                                <input type="text" name="make_model" class="form-control" placeholder="Tata 2518">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Capacity (Tons)</label>
                                <input type="number" step="0.01" name="capacity_tons" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Owner Name</label>
                                <input type="text" name="owner_name" class="form-control" placeholder="Owner Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Owner Phone</label>
                                <input type="text" name="owner_phone" class="form-control" placeholder="Owner Contact">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Insurance Expiry</label>
                                <input type="date" max="9999-12-31" name="insurance_expiry" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fitness Expiry</label>
                                <input type="date" max="9999-12-31" name="fitness_expiry" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Permit Expiry</label>
                                <input type="date" max="9999-12-31" name="permit_expiry" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Driver Modal -->
    <div class="modal fade" id="addDriverModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-premium py-3">
                    <h5 class="modal-title text-white fw-bold" style="font-family:'Inter',sans-serif;"><i class="bx bx-user me-2"></i>Quick Add Driver</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddDriverForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Driver Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Driver ID</label>
                                <input type="text" name="driver_id" class="form-control" placeholder="Driver ID">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" placeholder="Mobile Number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">License Number</label>
                                <input type="text" name="license_number" class="form-control" placeholder="License No.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">License Expiry</label>
                                <input type="date" max="9999-12-31" name="license_expiry" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Full Address"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Missing LR Modal -->
    <div class="modal fade" id="missingLRModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Missing LR Numbers</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Select a missing LR number or use the next sequence:</p>
                    <div id="missingLRList"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('style')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/admin/css/bilty-create.css') }}" />
<style>
    .section-card > .section-header {
        padding: 1.25rem 1.5rem;
    }
    .section-header[role="button"] {
        cursor: pointer;
        user-select: none;
    }
    .section-header[role="button"]:hover {
        opacity: 0.8;
    }
    .collapse-chevron {
        transition: transform 0.3s ease;
    }
    .collapse-chevron.rotated {
        transform: rotate(180deg);
    }
    button.btn-close.btn-close-white {
        display: none;
    }
    .suggestion-item.active {
        background-color: #e9ecef;
    }
</style>
@endsection


@section('script')
<script>
    $(document).ready(function() {
        function getCompanyBranchData() {
            const companyId = $('[name="company_id"]').val();
            const branchId = $('[name="branch_id"]').val();
            if (!companyId) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select Company and Branch first.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                return null;
            }
            return { company_id: companyId, branch_id: branchId || '' };
        }

        function getFieldFeedback($el) {
            const $fieldWrapper = $el.closest('[class*="col-"]');
            let $feedback = $fieldWrapper.children('.invalid-feedback').first();

            if (!$feedback.length) {
                $feedback = $fieldWrapper.find('> .position-relative + .invalid-feedback, > .input-group + .invalid-feedback').first();
            }

            if (!$feedback.length) {
                $feedback = $el.siblings('.invalid-feedback').first();
            }

            if (!$feedback.length) {
                $feedback = $('<div class="invalid-feedback">This field is required</div>');
                $fieldWrapper.append($feedback);
            }

            return $feedback;
        }

        function setFieldInvalid($el, invalid) {
            const $feedback = getFieldFeedback($el);
            $el.toggleClass('is-invalid', invalid);
            $feedback.toggleClass('d-block', invalid).toggle(invalid);
        }

        // Form Validation Logic
        $('#biltyForm').on('submit', function(e) {
            let isValid = true;
            let firstInvalid = null;

            $(this).find('[required]').each(function() {
                const $el = $(this);
                if (!$el.val() || $el.val() === "") {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = $el;
                    setFieldInvalid($el, true);
                } else {
                    setFieldInvalid($el, false);
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalid) {
                    firstInvalid.closest('.collapse').collapse('show');
                    $('html, body').animate({
                        scrollTop: firstInvalid.offset().top - 150
                    }, 500);
                }
            }
        });

        // Real-time validation feedback
        $('.bilty-form [required]').on('change input', function() {
            if ($(this).val()) {
                setFieldInvalid($(this), false);
            }
        });

        // Init controls



            // Vehicle Details Fetching & Autocomplete
            $('#vehicle_number').on('input', function() {
                const term = $(this).val();
                if (term.length < 2) {
                    $('#vehicle_suggestions').hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.masters.vehicles.search') }}",
                    method: 'GET',
                    data: { term: term },
                    success: function(vehicles) {
                        if (vehicles.length > 0) {
                            let html = '';
                            vehicles.forEach(v => {
                                html += `<div class="suggestion-item" data-id="${v.id}" data-json='${JSON.stringify(v).replace(/'/g, "&apos;")}'>
                                            <span class="v-num">${v.vehicle_number}</span>
                                            <span class="v-type">${v.vehicle_type || 'N/A'}</span>
                                         </div>`;
                            });
                            $('#vehicle_suggestions').html(html).show();
                        } else {
                            $('#vehicle_suggestions').hide().empty();
                        }
                    }
                });
            });

            $(document).on('click', '#vehicle_suggestions .suggestion-item', function() {
                const v = $(this).data('json');
                populateVehicle(v);
                $('#vehicle_suggestions').hide().empty();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('#vehicle_suggestions').hide();
                }
            });

            function populateVehicle(v) {
                $('#vehicle_number').val(v.vehicle_number).addClass('is-valid');
                $('#vehicle_id').val(v.id);
                $('#vehicle_type').val(v.vehicle_type);
                $('#make_model').val(v.make_model);
                $('#capacity_tons').val(v.capacity_tons);
                $('#owner_name').val(v.owner_name);
                $('#owner_phone').val(v.owner_phone);
                
                // Format dates for <input type="date" max="9999-12-31"> (YYYY-MM-DD)
                if (v.insurance_expiry) $('#insurance_expiry').val(v.insurance_expiry.split('T')[0]);
                if (v.fitness_expiry) $('#fitness_expiry').val(v.fitness_expiry.split('T')[0]);
                if (v.permit_expiry) $('#permit_expiry').val(v.permit_expiry.split('T')[0]);
            }

            // Keep the blur logic for manual full number entry if not selected from dropdown
            $('#vehicle_number').on('blur', function() {
                // Wait slightly for click event on suggestion to fire first
                setTimeout(() => {
                    if ($('#vehicle_suggestions').is(':visible')) return;
                    
                    const vehicleNum = $(this).val();
                    if (vehicleNum.length < 3 || $('#vehicle_id').val() !== '') return;

                    $('#vehicle_loader').removeClass('d-none');
                    $.ajax({
                        url: "{{ route('admin.masters.vehicles.fetch-details') }}",
                        method: 'GET',
                        data: { vehicle_number: vehicleNum },
                        success: function(response) {
                            $('#vehicle_loader').addClass('d-none');
                            if (response.success) {
                                if (response.in_use) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Vehicle In Use',
                                        text: 'This vehicle already has an open trip on another bilty. Close the trip first before using it again.',
                                        confirmButtonColor: '#ef4444'
                                    });
                                    $('#vehicle_number').val('').removeClass('is-valid');
                                    return;
                                }
                                populateVehicle(response.vehicle);
                            }
                        },
                        error: function() { $('#vehicle_loader').addClass('d-none'); }
                    });
                }, 200);
            });

            // Quick Add Vehicle
            $('#quickAddVehicleForm').on('submit', function(e) {
                e.preventDefault();
                const cbData = getCompanyBranchData();
                if (!cbData) return;
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: "{{ route('admin.masters.vehicles.quick-store') }}",
                    method: 'POST',
                    data: $(this).serialize() + '&' + $.param(cbData),
                    success: function(response) {
                        btn.prop('disabled', false).html('Save Vehicle');
                        if (response.success) {
                            $('#addVehicleModal').modal('hide');
                            populateVehicle(response.vehicle);
                            $('#quickAddVehicleForm')[0].reset();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Vehicle added and selected successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('Save Vehicle');
                        let errors = xhr.responseJSON.errors;
                        let errorMsg = '';
                        for (let key in errors) {
                            errorMsg += errors[key][0] + '\n';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMsg || 'Something went wrong.'
                        });
                    }
                });
            });

            // Driver Autocomplete
            $('#driver_name').on('input', function() {
                const term = $(this).val();
                if (term.length < 2) {
                    $('#driver_suggestions').hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.masters.drivers.search') }}",
                    method: 'GET',
                    data: { term: term },
                    success: function(drivers) {
                        if (drivers.length > 0) {
                            let html = '';
                            drivers.forEach(d => {
                                html += `<div class="suggestion-item" data-id="${d.id}" data-json='${JSON.stringify(d).replace(/'/g, "&apos;")}'>
                                            <span class="v-num">${d.name}</span>
                                            <span class="v-type">${d.driver_id ? d.driver_id : (d.phone || 'N/A')}</span>
                                         </div>`;
                            });
                            $('#driver_suggestions').html(html).show();
                        } else {
                            $('#driver_suggestions').hide().empty();
                        }
                    }
                });
            });

            $(document).on('click', '#driver_suggestions .suggestion-item, #driver_mobile_suggestions .suggestion-item', function() {
                const d = $(this).data('json');
                populateDriver(d);
                $('#driver_suggestions, #driver_mobile_suggestions').hide().empty();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('#driver_suggestions, #driver_mobile_suggestions').hide();
                }
            });

            function populateDriver(d) {
                $('#driver_name').val(d.name).addClass('is-valid');
                $('#driver_id').val(d.id);
                $('#driver_mobile').val(d.phone);
                $('#driver_license_no').val(d.license_number);
                if (d.license_expiry) $('#driver_license_expiry').val(d.license_expiry.split('T')[0]);
                $('#driver_driver_id').val(d.driver_id);
                $('#driver_address').val(d.address);
            }

            $('#driver_name').on('blur', function() {
                setTimeout(() => {
                    if ($('#driver_suggestions').is(':visible')) return;

                    const driverName = $(this).val();
                    if (driverName.length < 2 || $('#driver_id').val() !== '') return;

                    $('#driver_loader').removeClass('d-none');
                    $.ajax({
                        url: "{{ route('admin.masters.drivers.fetch-details') }}",
                        method: 'GET',
                        data: { driver_name: driverName },
                        success: function(response) {
                            $('#driver_loader').addClass('d-none');
                            if (response.success) {
                                populateDriver(response.driver);
                            }
                        },
                        error: function() { $('#driver_loader').addClass('d-none'); }
                    });
                }, 200);
            });

            // Driver Mobile Search
            $('#driver_mobile').on('input', function() {
                const term = $(this).val();
                if (term.length < 2) {
                    $('#driver_mobile_suggestions').hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.masters.drivers.search') }}",
                    method: 'GET',
                    data: { term: term },
                    success: function(drivers) {
                        if (drivers.length > 0) {
                            let html = '';
                            drivers.forEach(d => {
                                html += `<div class="suggestion-item" data-id="${d.id}" data-json='${JSON.stringify(d).replace(/'/g, "&apos;")}'>
                                            <span class="v-num">${d.name}</span>
                                            <span class="v-type">${d.driver_id ? d.driver_id : (d.phone || 'N/A')}</span>
                                         </div>`;
                            });
                            $('#driver_mobile_suggestions').html(html).show();
                        } else {
                            $('#driver_mobile_suggestions').hide().empty();
                        }
                    }
                });
            });

            $('#driver_mobile').on('blur', function() {
                setTimeout(() => {
                    if ($('#driver_mobile_suggestions').is(':visible')) return;

                    const mobile = $(this).val();
                    if (mobile.length < 2 || $('#driver_id').val() !== '') return;

                    $('#driver_mobile_loader').removeClass('d-none');
                    $.ajax({
                        url: "{{ route('admin.masters.drivers.fetch-details') }}",
                        method: 'GET',
                        data: { driver_name: mobile },
                        success: function(response) {
                            $('#driver_mobile_loader').addClass('d-none');
                            if (response.success) {
                                populateDriver(response.driver);
                            }
                        },
                        error: function() { $('#driver_mobile_loader').addClass('d-none'); }
                    });
                }, 200);
            });
            $('#consignor_name').on('input', function() {
                const term = $(this).val();
                if (term.length < 2) {
                    $('#consignor_suggestions').hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.masters.consignors.search') }}",
                    method: 'GET',
                    data: { term: term },
                    success: function(consignors) {
                        if (consignors.length > 0) {
                            let html = '';
                            consignors.forEach(c => {
                                html += `<div class="suggestion-item" data-id="${c.id}" data-json='${JSON.stringify(c).replace(/'/g, "&apos;")}'>
                                            <span class="v-num">${c.name}</span>
                                            <span class="v-type">${c.phone || 'N/A'}</span>
                                         </div>`;
                            });
                            $('#consignor_suggestions').html(html).show();
                        } else {
                            $('#consignor_suggestions').hide().empty();
                        }
                    }
                });
            });

            $(document).on('click', '#consignor_suggestions .suggestion-item', function() {
                const c = $(this).data('json');
                populateConsignor(c);
                $('#consignor_suggestions').hide().empty();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('#consignor_suggestions').hide();
                }
            });

            function populateConsignor(c) {
                $('#consignor_name').val(c.name).addClass('is-valid');
                $('#consignor_id').val(c.id);
                $('#consignor_phone').val(c.phone);
                $('#consignor_gstin').val(c.gstin);
                $('#consignor_address').val(c.address);
            }

            $('#consignor_name').on('blur', function() {
                setTimeout(() => {
                    if ($('#consignor_suggestions').is(':visible')) return;

                    const consignorName = $(this).val();
                    if (consignorName.length < 2 || $('#consignor_id').val() !== '') return;

                    // If user typed a phone number, try to find by phone
                    if (/^\d{10,}$/.test(consignorName)) {
                        $('#consignor_loader').removeClass('d-none');
                        $.ajax({
                            url: "{{ route('admin.masters.consignors.search') }}",
                            method: 'GET',
                            data: { term: consignorName },
                            success: function(consignors) {
                                $('#consignor_loader').addClass('d-none');
                                if (consignors.length === 1 && consignors[0].phone === consignorName) {
                                    populateConsignor(consignors[0]);
                                }
                            },
                            error: function() { $('#consignor_loader').addClass('d-none'); }
                        });
                    }
                }, 200);
            });

            // Consignee Autocomplete
            $('#consignee_name').on('input', function() {
                const term = $(this).val();
                if (term.length < 2) {
                    $('#consignee_suggestions').hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.masters.consignees.search') }}",
                    method: 'GET',
                    data: { term: term },
                    success: function(consignees) {
                        if (consignees.length > 0) {
                            let html = '';
                            consignees.forEach(c => {
                                html += `<div class="suggestion-item" data-id="${c.id}" data-json='${JSON.stringify(c).replace(/'/g, "&apos;")}'>
                                            <span class="v-num">${c.name}</span>
                                            <span class="v-type">${c.phone || 'N/A'}</span>
                                         </div>`;
                            });
                            $('#consignee_suggestions').html(html).show();
                        } else {
                            $('#consignee_suggestions').hide().empty();
                        }
                    }
                });
            });

            $(document).on('click', '#consignee_suggestions .suggestion-item', function() {
                const c = $(this).data('json');
                populateConsignee(c);
                $('#consignee_suggestions').hide().empty();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('#consignee_suggestions').hide();
                }
            });

            function populateConsignee(c) {
                $('#consignee_name').val(c.name).addClass('is-valid');
                $('#consignee_id').val(c.id);
                $('#consignee_phone').val(c.phone);
                $('#consignee_gstin').val(c.gstin);
                $('#consignee_address').val(c.address);
            }

            $('#consignee_name').on('blur', function() {
                setTimeout(() => {
                    if ($('#consignee_suggestions').is(':visible')) return;

                    const consigneeName = $(this).val();
                    if (consigneeName.length < 2 || $('#consignee_id').val() !== '') return;

                    if (/^\d{10,}$/.test(consigneeName)) {
                        $('#consignee_loader').removeClass('d-none');
                        $.ajax({
                            url: "{{ route('admin.masters.consignees.search') }}",
                            method: 'GET',
                            data: { term: consigneeName },
                            success: function(consignees) {
                                $('#consignee_loader').addClass('d-none');
                                if (consignees.length === 1 && consignees[0].phone === consigneeName) {
                                    populateConsignee(consignees[0]);
                                }
                            },
                            error: function() { $('#consignee_loader').addClass('d-none'); }
                        });
                    }
                }, 200);
            });

            // Item Autocomplete
            $(document).on('input', '.item-name', function() {
                const $input = $(this);
                const $container = $input.closest('.position-relative');
                const $suggestions = $container.find('.item-suggestions');
                const term = $input.val();
                $container.find('.item-id').val('');
                if (term.length < 2) {
                    $suggestions.hide().empty();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.masters.items.search') }}",
                    method: 'GET',
                    data: { term: term },
                    success: function(items) {
                        if (items.length > 0) {
                            let html = '';
                            items.forEach(item => {
                                html += `<div class="suggestion-item" data-id="${item.id}" data-json='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                                            <span class="v-num">${item.name}</span>
                                         </div>`;
                            });
                            $suggestions.html(html).show();
                        } else {
                            $suggestions.hide().empty();
                        }
                    }
                });
            });

            $(document).on('click', '.item-suggestions .suggestion-item', function() {
                const item = $(this).data('json');
                const $container = $(this).closest('.position-relative');
                $container.find('.item-name').val(item.name).addClass('is-valid');
                $container.find('.item-id').val(item.id);
                $container.find('.item-suggestions').hide().empty();
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('.item-row .position-relative').length === 0) {
                    $('.item-suggestions').hide();
                }
            });

            // Quick Add Driver
            $('#quickAddDriverForm').on('submit', function(e) {
                e.preventDefault();
                const cbData = getCompanyBranchData();
                if (!cbData) return;
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: "{{ route('admin.masters.drivers.quick-store') }}",
                    method: 'POST',
                    data: $(this).serialize() + '&' + $.param(cbData),
                    success: function(response) {
                        btn.prop('disabled', false).html('Save Driver');
                        if (response.success) {
                            $('#addDriverModal').modal('hide');
                            populateDriver(response.driver);
                            $('#quickAddDriverForm')[0].reset();

                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Driver added and selected successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('Save Driver');
                        let errors = xhr.responseJSON.errors;
                        let errorMsg = '';
                        for (let key in errors) {
                            errorMsg += errors[key][0] + '\n';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMsg || 'Something went wrong.'
                        });
                    }
                });
            });

            // Initial Calculations
        function runCalculations() {
            const freight = parseFloat($('#freight_charges').val()) || 0;
            const other = parseFloat($('#other_charges').val()) || 0;
            const damage = parseFloat($('#damage_amount').val()) || 0;
            const shortage = parseFloat($('#shortage_amount').val()) || 0;
            
            const total = freight + other - damage - shortage;
            const advance = parseFloat($('#advance_amount').val()) || 0;
            const remaining = total - advance;
            
            $('#total_amount').val(total.toFixed(2));
            $('#total_amount_view').text('₹ ' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

            $('#remaining_amount').val(remaining.toFixed(2));
            $('#remaining_amount_view').text('₹ ' + remaining.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
        }

        $('#freight_charges, #other_charges, #advance_amount, #damage_amount, #shortage_amount').on('input change', runCalculations);
        runCalculations();

        // Branch Logic
        $('#company_id').change(function() {
            var companyId = $(this).val();
            var $branchSelect = $('#branch_id');
            if (companyId) {
                $branchSelect.prop('disabled', true).html('<option value="">Syncing...</option>');
                $.ajax({
                    url: '/admin/users/get-branches/' + companyId,
                    type: 'GET',
                    success: function(data) {
                        $branchSelect.prop('disabled', false).empty().append('<option value="">Select Branch</option>');
                        $.each(data, function(key, value) {
                            $branchSelect.append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                        var oldBranch = '{{ old('branch_id') }}';
                        if (oldBranch) {
                            $branchSelect.val(oldBranch);
                        }
                        $branchSelect.trigger('change');
                    }
                });
            } else {
                $branchSelect.prop('disabled', false).empty().append('<option value="">Select Branch</option>').trigger('change');
            }
        });

        // Trigger company change on page load if company_id is selected
        if ($('#company_id').val()) {
            $('#company_id').trigger('change');
        }

        // Update LR number on branch change
        $(document).on('change', '#branch_id', function() {
            const branchId = $(this).val();
            if (branchId) {
                $.get('{{ url("admin/transport/bulties/next-lr") }}/' + branchId, function(res) {
                    if (res.missing && res.missing.length > 0) {
                        let html = '<div class="list-group" style="max-height:300px;overflow-y:auto;">';
                        res.missing.forEach(function(lr) {
                            html += '<a href="#" class="list-group-item list-group-item-action lr-option" data-lr="' + lr + '">' + lr + ' <span class="text-muted">(Missing)</span></a>';
                        });
                        html += '<a href="#" class="list-group-item list-group-item-action lr-option" data-lr="' + res.next + '">' + res.next + ' <span class="text-muted">(Next)</span></a>';
                        html += '</div>';
                        $('#missingLRList').html(html);
                        $('#missingLRModal').modal('show');
                    } else {
                        $('[name="lr_no"]').val(res.next);
                    }
                });
            }
        });

        $(document).on('click', '.lr-option', function(e) {
            e.preventDefault();
            $('[name="lr_no"]').val($(this).data('lr'));
            $('#missingLRModal').modal('hide');
        });

        // Select2 AJAX City Search Initialization
        function initCityAjaxSelect2() {
            $('.select2-ajax-city').each(function() {
                var $el = $(this);
                if ($el.data('select2')) return;
                var placeholderText = $el.data('placeholder') || ($el.find('option[value=""]').text() || 'Select City');
                $el.select2({
                    placeholder: {
                        id: '',
                        text: placeholderText
                    },
                    allowClear: !$el.prop('required'),
                    width: '100%',
                    ajax: {
                        url: '{{ route("admin.masters.city.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 0
                });
            });
        }
        initCityAjaxSelect2();

        let targetCitySelect = null;
        $(document).on('click', '[data-bs-target="#addCityModal"]', function() {
            targetCitySelect = $(this).closest('.col-md-5').find('select');
        });

        // Quick Add City
        $('#quickAddCityForm').on('submit', function(e) {
            e.preventDefault();
            const cbData = getCompanyBranchData();
            if (!cbData) return;
            var $btn = $('#saveCityBtn');
            var $error = $('#cityAddError');
            $error.addClass('d-none');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            
            $.ajax({
                url: '{{ route("admin.masters.city.quick-store") }}',
                type: 'POST',
                data: $(this).serialize() + '&' + $.param(cbData),
                success: function(res) {
                    var optionText = res.name + ' (' + res.state + ')';
                    var newOptionFrom = new Option(optionText, res.id, false, false);
                    var newOptionTo = new Option(optionText, res.id, false, false);
                    
                    $('select[name="from_city"]').append(newOptionFrom);
                    $('select[name="to_city"]').append(newOptionTo);

                    if (targetCitySelect && targetCitySelect.length) {
                        targetCitySelect.val(res.id).trigger('change');
                    } else {
                        $('select[name="from_city"]').val(res.id).trigger('change');
                    }
                    
                    $('#addCityModal').modal('hide');
                    $('#quickAddCityForm')[0].reset();
                    Swal.fire({ icon: 'success', title: 'Success!', text: 'City added and selected successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('<br>') : 'Error.';
                    $error.removeClass('d-none').html(msg);
                },
                complete: function() { $btn.prop('disabled', false).text('Save City'); }
            });
        });

        // ===== Quick Add Consignor =====
        $('#quickAddConsignorForm').on('submit', function(e) {
            e.preventDefault();
            const cbData = getCompanyBranchData();
            if (!cbData) return;
            const $btn = $(this).find('[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            const $error = $('#consignorAddError').addClass('d-none');
            $.ajax({
                url: '{{ route("admin.masters.consignors.quick-store") }}',
                method: 'POST',
                data: $(this).serialize() + '&' + $.param(cbData),
                success: function(res) {
                    $('#addConsignorModal').modal('hide');
                    $('#quickAddConsignorForm')[0].reset();
                    populateConsignor(res);
                    Swal.fire({ icon: 'success', title: 'Success!', text: res.name + ' added and selected successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.[Object.keys(xhr.responseJSON?.errors || {})[0]]?.[0] || 'Failed to save consignor.';
                    $error.removeClass('d-none').html(msg);
                },
                complete: function() { $btn.prop('disabled', false).text('Save Consignor'); }
            });
        });

        // ===== Quick Add Consignee =====
        $('#quickAddConsigneeForm').on('submit', function(e) {
            e.preventDefault();
            const cbData = getCompanyBranchData();
            if (!cbData) return;
            const $btn = $(this).find('[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            const $error = $('#consigneeAddError').addClass('d-none');
            $.ajax({
                url: '{{ route("admin.masters.consignees.quick-store") }}',
                method: 'POST',
                data: $(this).serialize() + '&' + $.param(cbData),
                success: function(res) {
                    $('#addConsigneeModal').modal('hide');
                    $('#quickAddConsigneeForm')[0].reset();
                    populateConsignee(res);
                    Swal.fire({ icon: 'success', title: 'Success!', text: res.name + ' added and selected successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.[Object.keys(xhr.responseJSON?.errors || {})[0]]?.[0] || 'Failed to save consignee.';
                    $error.removeClass('d-none').html(msg);
                },
                complete: function() { $btn.prop('disabled', false).text('Save Consignee'); }
            });
        });

        // ===== Quick Add Item =====
        $('#quickAddItemMasterForm').on('submit', function(e) {
            e.preventDefault();
            const cbData = getCompanyBranchData();
            if (!cbData) return;
            const $btn = $(this).find('[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            const $error = $('#itemAddError').addClass('d-none');
            $.ajax({
                url: '{{ route("admin.masters.items.quick-store") }}',
                method: 'POST',
                data: $(this).serialize() + '&' + $.param(cbData),
                success: function(res) {
                    $('#addItemMasterModal').modal('hide');
                    $('#quickAddItemMasterForm')[0].reset();
                    const $lastRow = $('#itemsContainer .item-row:last');
                    $lastRow.find('.item-name').val(res.name);
                    $lastRow.find('.item-id').val(res.id);
                    Swal.fire({ icon: 'success', title: 'Success!', text: res.name + ' added and selected successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.[Object.keys(xhr.responseJSON?.errors || {})[0]]?.[0] || 'Failed to save item.';
                    $error.removeClass('d-none').html(msg);
                },
                complete: function() { $btn.prop('disabled', false).text('Save Item'); }
            });
        });

        // Packaging & Unit data from DB
        const packagingOptions = @json($packagings->pluck('name'));
        const unitOptions = @json($units->pluck('name'));

        function buildOptions(options, selected) {
            let html = '<option value="">Select</option>';
            options.forEach(function(o) {
                const sel = o === selected ? ' selected' : '';
                html += '<option value="' + o + '"' + sel + '>' + o + '</option>';
            });
            return html;
        }

        $(document).on('change', '.item-unit', function() {
            const unit = $(this).val() || 'kg';
            $(this).closest('.item-row').find('.freight-unit').text(unit.toLowerCase());
        });

        // ===== Items Add More Functionality =====
        function recalcItems() {
            let totalArticles = 0, totalWeight = 0, totalAmt = 0, rowCount = 0;
            $('#itemsContainer .item-row').each(function() {
                const art = parseFloat($(this).find('.item-articles').val()) || 0;
                const freight = parseFloat($(this).find('.item-freight').val()) || 0;
                const weight = parseFloat($(this).find('.item-weight').val()) || 0;
                const amt = weight * freight;
                $(this).find('.item-amount').val(amt.toFixed(2));
                totalArticles += art;
                totalWeight += weight;
                totalAmt += amt;
                rowCount++;
            });
            $('#totalItemsCount').text(rowCount);
            $('#totalArticles').text(totalArticles);
            $('#totalWeight').text(totalWeight.toFixed(2));
            $('input[name="qty_mt"]').val(totalWeight.toFixed(2));
            $('#totalAmount').text('₹ ' + totalAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#freight_charges').val(totalAmt.toFixed(2));
            runCalculations();
        }

        $('#addItemRow').on('click', function() {
            const idx = $('#itemsContainer .item-row').length;
            const newCard = `
                <div class="item-card item-row" data-index="${idx}">
                    <div class="item-card-header">
                        <span class="item-number">Item #<span class="row-num">${idx + 1}</span></span>
                        <button type="button" class="btn btn-sm text-danger border-0 remove-item"><i class="bx bx-trash"></i> Remove</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label fw-bold small text-uppercase opacity-75 mb-0">Item Name</label>
                                <button type="button" class="btn btn-pill-add btn-xs" data-bs-toggle="modal" data-bs-target="#addItemMasterModal"><i class="bx bx-plus-circle me-1"></i> Add New</button>
                            </div>
                            <div class="position-relative">
                                <input type="text" name="items[${idx}][item_name]" class="form-control item-name pe-5" placeholder="Search item..." autocomplete="off">
                                <input type="hidden" name="items[${idx}][item_id]" class="item-id">
                                <i class="bx bx-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-5"></i>
                                <div class="item-suggestions vehicle-suggestions"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase opacity-75">Packaging Type</label>
                            <select name="items[${idx}][packaging_type]" class="form-select item-packaging">
                                ${buildOptions(packagingOptions)}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase opacity-75">No of Articles</label>
                            <input type="number" step="1" min="0" name="items[${idx}][articles]" class="form-control item-articles" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase opacity-75">Total Weight</label>
                            <input type="number" step="0.01" min="0" name="items[${idx}][weight]" class="form-control item-weight" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase opacity-75">Unit</label>
                            <select name="items[${idx}][unit]" class="form-select item-unit">
                                ${buildOptions(unitOptions)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase opacity-75 freight-label">Freight per <span class="freight-unit">mt</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="items[${idx}][freight_per_mt]" class="form-control item-freight" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase opacity-75">Amount</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text">₹</span>
                                <input type="text" name="items[${idx}][amount]" class="form-control item-amount" value="0.00" readonly style="font-weight:700;color:var(--bilty-primary);background:#f8fafc;">
                            </div>
                        </div>
                    </div>`;
            $('#itemsContainer').append(newCard);
            recalcItems();
            if ($('#itemsContainer .item-row').length > 1) {
                $('#itemsContainer .item-row:first .remove-item').prop('disabled', false).css('opacity', '1');
            }
        });

        $(document).on('click', '.remove-item', function() {
            if ($('#itemsContainer .item-row').length <= 1) return;
            $(this).closest('.item-row').remove();
            $('#itemsContainer .item-row').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('.row-num').text(i + 1);
                $(this).find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + i + ']'));
                    }
                });
            });
            recalcItems();
            if ($('#itemsContainer .item-row').length <= 1) {
                $('#itemsContainer .item-row:first .remove-item').prop('disabled', true).css('opacity', '0.3');
            }
        });

        $(document).on('input', '.item-weight, .item-freight, .item-articles', function() {
            recalcItems();
        });

        $(document).on('change', '.item-unit', function() {
            const unit = $(this).val() || 'kg';
            $(this).closest('.item-row').find('.freight-unit').text(unit);
        });

        $('.item-unit').each(function() {
            const unit = $(this).val() || 'kg';
            $(this).closest('.item-row').find('.freight-unit').text(unit);
        });

        // Open sections with validation errors
        $('.is-invalid').closest('.collapse').collapse('show');

        // Collapse chevron rotation
        $('.collapse-chevron').addClass('rotated');
        $(document).on('show.bs.collapse', '.collapse', function () {
            $(this).prevAll('.section-header').first().find('.collapse-chevron').removeClass('rotated');
        });
        $(document).on('hide.bs.collapse', '.collapse', function () {
            $(this).prevAll('.section-header').first().find('.collapse-chevron').addClass('rotated');
        });

        // Keyboard navigation for suggestion dropdowns
        function setupSuggestionKeyboard($input, $suggestions) {
            $input.on('keydown', function(e) {
                const $items = $suggestions.find('.suggestion-item');
                if (!$items.length) return;

                const $active = $items.filter('.active');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if ($active.length) {
                        const $next = $active.nextAll('.suggestion-item').first();
                        if ($next.length) {
                            $active.removeClass('active');
                            $next.addClass('active');
                        }
                    } else {
                        $items.first().addClass('active');
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if ($active.length) {
                        const $prev = $active.prevAll('.suggestion-item').first();
                        if ($prev.length) {
                            $active.removeClass('active');
                            $prev.addClass('active');
                        }
                    } else {
                        $items.last().addClass('active');
                    }
                } else if (e.key === 'Enter') {
                    if ($active.length) {
                        e.preventDefault();
                        $active.trigger('click');
                    }
                }
            });
        }

        setupSuggestionKeyboard($('#vehicle_number'), $('#vehicle_suggestions'));
        setupSuggestionKeyboard($('#driver_name'), $('#driver_suggestions'));
        setupSuggestionKeyboard($('#driver_mobile'), $('#driver_mobile_suggestions'));
        setupSuggestionKeyboard($('#consignor_name'), $('#consignor_suggestions'));
        setupSuggestionKeyboard($('#consignee_name'), $('#consignee_suggestions'));

        $(document).on('keydown', '.item-name', function(e) {
            const $input = $(this);
            const $suggestions = $input.closest('.position-relative').find('.item-suggestions');
            const $items = $suggestions.find('.suggestion-item');
            if (!$items.length) return;

            const $active = $items.filter('.active');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if ($active.length) {
                    const $next = $active.nextAll('.suggestion-item').first();
                    if ($next.length) {
                        $active.removeClass('active');
                        $next.addClass('active');
                    }
                } else {
                    $items.first().addClass('active');
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if ($active.length) {
                    const $prev = $active.prevAll('.suggestion-item').first();
                    if ($prev.length) {
                        $active.removeClass('active');
                        $prev.addClass('active');
                    }
                } else {
                    $items.last().addClass('active');
                }
            } else if (e.key === 'Enter') {
                if ($active.length) {
                    e.preventDefault();
                    $active.trigger('click');
                }
            }
        });
    });
</script>
@endsection
