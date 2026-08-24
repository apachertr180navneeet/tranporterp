@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bx bx-grid-alt me-2 text-primary"></i>Graphical Tyre Layout & Drag-and-Drop Positioning</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Maintenance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.maintenance.tyre-management.index') }}">Tyre Management</a></li>
                    <li class="breadcrumb-item active">Graphic Layout</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.maintenance.tyre-management.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-list-ul me-1"></i> List View
            </a>
            <a href="{{ route('admin.maintenance.tyre-management.create', ['vehicle_id' => $selectedVehicleId, 'return_to' => 'layout']) }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> New Tyre
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible shadow-sm mb-3" role="alert">
            <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible shadow-sm mb-3" role="alert">
            <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Toast Notification Container -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <!-- Vehicle Selector & Summary Stats -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.maintenance.tyre-management.layout') }}" id="vehicle-select-form" class="row align-items-center g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted mb-1"><i class="bx bx-truck me-1"></i> Select Vehicle (Truck)</label>
                    <select name="vehicle_id" id="vehicle_id" class="form-select form-select-lg fw-bold text-primary">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ $selectedVehicleId == $v->id ? 'selected' : '' }}>
                                {{ $v->vehicle_number }} {{ $v->vehicle_name ? '('.$v->vehicle_name.')' : '' }} {{ $v->brand ? '['.$v->brand.']' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <div class="d-flex justify-content-md-end gap-3 text-center">
                        <div class="bg-light rounded px-3 py-2 border">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.75rem;">Mounted Tyres</small>
                            <span class="fs-5 fw-bold text-success" id="mounted-count">{{ $vehicleTyres->count() }} / 20</span>
                        </div>
                        <div class="bg-light rounded px-3 py-2 border">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.75rem;">Avg Tread Depth</small>
                            <span class="fs-5 fw-bold text-info" id="avg-tread">
                                {{ $vehicleTyres->avg('tread_depth_current') ? number_format($vehicleTyres->avg('tread_depth_current'), 1).' mm' : 'N/A' }}
                            </span>
                        </div>
                        <div class="bg-light rounded px-3 py-2 border">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:0.75rem;">Unassigned Pool</small>
                            <span class="fs-5 fw-bold text-warning" id="unassigned-count">{{ $unassignedTyres->count() }}</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!$selectedVehicle)
        <div class="alert alert-info">Please select a vehicle to view its graphical tyre layout.</div>
    @else
        @php
            // Helper function to match tyres by slot code or position alias
            $findTyre = function($codes) use ($vehicleTyres) {
                $codes = (array)$codes;
                return $vehicleTyres->first(function($t) use ($codes) {
                    $pos = strtoupper(trim($t->tyre_position));
                    foreach ($codes as $c) {
                        if ($pos === strtoupper(trim($c))) return true;
                    }
                    return false;
                });
            };
        @endphp

        <div class="row g-4">
            <!-- Left Side: Interactive Graphic Chassis Diagram (9 Left + 9 Right + 2 Spares = 20 Total) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2 gap-2" style="background-color: #f8fafc !important; border-bottom: 1px solid #e2e8f0;">
                        <div class="d-flex align-items-center gap-2">
                            <!-- View Selector Mode Tabs (with gap spacing) -->
                            <div class="d-flex gap-2 me-2" id="layout-view-mode">
                                <button type="button" class="btn btn-sm btn-primary active rounded-pill px-3" onclick="setLayoutView('top-down')" id="btn-view-top-down">
                                    <i class="bx bx-layer me-1"></i> Top-Down Axle View
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="setLayoutView('side-view')" id="btn-view-side">
                                    <i class="bx bx-truck me-1"></i> Side Elevation View
                                </button>
                            </div>
                            <span class="fw-bold fs-6 text-dark d-none d-md-inline">
                                Vehicle: <span class="text-primary">{{ $selectedVehicle->vehicle_number }}</span>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="btn-group btn-group-sm" role="group" id="chassis-theme-selector">
                                <button type="button" class="btn btn-outline-secondary active" onclick="setChassisTheme('theme-dark-grid')" data-theme="theme-dark-grid" title="Dark Technical Grid"><i class="bx bx-grid-alt me-1"></i> Dark Grid</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setChassisTheme('theme-blueprint-blue')" data-theme="theme-blueprint-blue" title="Technical Blueprint Blue"><i class="bx bx-layer me-1"></i> Blueprint</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setChassisTheme('theme-light-studio')" data-theme="theme-light-studio" title="Light Workshop Grid"><i class="bx bx-sun me-1"></i> Light Studio</button>
                            </div>
                            <small class="text-secondary fw-semibold d-none d-xl-inline"><i class="bx bx-info-circle me-1"></i> Drag tyre cards to fit/swap</small>
                        </div>
                    </div>
                    <div id="chassis-canvas-body" class="card-body p-4 overflow-auto position-relative theme-dark-grid" style="min-height: 680px;">
                        
                        <!-- VIEW 1: Side Elevation Container Truck Graphic View -->
                        <div id="side-elevation-view-container" class="w-100 py-3 display-none" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary text-uppercase px-3 py-2 fs-6 shadow-sm"><i class="bx bx-truck me-1"></i> Side Elevation View - Container Semi Trailer Truck</span>
                                
                                <!-- Sub-tabs for Left Side vs Right Side Profile (with gap spacing) -->
                                <div class="d-flex gap-2" id="side-sub-tabs">
                                    <button type="button" class="btn btn-sm btn-outline-info active rounded-pill px-3" onclick="setSideProfile('left')" id="btn-side-left">
                                        <i class="bx bx-left-arrow-alt me-1"></i> Left Side (L1 - L9)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="setSideProfile('right')" id="btn-side-right">
                                        <i class="bx bx-right-arrow-alt me-1"></i> Right Side (R1 - R9)
                                    </button>
                                </div>
                            </div>

                            <!-- Vector Graphic Side Profile Trailer & Cab Banner -->
                            <div class="position-relative mx-auto my-4 p-3 bg-dark rounded border border-secondary shadow-lg text-center" style="max-width: 760px; overflow: hidden; background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 240" class="w-100 h-auto" id="side-truck-svg" style="max-height: 200px;">
                                    <defs>
                                        <linearGradient id="cabGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#f8fafc"/>
                                            <stop offset="100%" stop-color="#cbd5e1"/>
                                        </linearGradient>
                                        <linearGradient id="containerGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#ffffff"/>
                                            <stop offset="100%" stop-color="#e2e8f0"/>
                                        </linearGradient>
                                    </defs>
                                    <!-- Tractor Cab (Left Facing) -->
                                    <g id="svg-cab-group">
                                        <path d="M 20,180 L 20,80 Q 20,40 50,30 L 130,30 L 160,80 L 175,80 L 175,180 Z" fill="url(#cabGrad)" stroke="#475569" stroke-width="3"/>
                                        <path d="M 45,42 L 120,42 L 145,80 L 45,80 Z" fill="#1e293b" stroke="#38bdf8" stroke-width="2"/>
                                        <rect x="15" y="150" width="160" height="25" fill="#334155" rx="3"/>
                                    </g>
                                    <!-- Containers -->
                                    <rect x="195" y="40" width="330" height="120" fill="url(#containerGrad)" stroke="#94a3b8" stroke-width="3" rx="2"/>
                                    <g stroke="#cbd5e1" stroke-width="2">
                                        <line x1="220" y1="40" x2="220" y2="160"/><line x1="250" y1="40" x2="250" y2="160"/>
                                        <line x1="280" y1="40" x2="280" y2="160"/><line x1="310" y1="40" x2="310" y2="160"/>
                                        <line x1="340" y1="40" x2="340" y2="160"/><line x1="370" y1="40" x2="370" y2="160"/>
                                        <line x1="400" y1="40" x2="400" y2="160"/><line x1="430" y1="40" x2="430" y2="160"/>
                                        <line x1="460" y1="40" x2="460" y2="160"/><line x1="490" y1="40" x2="490" y2="160"/>
                                    </g>
                                    <rect x="535" y="40" width="345" height="120" fill="url(#containerGrad)" stroke="#94a3b8" stroke-width="3" rx="2"/>
                                    <g stroke="#cbd5e1" stroke-width="2">
                                        <line x1="560" y1="40" x2="560" y2="160"/><line x1="590" y1="40" x2="590" y2="160"/>
                                        <line x1="620" y1="40" x2="620" y2="160"/><line x1="650" y1="40" x2="650" y2="160"/>
                                        <line x1="680" y1="40" x2="680" y2="160"/><line x1="710" y1="40" x2="710" y2="160"/>
                                        <line x1="740" y1="40" x2="740" y2="160"/><line x1="770" y1="40" x2="770" y2="160"/>
                                        <line x1="800" y1="40" x2="800" y2="160"/><line x1="830" y1="40" x2="830" y2="160"/>
                                    </g>
                                    <rect x="170" y="160" width="710" height="20" fill="#334155"/>
                                    <path d="M 370,180 L 375,210 L 385,210 L 390,180 Z" fill="#64748b"/>
                                    <rect x="865" y="170" width="15" height="35" fill="#dc2626"/>
                                </svg>
                            </div>

                            <!-- LEFT SIDE PROFILE SLOTS (L1 TO L9 + SP1) -->
                            <div id="side-left-slots" class="row g-3 justify-content-center">
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-primary">Axle 1: Front Steer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L1', 'slotName' => 'Front Left (L1)', 'tyre' => $findTyre(['L1'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 2: Left Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L2', 'slotName' => 'Left Outer 1 (L2)', 'tyre' => $findTyre(['L2'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 2: Left Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L3', 'slotName' => 'Left Inner 1 (L3)', 'tyre' => $findTyre(['L3'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 3: Left Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L4', 'slotName' => 'Left Outer 2 (L4)', 'tyre' => $findTyre(['L4'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 3: Left Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L5', 'slotName' => 'Left Inner 2 (L5)', 'tyre' => $findTyre(['L5'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 4: Left Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L6', 'slotName' => 'Left Outer 3 (L6)', 'tyre' => $findTyre(['L6'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 4: Left Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L7', 'slotName' => 'Left Inner 3 (L7)', 'tyre' => $findTyre(['L7'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 5: Left Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L8', 'slotName' => 'Left Outer 4 (L8)', 'tyre' => $findTyre(['L8'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 5: Left Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'L9', 'slotName' => 'Left Inner 4 (L9)', 'tyre' => $findTyre(['L9'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-warning text-dark">Spare Stepney</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'SP1', 'slotName' => 'Spare 1 (SP1)', 'tyre' => $findTyre(['SP1'])])
                                </div>
                            </div>

                            <!-- RIGHT SIDE PROFILE SLOTS (R1 TO R9 + SP2) -->
                            <div id="side-right-slots" class="row g-3 justify-content-center display-none" style="display: none;">
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-primary">Axle 1: Front Steer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R1', 'slotName' => 'Front Right (R1)', 'tyre' => $findTyre(['R1'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 2: Right Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R3', 'slotName' => 'Right Inner 1 (R3)', 'tyre' => $findTyre(['R3'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 2: Right Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R2', 'slotName' => 'Right Outer 1 (R2)', 'tyre' => $findTyre(['R2'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 3: Right Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R5', 'slotName' => 'Right Inner 2 (R5)', 'tyre' => $findTyre(['R5'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-dark border border-secondary">Axle 3: Right Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R4', 'slotName' => 'Right Outer 2 (R4)', 'tyre' => $findTyre(['R4'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 4: Right Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R7', 'slotName' => 'Right Inner 3 (R7)', 'tyre' => $findTyre(['R7'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 4: Right Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R6', 'slotName' => 'Right Outer 3 (R6)', 'tyre' => $findTyre(['R6'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 5: Right Inner</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R9', 'slotName' => 'Right Inner 4 (R9)', 'tyre' => $findTyre(['R9'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-secondary">Axle 5: Right Outer</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'R8', 'slotName' => 'Right Outer 4 (R8)', 'tyre' => $findTyre(['R8'])])
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="text-center mb-1"><span class="badge bg-warning text-dark">Spare Stepney</span></div>
                                    @include('admin.maintenance.tyre-management.partials.slot', ['slotCode' => 'SP2', 'slotName' => 'Spare 2 (SP2)', 'tyre' => $findTyre(['SP2'])])
                                </div>
                            </div>
                        </div>

                        <!-- VIEW 2: Top-Down Chassis Detailed View -->
                        <div id="top-down-view-container" class="truck-chassis-container mx-auto position-relative py-3" style="max-width: 740px;">
                            
                            <!-- Front Cabin Graphic (Realistic Semi Truck Top-Down Silhouette) -->
                            <div class="truck-cab text-center mb-4 p-3 rounded-top position-relative shadow-lg" style="background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); border-bottom: 5px solid #38bdf8;">
                                <div class="position-relative mx-auto" style="max-width: 320px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 120" class="w-100 h-auto">
                                        <defs>
                                            <linearGradient id="topCabHood" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#3b82f6"/>
                                                <stop offset="100%" stop-color="#1d4ed8"/>
                                            </linearGradient>
                                            <linearGradient id="topGlass" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#38bdf8"/>
                                                <stop offset="100%" stop-color="#0284c7"/>
                                            </linearGradient>
                                        </defs>
                                        <!-- Front Bumper & Bullbar -->
                                        <rect x="50" y="5" width="220" height="14" rx="4" fill="#64748b" stroke="#cbd5e1" stroke-width="2"/>
                                        <!-- Engine Hood -->
                                        <path d="M 60,19 L 260,19 L 250,60 L 70,60 Z" fill="url(#topCabHood)" stroke="#60a5fa" stroke-width="2"/>
                                        <!-- Front Windshield -->
                                        <path d="M 80,63 L 240,63 L 230,85 L 90,85 Z" fill="url(#topGlass)" opacity="0.9"/>
                                        <!-- Cabin Roof Shell -->
                                        <rect x="70" y="88" width="180" height="28" rx="6" fill="#1e3a8a" stroke="#3b82f6" stroke-width="2"/>
                                        <!-- Left Side Mirror -->
                                        <rect x="25" y="45" width="22" height="10" rx="2" fill="#475569" stroke="#94a3b8"/>
                                        <line x1="47" y1="50" x2="65" y2="50" stroke="#cbd5e1" stroke-width="3"/>
                                        <!-- Right Side Mirror -->
                                        <rect x="273" y="45" width="22" height="10" rx="2" fill="#475569" stroke="#94a3b8"/>
                                        <line x1="273" y1="50" x2="255" y2="50" stroke="#cbd5e1" stroke-width="3"/>
                                    </svg>
                                </div>
                                <div class="fs-6 fw-bold text-uppercase tracking-wider text-white mt-1"><i class="bx bx-navigation me-1"></i> HEAVY TRUCK FRONT CABIN</div>
                                <div class="small text-light opacity-75">Driver Cockpit & Engine Compartment</div>
                            </div>

                            <!-- Chassis Frame Centerline Rails (Twin Heavy Metallic Rails) -->
                            <div class="chassis-frame position-absolute top-0 bottom-0 start-50 translate-middle-x opacity-40 d-flex justify-content-between" style="width: 130px; z-index: 0; pointer-events: none;">
                                <div class="chassis-frame-rail"></div>
                                <div class="chassis-frame-rail"></div>
                            </div>

                            <!-- AXLE 1: FRONT STEERING AXLE (L1 / R1) -->
                            <div class="axle-section mb-4 position-relative" style="z-index: 1;">
                                <div class="text-center mb-2">
                                    <span class="badge bg-primary text-uppercase px-3 py-1 shadow-sm" style="letter-spacing: 0.5px;">Axle 1: Front Steering (L1 / R1)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center position-relative px-2">
                                    <div class="position-absolute start-0 end-0 bg-secondary opacity-75" style="height: 14px; top: 50%; transform: translateY(-50%); z-index: 0;"></div>

                                    <!-- Left Slot 1 (L1) -->
                                    @include('admin.maintenance.tyre-management.partials.slot', [
                                        'slotCode' => 'L1',
                                        'slotName' => 'Front Left (L1)',
                                        'tyre' => $findTyre(['L1', 'FL', 'Front Left'])
                                    ])

                                    <!-- Center Axle Hub -->
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow" style="width: 42px; height: 42px; z-index: 1; border: 2px solid #ffffff;">
                                        A1
                                    </div>

                                    <!-- Right Slot 1 (R1) -->
                                    @include('admin.maintenance.tyre-management.partials.slot', [
                                        'slotCode' => 'R1',
                                        'slotName' => 'Front Right (R1)',
                                        'tyre' => $findTyre(['R1', 'FR', 'Front Right'])
                                    ])
                                </div>
                            </div>

                            <!-- AXLE 2: DRIVE AXLE 1 DUAL TYRES (L2, L3 / R3, R2) -->
                            <div class="axle-section mb-4 position-relative" style="z-index: 1;">
                                <div class="text-center mb-2">
                                    <span class="badge bg-dark border border-secondary text-uppercase px-3 py-1 shadow-sm">Axle 2: Drive Axle 1 (L2, L3 / R3, R2)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center position-relative px-2">
                                    <div class="position-absolute start-0 end-0 bg-dark" style="height: 16px; top: 50%; transform: translateY(-50%); z-index: 0;"></div>

                                    <!-- Left Dual Pair (L2 Outer, L3 Inner) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L2',
                                            'slotName' => 'Left Outer 1 (L2)',
                                            'tyre' => $findTyre(['L2', 'ROL1', 'Rear Left Outer'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L3',
                                            'slotName' => 'Left Inner 1 (L3)',
                                            'tyre' => $findTyre(['L3', 'RIL1', 'Rear Left Inner'])
                                        ])
                                    </div>

                                    <!-- Differential Hub -->
                                    <div class="bg-dark border border-2 border-warning text-warning rounded-circle d-flex align-items-center justify-content-center fw-bold shadow" style="width: 46px; height: 46px; z-index: 1;">
                                        DIFF1
                                    </div>

                                    <!-- Right Dual Pair (R3 Inner, R2 Outer) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R3',
                                            'slotName' => 'Right Inner 1 (R3)',
                                            'tyre' => $findTyre(['R3', 'RIR1', 'Rear Right Inner'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R2',
                                            'slotName' => 'Right Outer 1 (R2)',
                                            'tyre' => $findTyre(['R2', 'ROR1', 'Rear Right Outer'])
                                        ])
                                    </div>
                                </div>
                            </div>

                            <!-- DISTANCE GAP AFTER AXLE 2 (Wheelbase Space) -->
                            <div class="wheelbase-gap my-5 position-relative d-flex justify-content-center align-items-center" style="z-index: 1;">
                                <div class="px-3 py-2 rounded-pill bg-dark text-warning border border-warning shadow-sm small fw-semibold text-uppercase opacity-90">
                                    <i class="bx bx-ruler me-1"></i> Chassis Wheelbase Gap (After Axle 2)
                                </div>
                            </div>

                            <!-- AXLE 3: DRIVE AXLE 2 DUAL TYRES (L4, L5 / R5, R4) -->
                            <div class="axle-section mb-4 position-relative" style="z-index: 1;">
                                <div class="text-center mb-2">
                                    <span class="badge bg-dark border border-secondary text-uppercase px-3 py-1 shadow-sm">Axle 3: Drive Axle 2 (L4, L5 / R5, R4)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center position-relative px-2">
                                    <div class="position-absolute start-0 end-0 bg-dark" style="height: 16px; top: 50%; transform: translateY(-50%); z-index: 0;"></div>

                                    <!-- Left Dual Pair (L4 Outer, L5 Inner) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L4',
                                            'slotName' => 'Left Outer 2 (L4)',
                                            'tyre' => $findTyre(['L4', 'ROL2'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L5',
                                            'slotName' => 'Left Inner 2 (L5)',
                                            'tyre' => $findTyre(['L5', 'RIL2'])
                                        ])
                                    </div>

                                    <!-- Differential Hub -->
                                    <div class="bg-dark border border-2 border-warning text-warning rounded-circle d-flex align-items-center justify-content-center fw-bold shadow" style="width: 46px; height: 46px; z-index: 1;">
                                        DIFF2
                                    </div>

                                    <!-- Right Dual Pair (R5 Inner, R4 Outer) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R5',
                                            'slotName' => 'Right Inner 2 (R5)',
                                            'tyre' => $findTyre(['R5', 'RIR2'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R4',
                                            'slotName' => 'Right Outer 2 (R4)',
                                            'tyre' => $findTyre(['R4', 'ROR2'])
                                        ])
                                    </div>
                                </div>
                            </div>

                            <!-- AXLE 4: TRAILER AXLE 1 DUAL TYRES (L6, L7 / R7, R6) -->
                            <div class="axle-section mb-4 position-relative" style="z-index: 1;">
                                <div class="text-center mb-2">
                                    <span class="badge bg-secondary text-uppercase px-3 py-1 shadow-sm">Axle 4: Rear / Trailer Axle (L6, L7 / R7, R6)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center position-relative px-2">
                                    <div class="position-absolute start-0 end-0 bg-dark" style="height: 14px; top: 50%; transform: translateY(-50%); z-index: 0;"></div>

                                    <!-- Left Dual Pair (L6 Outer, L7 Inner) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L6',
                                            'slotName' => 'Left Outer 3 (L6)',
                                            'tyre' => $findTyre(['L6', 'TLO1', 'ROL3'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L7',
                                            'slotName' => 'Left Inner 3 (L7)',
                                            'tyre' => $findTyre(['L7', 'TLI1', 'RIL3'])
                                        ])
                                    </div>

                                    <!-- Center Axle Hub -->
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; z-index: 1; border: 2px solid #ffffff;">
                                        A4
                                    </div>

                                    <!-- Right Dual Pair (R7 Inner, R6 Outer) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R7',
                                            'slotName' => 'Right Inner 3 (R7)',
                                            'tyre' => $findTyre(['R7', 'TRI1', 'RIR3'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R6',
                                            'slotName' => 'Right Outer 3 (R6)',
                                            'tyre' => $findTyre(['R6', 'TRO1', 'ROR3'])
                                        ])
                                    </div>
                                </div>
                            </div>

                            <!-- AXLE 5: AUXILIARY REAR AXLE 1 DUAL TYRES (L8, L9 / R9, R8) -->
                            <div class="axle-section mb-4 position-relative" style="z-index: 1;">
                                <div class="text-center mb-2">
                                    <span class="badge bg-secondary text-uppercase px-3 py-1 shadow-sm">Axle 5: Auxiliary Rear Axle 1 (L8, L9 / R9, R8)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center position-relative px-2">
                                    <div class="position-absolute start-0 end-0 bg-dark" style="height: 14px; top: 50%; transform: translateY(-50%); z-index: 0;"></div>

                                    <!-- Left Dual Pair (L8 Outer, L9 Inner) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L8',
                                            'slotName' => 'Left Outer 4 (L8)',
                                            'tyre' => $findTyre(['L8', 'RL8'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'L9',
                                            'slotName' => 'Left Inner 4 (L9)',
                                            'tyre' => $findTyre(['L9', 'RL9'])
                                        ])
                                    </div>

                                    <!-- Center Axle Hub -->
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; z-index: 1; border: 2px solid #ffffff;">
                                        A5
                                    </div>

                                    <!-- Right Dual Pair (R9 Inner, R8 Outer) -->
                                    <div class="d-flex gap-2 z-1">
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R9',
                                            'slotName' => 'Right Inner 4 (R9)',
                                            'tyre' => $findTyre(['R9', 'RR9'])
                                        ])
                                        @include('admin.maintenance.tyre-management.partials.slot', [
                                            'slotCode' => 'R8',
                                            'slotName' => 'Right Outer 4 (R8)',
                                            'tyre' => $findTyre(['R8', 'RR8'])
                                        ])
                                    </div>
                                </div>
                            </div>

                            <!-- SPARE STEPNEY CARRIER (2 SPARES: SP1, SP2) -->
                            <div class="axle-section mb-4 position-relative" style="z-index: 1;">
                                <div class="text-center mb-2">
                                    <span class="badge bg-warning text-dark text-uppercase px-3 py-1 shadow-sm"><i class="bx bx-shield me-1"></i> Spare Wheel Carrier (2 Spares)</span>
                                </div>
                                <div class="d-flex justify-content-center gap-4 align-items-center position-relative px-2">
                                    @include('admin.maintenance.tyre-management.partials.slot', [
                                        'slotCode' => 'SP1',
                                        'slotName' => 'Spare 1 (Stepney)',
                                        'tyre' => $findTyre(['SP1', 'Spare 1', 'Spare'])
                                    ])
                                    @include('admin.maintenance.tyre-management.partials.slot', [
                                        'slotCode' => 'SP2',
                                        'slotName' => 'Spare 2 (Stepney)',
                                        'tyre' => $findTyre(['SP2', 'Spare 2'])
                                    ])
                                </div>
                            </div>

                            <!-- Rear Bumper Graphic (Heavy Duty Rear Bumper with Warning Lights) -->
                            <div class="truck-bumper text-center p-3 rounded-bottom bg-dark text-white fw-bold shadow-lg position-relative" style="border-top: 4px dashed #f59e0b; border-bottom: 3px solid #dc2626;">
                                <div class="d-flex justify-content-between align-items-center px-3">
                                    <span class="badge bg-danger rounded-circle p-2"></span>
                                    <small class="text-uppercase tracking-wider text-warning"><i class="bx bx-pause me-1"></i> TRUCK REAR BUMPER / REFLECTOR STRIP</small>
                                    <span class="badge bg-danger rounded-circle p-2"></span>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

            <!-- Right Side: Unassigned Tyre Inventory & Pool Rack -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                        <div class="fw-bold"><i class="bx bx-archive me-1"></i> Tyre Pool & Inventory Rack</div>
                        <span class="badge bg-light text-primary" id="unassigned-badge-count">{{ $unassignedTyres->count() }}</span>
                    </div>
                    <div class="card-body p-3 bg-light">
                        <div class="text-muted small mb-3">
                            <i class="bx bx-info-circle me-1"></i> Drag tyres from this rack into wheel slots on the truck layout to fit them. Or drag tyres back here to unmount them.
                        </div>

                        <!-- Drop zone for unassigning tyres -->
                        <div class="unassigned-drop-zone p-3 rounded border border-2 border-dashed border-primary bg-white text-center mb-3 shadow-xs" 
                             data-slot-code="Unassigned"
                             ondragover="handleDragOver(event)"
                             ondragleave="handleDragLeave(event)"
                             ondrop="handleDrop(event, 'Unassigned')">
                            <i class="bx bx-cloud-upload fs-3 text-primary d-block mb-1"></i>
                            <span class="fw-bold text-primary">Unmount Tyre Zone</span>
                            <small class="d-block text-muted">Drop any mounted tyre here to unmount from vehicle</small>
                        </div>

                        <!-- Unassigned Tyre Pool Container -->
                        <div id="unassigned-tyres-list" class="d-flex flex-column gap-2 overflow-auto" style="max-height: 600px;">
                            @forelse($unassignedTyres as $unTyre)
                                @include('admin.maintenance.tyre-management.partials.tyre-card', ['tyre' => $unTyre])
                            @empty
                                <div class="text-center text-muted py-4 id-no-unassigned">
                                    <i class="bx bx-check-circle fs-2 text-success d-block mb-1"></i>
                                    No unassigned tyres in inventory pool
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
/* Theme Selector Button Contrast Fix */
#chassis-theme-selector .btn {
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    font-weight: 600;
    opacity: 1 !important;
}
#chassis-theme-selector .btn.active {
    background-color: #0f172a !important;
    color: #ffffff !important;
    border-color: #0f172a !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.25);
}
#chassis-theme-selector .btn:hover:not(.active) {
    background-color: #e2e8f0 !important;
    color: #0f172a !important;
}

