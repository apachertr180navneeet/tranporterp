@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Generate Bill</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Generate Bill</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.transport.billing') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search LR No, PO No, Consignor, Consignee..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="consignor_id" class="form-select">
                        <option value="">All Consignors</option>
                        @foreach($consignors as $consignor)
                            <option value="{{ $consignor->id }}" {{ request('consignor_id') == $consignor->id ? 'selected' : '' }}>
                                {{ $consignor->name }} ({{ $consignor->phone }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To Date">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                @if(request()->filled('search') || request()->filled('consignor_id') || request()->filled('from_date') || request()->filled('to_date'))
                <div class="col-md-2">
                    <a href="{{ route('admin.transport.billing') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Selected Count Bar -->
    <div id="selectedBar" class="alert alert-info d-none py-2 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bx bx-check-circle fs-5"></i>
            <span><strong id="selectedCount">0</strong> LR(s) selected across pages</span>
            <button type="button" id="clearSelectionBtn" class="btn btn-outline-secondary btn-sm ms-2">
                <i class="bx bx-x me-1"></i> Clear Selection
            </button>
        </div>
        <button id="generateBillBtn" class="btn btn-success btn-sm" disabled>
            <i class="bx bx-receipt me-1"></i> Generate Bill
        </button>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>LR No</th>
                            <th>Date</th>
                            <th>Consignor</th>
                            <th>Consignee</th>
                            <th>From → To</th>
                            <th>PO No</th>
                            <th>Freight (₹)</th>
                            <th>GST (₹)</th>
                            <th>Other (₹)</th>
                            <th>Total (₹)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bulties as $bulty)
                        <tr>
                            <td><input type="checkbox" class="bulty-checkbox" value="{{ $bulty->id }}"></td>
                            <td><strong>{{ $bulty->lr_no }}</strong></td>
                            <td>{{ $bulty->lr_date->format('d M Y') }}</td>
                            <td>{{ $bulty->consignor->name ?? '-' }}</td>
                            <td>{{ $bulty->consignee->name ?? '-' }}</td>
                            <td>
                                {{ $bulty->originCity->name ?? '-' }}
                                <i class="bx bx-chevron-right bx-xs"></i>
                                {{ $bulty->destinationCity->name ?? '-' }}
                            </td>
                            <td>{{ $bulty->bultyDetail->po_no ?? '-' }}</td>
                            <td class="text-end">{{ number_format($bulty->freight_charges, 2) }}</td>
                            <td class="text-end">{{ number_format($bulty->gst_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($bulty->other_charges, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($bulty->total_amount, 2) }}</strong></td>
                            <td>
                                <a href="{{ route('admin.transport.bulties.show', $bulty->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="bx bx-show"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No unbilled LRs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bulties->hasPages())
        <div class="card-footer">
            {{ $bulties->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
    const STORAGE_KEY = 'selected_billing_lrs';
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.bulty-checkbox');
    const selectedBar = document.getElementById('selectedBar');
    const selectedCount = document.getElementById('selectedCount');
    const generateBtn = document.getElementById('generateBillBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');

    function getSelectedIds() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveSelectedIds(ids) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
        } catch (e) {}
    }

    function updateSelectedBar() {
        const ids = getSelectedIds();
        const count = ids.length;
        if (count > 0) {
            selectedBar.classList.remove('d-none');
            selectedCount.textContent = count;
            generateBtn.disabled = false;
        } else {
            selectedBar.classList.add('d-none');
            generateBtn.disabled = true;
        }
    }

    function syncUIFromStorage() {
        const ids = getSelectedIds();
        const idSet = new Set(ids.map(String));
        let allPageChecked = checkboxes.length > 0;

        checkboxes.forEach(cb => {
            if (idSet.has(String(cb.value))) {
                cb.checked = true;
            } else {
                cb.checked = false;
                allPageChecked = false;
            }
        });

        if (selectAll) {
            selectAll.checked = allPageChecked && checkboxes.length > 0;
        }

        updateSelectedBar();
    }

    selectAll?.addEventListener('change', function() {
        let ids = getSelectedIds();
        const isChecked = this.checked;

        checkboxes.forEach(cb => {
            cb.checked = isChecked;
            const val = String(cb.value);
            if (isChecked) {
                if (!ids.includes(val)) {
                    ids.push(val);
                }
            } else {
                ids = ids.filter(id => id !== val);
            }
        });

        saveSelectedIds(ids);
        updateSelectedBar();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            let ids = getSelectedIds();
            const val = String(this.value);

            if (this.checked) {
                if (!ids.includes(val)) {
                    ids.push(val);
                }
            } else {
                ids = ids.filter(id => id !== val);
            }

            saveSelectedIds(ids);

            if (selectAll) {
                selectAll.checked = Array.from(checkboxes).every(c => c.checked) && checkboxes.length > 0;
            }

            updateSelectedBar();
        });
    });

    clearSelectionBtn?.addEventListener('click', function() {
        sessionStorage.removeItem(STORAGE_KEY);
        checkboxes.forEach(cb => cb.checked = false);
        if (selectAll) selectAll.checked = false;
        updateSelectedBar();
    });

    generateBtn?.addEventListener('click', function() {
        const ids = getSelectedIds();
        if (ids.length > 0) {
            window.location.href = '{{ route("admin.transport.billing.create") }}?ids=' + ids.join(',');
        }
    });

    // Run on initial load
    document.addEventListener('DOMContentLoaded', syncUIFromStorage);
    syncUIFromStorage();
</script>
@endsection
