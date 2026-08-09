@extends('admin.layouts.app')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h5 class="fw-bold mb-1">All Salary Slips</h5>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.driver-management.salary') }}">Driver Salary</a></li>
                    <li class="breadcrumb-item active">All Salary Slips</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('generate driver salary slips')
            <a href="{{ route('admin.driver-management.salary-slip') }}" class="btn btn-outline-primary"><i class="bx bx-receipt me-1"></i> Generate Slip</a>
            @endcan
            @can('view driver salary')
            <a href="{{ route('admin.driver-management.salary') }}" class="btn btn-outline-primary"><i class="bx bx-money me-1"></i> Salary Management</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Generated Salary Slips</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Driver ID</th>
                        <th>Phone</th>
                        <th>Month</th>
                        <th class="text-end">Salary</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net Payable</th>
                        <th>Generated</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slips as $slip)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $slip->driver?->name ?? 'N/A' }}</td>
                        <td>{{ $slip->driver?->driver_id ?? '-' }}</td>
                        <td>{{ $slip->driver?->phone ?? '-' }}</td>
                        <td>{{ Carbon\Carbon::create()->month($slip->month)->format('F') }} {{ $slip->year }}</td>
                        <td class="text-end">₹ {{ number_format($slip->salary_amount, 2) }}</td>
                        <td class="text-end text-danger">₹ {{ number_format($slip->total_deductions, 2) }}</td>
                        <td class="text-end fw-semibold">₹ {{ number_format($slip->net_payable, 2) }}</td>
                        <td>{{ $slip->generated_at ? $slip->generated_at->format('d-m-Y') : '-' }}</td>
                        <td class="text-center">
                            @canany(['view driver salary slips', 'generate driver salary slips'])
                            <a href="{{ route('admin.driver-management.salary-slip', ['driver_id' => $slip->driver_id, 'month' => $slip->month, 'year' => $slip->year]) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View"><i class="bx bx-show"></i></a>
                            @endcanany
                            @can('delete driver salary slips')
                            <form method="POST" action="{{ route('admin.driver-management.salary-slip.destroy', $slip) }}" class="d-inline" onsubmit="return confirm('Delete this salary slip?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No salary slips generated yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