/* Canvas Background Themes & Styling */
#chassis-canvas-body {
    transition: background 0.3s ease, color 0.3s ease;
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
}

/* 1. Dark Technical Grid Theme (Default - Samsara / Modern Telematics style) */
#chassis-canvas-body.theme-dark-grid {
    background-color: #0b0f19;
    background-image: 
        radial-gradient(ellipse at 50% 40%, rgba(30, 58, 138, 0.35) 0%, transparent 75%),
        radial-gradient(rgba(59, 130, 246, 0.15) 1px, transparent 1px),
        linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 100% 100%, 24px 24px, 24px 24px, 24px 24px;
    color: #f8fafc;
}

/* 2. Technical Blueprint Blue Theme */
#chassis-canvas-body.theme-blueprint-blue {
    background-color: #091e36;
    background-image: 
        radial-gradient(ellipse at 50% 30%, rgba(56, 189, 248, 0.25) 0%, transparent 75%),
        linear-gradient(to right, rgba(56, 189, 248, 0.12) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(56, 189, 248, 0.12) 1px, transparent 1px);
    background-size: 100% 100%, 20px 20px, 20px 20px;
    color: #e0f2fe;
}

/* 3. Soft Light Workshop Theme */
#chassis-canvas-body.theme-light-studio {
    background-color: #f1f5f9;
    background-image: 
        radial-gradient(ellipse at 50% 30%, rgba(224, 231, 255, 0.8) 0%, transparent 80%),
        linear-gradient(to right, rgba(148, 163, 184, 0.18) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(148, 163, 184, 0.18) 1px, transparent 1px);
    background-size: 100% 100%, 20px 20px, 20px 20px;
    color: #0f172a;
}

