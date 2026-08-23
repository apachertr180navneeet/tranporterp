@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Toll Bills</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.transport.billing') }}">Billing</a></li>
                    <li class="breadcrumb-item active">Toll Bills</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.transport.toll-bills.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search Invoice No, Consignor, Consignee..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="consignor_id" class="form-select">
                        <option value="">All Consignors</option>
                        @foreach($consignors as $consignor)
                            <option value="{{ $consignor->id }}" {{ request('consignor_id') == $consignor->id ? 'selected' : '' }}>
                                {{ $consignor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="consignee_id" class="form-select">
                        <option value="">All Consignees</option>
                        @foreach($consignees as $consignee)
                            <option value="{{ $consignee->id }}" {{ request('consignee_id') == $consignee->id ? 'selected' : '' }}>
                                {{ $consignee->name }}
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
                @if(request()->filled('search') || request()->filled('consignor_id') || request()->filled('consignee_id') || request()->filled('from_date') || request()->filled('to_date'))
                <div class="col-md-12 text-end">
                    <a href="{{ route('admin.transport.toll-bills.index') }}" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Consignor / Party</th>
                            <th>Consignee</th>
                            <th class="text-end">Toll Amount (₹)</th>
                            <th class="text-end">GST (₹)</th>
                            <th class="text-end">Grand Total (₹)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                        <tr>
                            <td><strong>{{ $inv->invoice_no }}</strong></td>
                            <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                            <td>{{ $inv->consignor_name ?? ($inv->consignor->name ?? '-') }}</td>
                            <td>{{ $inv->consignee_names }}</td>
                            <td class="text-end">{{ number_format($inv->total_freight, 2) }}</td>
                            <td class="text-end">{{ number_format($inv->total_gst, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($inv->total_amount, 2) }}</strong></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link dropdown-toggle text-decoration-none p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        @if($inv->status === 'paid')
                                            <span class="badge bg-label-success">Paid</span>
                                        @elseif($inv->status === 'cancelled')
                                            <span class="badge bg-label-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-label-warning">Pending</span>
                                        @endif
                                    </button>
                                    <ul class="dropdown-menu shadow-sm">
                                        <li>
                                             <form action="{{ route('admin.transport.invoices.update-status', $inv->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="dropdown-item"><i class="bx bx-time me-2"></i> Pending</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.transport.invoices.update-status', $inv->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="paid">
                                                <button type="submit" class="dropdown-item text-success"><i class="bx bx-check-circle me-2"></i> Paid</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.transport.invoices.update-status', $inv->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bx bx-x-circle me-2"></i> Cancelled</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.transport.invoices.show', $inv->id) }}" class="btn btn-sm btn-outline-info" title="View / Reprint Toll Bill">
                                        <i class="bx bx-printer me-1"></i> Print
                                    </a>
                                    <form action="{{ route('admin.transport.invoices.destroy', $inv->id) }}" method="POST" class="d-inline" onsubmit="confirmDelete(event, this);">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete Invoice">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">No toll bills generated yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer clearfix">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
    function confirmDelete(event, form) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete this invoice! The associated LRs will be unbilled.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
@endsection
