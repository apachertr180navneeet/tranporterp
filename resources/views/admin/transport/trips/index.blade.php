@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Trips</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Trips</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.transport.trips.index') }}" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search LR No, Consignor or Consignee..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                @if(request()->filled('search'))
                <div class="col-md-2">
                    <a href="{{ route('admin.transport.trips.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>LR No</th>
                        <th>Date</th>
                        <th>Consignor</th>
                        <th>Consignee</th>
                        <th>From → To</th>
                        <th>Weight</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trips as $trip)
                    <tr>
                        <td class="fw-semibold">{{ $trip->lr_no }}</td>
                        <td>{{ $trip->lr_date ? date('d M Y', strtotime($trip->lr_date)) : '-' }}</td>
                        <td>{{ $trip->consignor->name ?? '-' }}</td>
                        <td>{{ $trip->consignee->name ?? '-' }}</td>
                        <td>{{ $trip->originCity->name ?? '-' }} <i class="bx bx-chevron-right mx-1 text-muted"></i> {{ $trip->destinationCity->name ?? '-' }}</td>
                        <td>{{ number_format($trip->bultyItems->sum('weight'), 2) }} {{ $trip->bultyItems->pluck('unit')->filter()->unique()->values()->first() ?? 'kg' }}</td>
                        <td>₹{{ number_format($trip->total_amount, 2) }}</td>
                        <td>
                            @php
                                $tripStatus = $trip->trip?->status ?? 'pending';
                            @endphp
                            @if($trip->trip)
                                @can('edit trips')
                                <select class="form-select form-select-sm status-select" data-id="{{ $trip->trip->id }}" style="min-width:90px" {{ $tripStatus == 'complete' ? 'disabled' : '' }}>
                                    <option value="pending" {{ $tripStatus == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="complete" {{ $tripStatus == 'complete' ? 'selected' : '' }}>Complete</option>
                                    <option value="reject" {{ $tripStatus == 'reject' ? 'selected' : '' }}>Reject</option>
                                </select>
                                @else
                                @php
                                    $statusBadges = [
                                        'pending' => 'bg-label-warning',
                                        'complete' => 'bg-label-success',
                                        'reject' => 'bg-label-danger'
                                    ];
                                @endphp
                                <span class="badge {{ $statusBadges[$tripStatus] ?? 'bg-label-warning' }}">{{ ucfirst($tripStatus) }}</span>
                                @endcan
                            @else
                            <span class="badge bg-label-warning">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if($trip->trip)
                                    @can('edit trips')
                                    <a href="{{ route('admin.transport.trips.edit', $trip->trip->id) }}" class="btn btn-sm btn-icon btn-outline-warning" title="Edit Trip">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    @endcan
                                @else
                                    @can('create trips')
                                    <a href="{{ route('admin.transport.trips.create', $trip->id) }}" class="btn btn-sm btn-icon btn-outline-success" title="Add Trip">
                                        <i class="bx bx-plus-circle"></i>
                                    </a>
                                    @endcan
                                @endif
                                @can('view bulties')
                                <a href="{{ route('admin.transport.bulties.show', $trip->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View">
                                    <i class="bx bx-show"></i>
                                </a>
                                @endcan
                                @if($trip->material_document)
                                <a href="{{ $trip->material_document }}" target="_blank" class="btn btn-sm btn-icon btn-outline-info" title="View Document">
                                    <i class="bx bx-file"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <p class="text-muted mb-0">No Trips with Material Documents Found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($trips->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $trips->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script>
$(document).on('change', '.status-select', function() {
    const id = $(this).data('id');
    const status = $(this).val();
    $.ajax({
        url: '{{ url("admin/transport/trips") }}/' + id + '/toggle-status',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', status: status },
        success: function(res) {
            location.reload();
        }
    });
});
</script>
@endsection