/* Centerline Heavy Metallic Frame Rails with Mechanical Steel Crossmembers */
.chassis-frame-rail {
    width: 16px;
    border-radius: 4px;
    background: linear-gradient(90deg, #1e293b 0%, #475569 25%, #cbd5e1 50%, #475569 75%, #0f172a 100%);
    box-shadow: 0 0 12px rgba(0, 0, 0, 0.7), inset 0 0 4px rgba(255, 255, 255, 0.5), inset 0 0 8px rgba(0, 0, 0, 0.8);
    position: relative;
}
.chassis-frame-rail::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 4px;
    right: 4px;
    background: repeating-linear-gradient(0deg, rgba(255,255,255,0.15), rgba(255,255,255,0.15) 2px, transparent 2px, transparent 30px);
}

/* Steel Crossmember Bars connecting the dual frame rails */
.chassis-frame::before {
    content: '';
    position: absolute;
    top: 15%;
    bottom: 15%;
    left: 10px;
    right: 10px;
    background: repeating-linear-gradient(0deg, #334155, #334155 8px, transparent 8px, transparent 80px);
    box-shadow: 0 0 4px rgba(0,0,0,0.5);
    z-index: -1;
}

/* Modern Heavy Truck Tyre Slot & Mechanical Wheel Hub Styles */
.tyre-slot {
    width: 124px;
    min-height: 145px;
    border-radius: 12px;
    transition: all 0.25s ease-in-out;
    position: relative;
    backdrop-filter: blur(8px);
}

/* Realistic Wheel Hub / Brake Drum Connector behind slots */
.tyre-slot::before {
    content: '';
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 38px;
    background: linear-gradient(180deg, #475569 0%, #1e293b 50%, #475569 100%);
    border: 1px solid #64748b;
    border-radius: 4px;
    z-index: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
}
.tyre-slot[data-slot-code^="L"]::before {
    right: -16px;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}
.tyre-slot[data-slot-code^="R"]::before {
    left: -16px;
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}
.tyre-slot[data-slot-code^="SP"]::before {
    display: none;
}

/* Dark Theme Slots */
#chassis-canvas-body.theme-dark-grid .tyre-slot,
#chassis-canvas-body.theme-blueprint-blue .tyre-slot {
    background: rgba(15, 23, 42, 0.85);
    border: 2px dashed rgba(148, 163, 184, 0.45);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5), inset 0 0 10px rgba(0,0,0,0.3);
}
#chassis-canvas-body.theme-dark-grid .tyre-slot.occupied,
#chassis-canvas-body.theme-blueprint-blue .tyre-slot.occupied {
    background: rgba(30, 41, 59, 0.95);
    border: 2px solid #38bdf8;
    box-shadow: 0 4px 18px rgba(56, 189, 248, 0.3), inset 0 0 8px rgba(56, 189, 248, 0.15);
}

/* Light Theme Slots */
#chassis-canvas-body.theme-light-studio .tyre-slot {
    background: rgba(255, 255, 255, 0.9);
    border: 2px dashed #94a3b8;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
#chassis-canvas-body.theme-light-studio .tyre-slot.occupied {
    background: #ffffff;
    border: 2px solid #2563eb;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
}

.tyre-slot.drag-over {
    border-color: #38bdf8 !important;
    background-color: rgba(56, 189, 248, 0.25) !important;
    transform: scale(1.06);
    box-shadow: 0 0 25px rgba(56, 189, 248, 0.7) !important;
}

/* Draggable Tyre Card */
.tyre-card {
    cursor: grab;
    user-select: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.tyre-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2) !important;
}
.tyre-card:active {
    cursor: grabbing;
}
.tyre-card.dragging {
    opacity: 0.4;
}

.unassigned-drop-zone.drag-over {
    background-color: #e7f1ff !important;
    border-color: #0d6efd !important;
    transform: scale(1.02);
}

/* Interactive Empty Slot Styling */
.tyre-slot.empty {
    cursor: pointer !important;
    transition: all 0.2s ease-in-out;
    position: relative;
}
.tyre-slot.empty:hover {
    border-color: #38bdf8 !important;
    background-color: rgba(56, 189, 248, 0.15) !important;
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 6px 20px rgba(56, 189, 248, 0.4) !important;
}
.tyre-slot.empty:hover .slot-plus-icon {
    transform: scale(1.25);
    transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    color: #38bdf8 !important;
}
.tyre-slot.empty:hover .empty-slot-text {
    color: #38bdf8 !important;
    font-weight: 800 !important;
}
.tyre-slot.empty:hover .empty-slot-hint {
    color: #bae6fd !important;
}
</style>

<script>
let currentVehicleId = {{ $selectedVehicleId ?? 'null' }};
let draggedTyreId = null;
let draggedFromSlot = null;

function handleEmptySlotClick(slotCode) {
    if (!currentVehicleId) {
        showToast('warning', 'Please select a vehicle first.');
        return;
    }
    const createUrl = "{{ route('admin.maintenance.tyre-management.create') }}?vehicle_id=" + currentVehicleId + "&tyre_position=" + encodeURIComponent(slotCode) + "&return_to=layout";
    window.location.href = createUrl;
}
window.handleEmptySlotClick = handleEmptySlotClick;

function setLayoutView(mode) {
    const topDownContainer = document.getElementById('top-down-view-container');
    const sideViewContainer = document.getElementById('side-elevation-view-container');
    const btnTopDown = document.getElementById('btn-view-top-down');
    const btnSide = document.getElementById('btn-view-side');

    if (mode === 'side-view') {
        topDownContainer.style.display = 'none';
        sideViewContainer.style.display = 'block';
        btnTopDown.classList.remove('btn-primary', 'active');
        btnTopDown.classList.add('btn-outline-primary');
        btnSide.classList.remove('btn-outline-primary');
        btnSide.classList.add('btn-primary', 'active');
    } else {
        sideViewContainer.style.display = 'none';
        topDownContainer.style.display = 'block';
        btnSide.classList.remove('btn-primary', 'active');
        btnSide.classList.add('btn-outline-primary');
        btnTopDown.classList.remove('btn-outline-primary');
        btnTopDown.classList.add('btn-primary', 'active');
    }
}

function setSideProfile(side) {
    const leftSlots = document.getElementById('side-left-slots');
    const rightSlots = document.getElementById('side-right-slots');
    const btnLeft = document.getElementById('btn-side-left');
    const btnRight = document.getElementById('btn-side-right');
    const truckSvg = document.getElementById('side-truck-svg');

    if (side === 'right') {
        leftSlots.style.display = 'none';
        rightSlots.style.display = 'flex';
        btnLeft.classList.remove('active');
        btnRight.classList.add('active');
        if (truckSvg) truckSvg.style.transform = 'scaleX(-1)';
    } else {
        rightSlots.style.display = 'none';
        leftSlots.style.display = 'flex';
        btnRight.classList.remove('active');
        btnLeft.classList.add('active');
        if (truckSvg) truckSvg.style.transform = 'scaleX(1)';
    }
}

function handleDragStart(event, tyreId, slotCode) {
    draggedTyreId = tyreId;
    draggedFromSlot = slotCode;
    event.dataTransfer.setData('text/plain', tyreId);
    event.dataTransfer.effectAllowed = 'move';
    event.target.classList.add('dragging');
}

function handleDragEnd(event) {
    event.target.classList.remove('dragging');
}

function handleDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    event.currentTarget.classList.add('drag-over');
}

function handleDragLeave(event) {
    event.currentTarget.classList.remove('drag-over');
}

function handleDrop(event, targetSlotCode) {
    event.preventDefault();
    const targetElement = event.currentTarget;
    targetElement.classList.remove('drag-over');

    if (!draggedTyreId || !currentVehicleId) return;

    let targetTyreId = null;
    const existingTyreCard = targetElement.querySelector('.tyre-card');
    if (existingTyreCard && targetSlotCode !== 'Unassigned') {
        targetTyreId = existingTyreCard.getAttribute('data-tyre-id');
    }

    updateTyrePosition(draggedTyreId, targetSlotCode, targetTyreId);
}

function updateTyrePosition(tyreId, newPosition, targetTyreId = null) {
    showToast('info', 'Updating tyre position...');

    fetch('{{ route("admin.maintenance.tyre-management.update-position") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            tyre_id: tyreId,
            vehicle_id: currentVehicleId,
            new_position: newPosition,
            target_tyre_id: targetTyreId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            
            // Dynamically update affected wheel slots in DOM without page reload
            if (data.slots_html) {
                Object.keys(data.slots_html).forEach(slotCode => {
                    const slotEl = document.querySelector(`.tyre-slot[data-slot-code="${slotCode}"]`);
                    if (slotEl) {
                        slotEl.outerHTML = data.slots_html[slotCode];
                    }
                });
            }

            // Dynamically update unassigned tyres pool
            if (data.unassigned_html !== undefined) {
                const unassignedList = document.getElementById('unassigned-tyres-list');
                if (unassignedList) {
                    unassignedList.innerHTML = data.unassigned_html;
                }
            }

            // Dynamically update header summary stats
            if (data.stats) {
                const mountedEl = document.getElementById('mounted-count');
                const avgTreadEl = document.getElementById('avg-tread');
                const unassignedCountEl = document.getElementById('unassigned-count');
                const unassignedBadgeEl = document.getElementById('unassigned-badge-count');

                if (mountedEl) mountedEl.textContent = data.stats.mounted_count;
                if (avgTreadEl) avgTreadEl.textContent = data.stats.avg_tread;
                if (unassignedCountEl) unassignedCountEl.textContent = data.stats.unassigned_count;
                if (unassignedBadgeEl) unassignedBadgeEl.textContent = data.stats.unassigned_count;
            }
        } else {
            showToast('danger', data.message || 'Failed to update tyre position');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('danger', 'Error updating tyre position');
    });
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0 show shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="bx bx-info-circle me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    setTimeout(() => {
        const el = document.getElementById(toastId);
        if (el) el.remove();
    }, 4000);
}
function setChassisTheme(themeName) {
    const canvas = document.getElementById('chassis-canvas-body');
    if (canvas) {
        canvas.classList.remove('theme-dark-grid', 'theme-blueprint-blue', 'theme-light-studio');
        canvas.classList.add(themeName);
    }
    const buttons = document.querySelectorAll('#chassis-theme-selector .btn');
    buttons.forEach(btn => {
        if (btn.getAttribute('data-theme') === themeName) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    try {
        localStorage.setItem('chassis_layout_theme', themeName);
    } catch(e) {
        console.error('LocalStorage error:', e);
    }
}
window.setChassisTheme = setChassisTheme;

document.addEventListener('DOMContentLoaded', function() {
    const vehicleSelect = document.getElementById('vehicle_id');
    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', function() {
            document.getElementById('vehicle-select-form')?.submit();
        });
    }

    if (typeof jQuery !== 'undefined') {
        jQuery('#vehicle_id').on('change', function() {
            document.getElementById('vehicle-select-form')?.submit();
        });
    }

    // Apply saved theme on page load
    const savedTheme = localStorage.getItem('chassis_layout_theme') || 'theme-dark-grid';
    setChassisTheme(savedTheme);
});
</script>
@endsection
