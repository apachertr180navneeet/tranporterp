<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Bulty;
use App\Models\Trip;
use App\Models\TripFuelDetail;
use App\Models\Consignee;
use App\Models\FuelCompany;
use App\Models\FuelPump;
use App\Models\AdBlueCompany;
use App\Models\TripFastTagDetail;
use App\Models\TripAdBlueDetail;
use App\Models\TripOtherAmountDetail;
use App\Models\TripAdvanceDetail;
use App\Models\MaintenanceHistory;
use App\Models\Breakdown;
use App\Models\SparePart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\GstMaster;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\Reports\ReportExport;

class ReportController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.reports.vehicle');
    }

    public function vehicleReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $vehicleGroupCols = [
            'vehicles.id',
            'vehicles.vehicle_number', 'vehicles.vehicle_type', 'vehicles.make_model',
            'vehicles.capacity_tons', 'vehicles.owner_name', 'vehicles.owner_phone',
            'vehicles.insurance_expiry', 'vehicles.fitness_expiry', 'vehicles.permit_expiry',
            'vehicles.pollution_expiry', 'vehicles.status',
            'vehicles.registration_cert', 'vehicles.insurance_doc',
            'vehicles.fitness_doc', 'vehicles.permit_doc', 'vehicles.pollution_cert',
            'vehicles.created_at', 'vehicles.updated_at', 'vehicles.deleted_at',
        ];

        $fuelSubquery = DB::table('trip_fuel_details')
            ->select('trip_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(amount) as total_amt')
            )
            ->groupBy('trip_id');

        $kmSubquery = DB::table('trip_fuel_details')
            ->join('trips', 'trips.id', '=', 'trip_fuel_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(MAX(trip_fuel_details.km) - MIN(trip_fuel_details.km), 0) as total_km'))
            ->whereNull('bulties.deleted_at')
            ->whereNotIn('bulties.status', ['pending', 'planned']);

        if ($companyId && $companyId !== 'all') {
            $kmSubquery->where('bulties.company_id', $companyId);
        }
        if ($request->filled('vehicle_id')) {
            $kmSubquery->where('bulties.vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('date_from')) {
            $kmSubquery->whereDate('bulties.lr_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $kmSubquery->whereDate('bulties.lr_date', '<=', $request->date_to);
        }
        $kmSubquery->groupBy('bulties.vehicle_id');

        $query = Vehicle::query()
            ->select('vehicles.*',
                DB::raw('COUNT(bulties.id) as total_trips'),
                DB::raw('COALESCE(SUM(fuel_summary.total_qty), 0) as total_fuel_qty'),
                DB::raw('COALESCE(SUM(fuel_summary.total_amt), 0) as total_fuel_amount'),
                DB::raw('COALESCE(MAX(km_summary.total_km), 0) as total_km'),
                DB::raw('COALESCE(SUM(trips.advance_total_amount), 0) as total_advance')
            )
            ->leftJoin('bulties', function ($join) {
                $join->on('bulties.vehicle_id', '=', 'vehicles.id')
                    ->whereNull('bulties.deleted_at')
                    ->whereNotIn('bulties.status', ['pending', 'planned']);
            })
            ->leftJoin('trips', 'trips.builty_id', '=', 'bulties.id')
            ->leftJoinSub($fuelSubquery, 'fuel_summary', function ($join) {
                $join->on('fuel_summary.trip_id', '=', 'trips.id');
            })
            ->leftJoinSub($kmSubquery, 'km_summary', function ($join) {
                $join->on('km_summary.vehicle_id', '=', 'vehicles.id');
            })
            ->groupBy($vehicleGroupCols);

        if ($companyId && $companyId !== 'all') {
            $query->where('bulties.company_id', $companyId);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicles.id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('bulties.lr_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('bulties.lr_date', '<=', $request->date_to);
        }

        $vehicles = $query->orderBy('vehicles.vehicle_number')->paginate(20);

        $companies = $user->isSuperAdmin()
            ? Company::where('status', 'active')->orderBy('name')->get()
            : collect([]);

        $vehicleList = Vehicle::where('status', 'active')
            ->orderBy('vehicle_number')
            ->get();

        return view('admin.reports.vehicle', compact('vehicles', 'companies', 'vehicleList'));
    }

    public function tripReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = Bulty::with([
            'vehicle', 'driver', 'consignor', 'consignee',
            'originCity', 'destinationCity',
            'trip.fuelDetails.fuelPump', 'trip.fuelDetails.fuelCompany',
            'trip.fastTagDetails', 'trip.adblueDetails', 'trip.otherAmountDetails',
        ])
            ->whereNotIn('status', ['pending', 'planned']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lr_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lr_date', '<=', $request->date_to);
        }

        if ($request->filled('trip_status')) {
            $query->whereHas('trip', fn($q) => $q->where('status', $request->trip_status));
        }

        $trips = $query->orderBy('lr_date', 'desc')->paginate(20);

        return view('admin.reports.trip', compact('trips'));
    }

    public function driverTripReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = Bulty::with([
            'driver', 'vehicle',
            'trip.fuelDetails',
            'trip.fastTagDetails',
            'trip.adblueDetails',
            'trip.otherAmountDetails',
            'trip.advanceDetails',
        ])
            ->whereNotNull('driver_id')
            ->whereNotIn('status', ['pending', 'planned']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lr_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lr_date', '<=', $request->date_to);
        }

        $trips = $request->filled('driver_id')
            ? $query->orderBy('driver_id')->paginate(50)
            : collect([]);

        $drivers = Driver::where('status', 'active')
            ->orderBy('name')->get();

        return view('admin.reports.driver_trip', compact('trips', 'drivers'));
    }

    public function customerLedger(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $consignees = Consignee::where('status', 'active')
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get();

        $selectedConsignee = null;
        $transactions = collect([]);
        $summary = null;

        if ($request->filled('consignee_id')) {
            $selectedConsignee = Consignee::find($request->consignee_id);

            $query = Bulty::with(['originCity', 'destinationCity', 'vehicle'])
                ->where('consignee_id', $request->consignee_id)
                ->whereNotIn('status', ['pending', 'planned']);

            if ($companyId && $companyId !== 'all') {
                $query->where('company_id', $companyId);
            }

            $transactions = $query->orderBy('lr_date', 'desc')->paginate(25);

            $summaryQuery = Bulty::where('consignee_id', $request->consignee_id)
                ->whereNotIn('status', ['pending', 'planned']);
            if ($companyId && $companyId !== 'all') {
                $summaryQuery->where('company_id', $companyId);
            }
            $summary = $summaryQuery->select(DB::raw('COUNT(*) as total_lr'),
                    DB::raw('COALESCE(SUM(freight_charges), 0) as total_freight'),
                    DB::raw('COALESCE(SUM(gst_amount), 0) as total_gst'),
                    DB::raw('COALESCE(SUM(other_charges), 0) as total_other'),
                    DB::raw('COALESCE(SUM(total_amount), 0) as total_amount'),
                    DB::raw('COALESCE(SUM(advance_amount), 0) as total_advance'),
                    DB::raw('COALESCE(SUM(remaining_amount), 0) as total_remaining'))
                ->first();
        }

        return view('admin.reports.customer_ledger', compact('consignees', 'selectedConsignee', 'transactions', 'summary'));
    }

    public function tripReports(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = Bulty::with([
            'vehicle', 'driver', 'consignor', 'consignee',
            'originCity', 'destinationCity',
            'trip.fuelDetails.fuelPump', 'trip.fuelDetails.fuelCompany',
            'trip.fastTagDetails', 'trip.adblueDetails', 'trip.otherAmountDetails',
        ])
            ->whereNotIn('status', ['pending', 'planned'])
            ->select('bulties.*');

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lr_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lr_date', '<=', $request->date_to);
        }

        if ($request->filled('trip_status')) {
            $query->whereHas('trip', fn($q) => $q->where('status', $request->trip_status));
        }

        $trips = $query->orderBy('lr_date', 'desc')->paginate(20);

        $vehicleList = Vehicle::where('status', 'active')
            ->orderBy('vehicle_number')
            ->get();

        return view('admin.reports.trip_reports', compact('trips', 'vehicleList'));
    }

    public function fuelReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = TripFuelDetail::with([
            'fuelPump', 'fuelCompany', 'trip.builty.vehicle',
        ]);

        if ($companyId && $companyId !== 'all') {
            $query->whereHas('trip.builty', fn($q) => $q->where('company_id', $companyId));
        }

        if ($request->filled('vehicle_id')) {
            $query->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        if ($request->filled('fuel_company_id')) {
            $query->where('fuel_company_id', $request->fuel_company_id);
        }

        if ($request->filled('fuel_pump_id')) {
            $query->where('fuel_pump_id', $request->fuel_pump_id);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $summary = (clone $query)->select(DB::raw('COALESCE(SUM(quantity), 0) as total_qty'),
            DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
            DB::raw('COALESCE(SUM(km), 0) as total_km'))
            ->first();

        $fuelDetails = $query->orderBy('date', 'desc')->paginate(20);

        $vehicleList = Vehicle::where('status', 'active')
            ->orderBy('vehicle_number')
            ->get();

        $fuelCompanies = FuelCompany::orderBy('name')->get();
        $fuelPumps = FuelPump::orderBy('name')->get();

        return view('admin.reports.fuel', compact('fuelDetails', 'vehicleList', 'fuelCompanies', 'fuelPumps', 'summary'));
    }

    public function adblueReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = TripAdBlueDetail::with([
            'adblueCompany', 'trip.builty.vehicle',
        ]);

        if ($companyId && $companyId !== 'all') {
            $query->whereHas('trip.builty', fn($q) => $q->where('company_id', $companyId));
        }

        if ($request->filled('vehicle_id')) {
            $query->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        }

        if ($request->filled('adblue_company_id')) {
            $query->where('adblue_company_id', $request->adblue_company_id);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $summary = (clone $query)->select(
            DB::raw('COALESCE(SUM(quantity), 0) as total_qty'),
            DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
            DB::raw('COALESCE(SUM(km), 0) as total_km')
        )->first();

        $adblueDetails = $query->orderBy('date', 'desc')->paginate(20);

        $vehicleList = Vehicle::where('status', 'active')
            ->orderBy('vehicle_number')
            ->get();

        $adblueCompanies = AdBlueCompany::orderBy('name')->get();

        return view('admin.reports.adblue', compact('adblueDetails', 'vehicleList', 'adblueCompanies', 'summary'));
    }

    public function vehicleUtilization(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $vehicleGroupCols = [
            'vehicles.id',
            'vehicles.vehicle_number', 'vehicles.vehicle_type', 'vehicles.make_model',
            'vehicles.capacity_tons', 'vehicles.owner_name', 'vehicles.owner_phone',
            'vehicles.insurance_expiry', 'vehicles.fitness_expiry', 'vehicles.permit_expiry',
            'vehicles.pollution_expiry', 'vehicles.status',
            'vehicles.registration_cert', 'vehicles.insurance_doc',
            'vehicles.fitness_doc', 'vehicles.permit_doc', 'vehicles.pollution_cert',
            'vehicles.created_at', 'vehicles.updated_at', 'vehicles.deleted_at',
        ];

        $fuelSubquery = DB::table('trip_fuel_details')
            ->select('trip_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(amount) as total_amt')
            )
            ->groupBy('trip_id');

        $advanceSubquery = DB::table('trip_advance_details')
            ->select('trip_id', DB::raw('SUM(advance_amount) as total_advance'))
            ->groupBy('trip_id');

        $kmSubquery = DB::table('trip_fuel_details')
            ->join('trips', 'trips.id', '=', 'trip_fuel_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(MAX(trip_fuel_details.km) - MIN(trip_fuel_details.km), 0) as total_km'))
            ->whereNull('bulties.deleted_at')
            ->whereNotIn('bulties.status', ['pending', 'planned']);

        if ($companyId && $companyId !== 'all') {
            $kmSubquery->where('bulties.company_id', $companyId);
        }
        if ($request->filled('vehicle_id')) {
            $kmSubquery->where('bulties.vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('date_from')) {
            $kmSubquery->whereDate('bulties.lr_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $kmSubquery->whereDate('bulties.lr_date', '<=', $request->date_to);
        }
        $kmSubquery->groupBy('bulties.vehicle_id');

        $query = Vehicle::query()
            ->select('vehicles.*',
                DB::raw('COUNT(bulties.id) as total_trips'),
                DB::raw('COALESCE(SUM(fuel_summary.total_qty), 0) as total_fuel_qty'),
                DB::raw('COALESCE(SUM(fuel_summary.total_amt), 0) as total_fuel_amount'),
                DB::raw('COALESCE(MAX(km_summary.total_km), 0) as total_km'),
                DB::raw('COALESCE(SUM(bulties.total_amount), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(trips.fasttag_total_amount), 0) as total_fasttag'),
                DB::raw('COALESCE(SUM(trips.adblue_total_amount), 0) as total_adblue'),
                DB::raw('COALESCE(SUM(trips.other_amount), 0) as total_other_expense'),
                DB::raw('COALESCE(SUM(advance_summary.total_advance), 0) as total_advance')
            )
            ->leftJoin('bulties', function ($join) {
                $join->on('bulties.vehicle_id', '=', 'vehicles.id')
                    ->whereNull('bulties.deleted_at')
                    ->whereNotIn('bulties.status', ['pending', 'planned']);
            })
            ->leftJoin('trips', 'trips.builty_id', '=', 'bulties.id')
            ->leftJoinSub($fuelSubquery, 'fuel_summary', function ($join) {
                $join->on('fuel_summary.trip_id', '=', 'trips.id');
            })
            ->leftJoinSub($advanceSubquery, 'advance_summary', function ($join) {
                $join->on('advance_summary.trip_id', '=', 'trips.id');
            })
            ->leftJoinSub($kmSubquery, 'km_summary', function ($join) {
                $join->on('km_summary.vehicle_id', '=', 'vehicles.id');
            })
            ->groupBy($vehicleGroupCols);

        if ($companyId && $companyId !== 'all') {
            $query->where('bulties.company_id', $companyId);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicles.id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('bulties.lr_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('bulties.lr_date', '<=', $request->date_to);
        }

        $vehicles = $query->orderBy('vehicles.vehicle_number')->paginate(20);

        $companies = $user->isSuperAdmin()
            ? Company::where('status', 'active')->orderBy('name')->get()
            : collect([]);

        $vehicleList = Vehicle::where('status', 'active')
            ->orderBy('vehicle_number')
            ->get();

        return view('admin.reports.vehicle_utilization', compact('vehicles', 'companies', 'vehicleList'));
    }

    public function misReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate = $request->filled('to_date') ? $request->to_date : null;

        $whereBulty = function ($q) use ($companyId) {
            $q->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId));
        };

        // Single query for bulty-level aggregates
        $bultyAgg = Bulty::select(
                DB::raw('COUNT(*) as total_lr'),
                DB::raw('COALESCE(SUM(total_amount),0) as total_revenue'),
                DB::raw('COALESCE(SUM(advance_amount),0) as total_advance'),
                DB::raw('COALESCE(SUM(remaining_amount),0) as total_due')
            )
            ->whereNotIn('status', ['pending', 'planned'])
            ->where($whereBulty)
            ->when($fromDate, fn($q, $d) => $q->whereDate('lr_date', '>=', $d))
            ->when($toDate, fn($q, $d) => $q->whereDate('lr_date', '<=', $d))
            ->first();

        $totalLR = (int)($bultyAgg->total_lr ?? 0);
        $totalRevenue = (float)($bultyAgg->total_revenue ?? 0);
        $totalAdvance = (float)($bultyAgg->total_advance ?? 0);
        $totalDue = (float)($bultyAgg->total_due ?? 0);

        $totalVehicles = Vehicle::where('status', 'active')->count();
        $totalDrivers = Driver::where('status', 'active')->count();

        $activeTrips = Trip::query()
            ->when($companyId && $companyId !== 'all', fn($q) => $q->whereHas('builty', fn($q) => $q->where('company_id', $companyId)))
            ->whereIn('status', ['running', 'in_transit', 'complete'])->count();

        $thisMonth = now()->startOfMonth();

        // Single query for this-month aggregates
        $monthAgg = Bulty::select(
                DB::raw('COUNT(*) as month_lr'),
                DB::raw('COALESCE(SUM(total_amount),0) as month_revenue')
            )
            ->whereNotIn('status', ['pending', 'planned'])
            ->where($whereBulty)
            ->whereDate('lr_date', '>=', $thisMonth)
            ->when($fromDate, fn($q, $d) => $q->whereDate('lr_date', '>=', $d))
            ->when($toDate, fn($q, $d) => $q->whereDate('lr_date', '<=', $d))
            ->first();

        $monthLR = (int)($monthAgg->month_lr ?? 0);
        $monthRevenue = (float)($monthAgg->month_revenue ?? 0);

        // Get bulty IDs once for detail queries
        $bultyIds = Bulty::whereNotIn('status', ['pending', 'planned'])
            ->where($whereBulty)
            ->when($fromDate, fn($q, $d) => $q->whereDate('lr_date', '>=', $d))
            ->when($toDate, fn($q, $d) => $q->whereDate('lr_date', '<=', $d))
            ->pluck('id');

        // Combined fuel query
        $fuelAgg = TripFuelDetail::select(
                DB::raw('COALESCE(SUM(quantity),0) as total_qty'),
                DB::raw('COALESCE(SUM(amount),0) as total_amt')
            )
            ->whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->first();

        $totalFuelQty = (float)($fuelAgg->total_qty ?? 0);
        $totalFuelAmt = (float)($fuelAgg->total_amt ?? 0);

        $totalFastTag = (float)TripFastTagDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('transaction_time', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('transaction_time', '<=', $toDate))
            ->sum('amount');
        $totalAdBlue = (float)TripAdBlueDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');
        $totalOtherExp = (float)TripOtherAmountDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');
        
        $totalTripAdvance = (float)Trip::whereIn('builty_id', $bultyIds)->sum('advance_total_amount');

        $topVehicles = Bulty::select('vehicle_id',
            DB::raw('COUNT(*) as trip_count'),
            DB::raw('COALESCE(SUM(total_amount), 0) as revenue'),
            DB::raw('COALESCE(SUM(freight_charges), 0) as freight'))
            ->whereNotNull('vehicle_id')
            ->whereNotIn('status', ['pending', 'planned'])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->groupBy('vehicle_id')
            ->orderByDesc('trip_count')
            ->limit(10)
            ->with('vehicle')
            ->get();

        $recentTrips = Bulty::with(['vehicle', 'driver', 'originCity', 'destinationCity', 'trip'])
            ->whereNotIn('status', ['pending', 'planned'])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.reports.mis', compact(
            'totalLR', 'totalRevenue', 'totalAdvance', 'totalDue',
            'totalVehicles', 'totalDrivers', 'activeTrips',
            'monthLR', 'monthRevenue',
            'totalFuelQty', 'totalFuelAmt',
            'totalFastTag', 'totalAdBlue', 'totalOtherExp', 'totalTripAdvance',
            'topVehicles', 'recentTrips', 'fromDate', 'toDate'
        ));
    }

    public function expenseReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? $request->to_date : now()->toDateString();

        $companyFilter = fn($q) => $q->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId));

        // Trip Expenses
        $tripCompanyFilter = fn($q) => $q->when($companyId && $companyId !== 'all', fn($q) => $q->whereHas('trip.builty', fn($q) => $q->where('company_id', $companyId)));

        $tripFuelQuery = TripFuelDetail::whereBetween('date', [$fromDate, $toDate]);
        $tripFastTagQuery = TripFastTagDetail::where(function($q) use ($fromDate, $toDate) {
            $q->whereBetween('transaction_time', [$fromDate, $toDate . ' 23:59:59'])
              ->orWhere(function($q2) use ($fromDate, $toDate) {
                  $q2->whereNull('transaction_time')
                     ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
              });
        });
        $tripAdBlueQuery = TripAdBlueDetail::whereBetween('date', [$fromDate, $toDate]);
        $tripOtherQuery = TripOtherAmountDetail::whereBetween('date', [$fromDate, $toDate]);
        $tripAdvanceQuery = TripAdvanceDetail::whereBetween('date', [$fromDate, $toDate]);

        $tripCompanyFilter($tripFuelQuery);
        $tripCompanyFilter($tripFastTagQuery);
        $tripCompanyFilter($tripAdBlueQuery);
        $tripCompanyFilter($tripOtherQuery);
        $tripCompanyFilter($tripAdvanceQuery);

        $vehicleId = $request->vehicle_id;
        if ($request->filled('vehicle_id')) {
            $tripFuelQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripFastTagQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripAdBlueQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripOtherQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripAdvanceQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
        }

        $totalFuelAmt = (clone $tripFuelQuery)->sum('amount');
        $totalFuelQty = (clone $tripFuelQuery)->sum('quantity');
        $totalFastTag = (clone $tripFastTagQuery)->sum('amount');
        $totalAdBlue = (clone $tripAdBlueQuery)->sum('amount');
        $totalOtherExp = (clone $tripOtherQuery)->sum('amount');
        $totalTripAdvance = (clone $tripAdvanceQuery)->sum('advance_amount');

        // Maintenance Expenses
        $maintenanceQuery = MaintenanceHistory::whereBetween('service_date', [$fromDate, $toDate]);
        $breakdownQuery = Breakdown::whereBetween('breakdown_date', [$fromDate, $toDate]);
        $sparePartQuery = SparePart::whereBetween('part_change_date', [$fromDate, $toDate]);

        $companyFilter($maintenanceQuery);
        $companyFilter($breakdownQuery);
        $companyFilter($sparePartQuery);

        if ($request->filled('vehicle_id')) {
            $maintenanceQuery->where('vehicle_id', $vehicleId);
            $breakdownQuery->where('vehicle_id', $vehicleId);
            $sparePartQuery->where('vehicle_id', $vehicleId);
        }

        $totalMaintenance = (clone $maintenanceQuery)->sum('cost');
        $totalBreakdown = (clone $breakdownQuery)->sum('repair_cost');
        $totalSparePart = (clone $sparePartQuery)->sum('amount');

        // Vehicle-wise summary (single query instead of N+1)
        $vehicleSubQuery = Vehicle::where('status', 'active')
            ->when($request->filled('vehicle_id'), fn($q) => $q->where('id', $vehicleId))
            ->select('vehicles.*');

        $fuelSub = DB::table('trip_fuel_details')
            ->join('trips', 'trips.id', '=', 'trip_fuel_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->whereBetween('trip_fuel_details.date', [$fromDate, $toDate])
            ->whereNull('bulties.deleted_at')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(SUM(trip_fuel_details.amount),0) as fuel_expense'))
            ->groupBy('bulties.vehicle_id');

        $fasttagSub = DB::table('trip_fast_tag_details')
            ->join('trips', 'trips.id', '=', 'trip_fast_tag_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->where(function($q) use ($fromDate, $toDate) {
                $q->whereBetween('trip_fast_tag_details.transaction_time', [$fromDate, $toDate . ' 23:59:59'])
                  ->orWhere(function($q2) use ($fromDate, $toDate) {
                      $q2->whereNull('trip_fast_tag_details.transaction_time')
                         ->whereBetween('trip_fast_tag_details.created_at', [$fromDate, $toDate . ' 23:59:59']);
                  });
            })
            ->whereNull('bulties.deleted_at')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(SUM(trip_fast_tag_details.amount),0) as fasttag_expense'))
            ->groupBy('bulties.vehicle_id');

        $adblueSub = DB::table('trip_adblue_details')
            ->join('trips', 'trips.id', '=', 'trip_adblue_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->whereBetween('trip_adblue_details.date', [$fromDate, $toDate])
            ->whereNull('bulties.deleted_at')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(SUM(trip_adblue_details.amount),0) as adblue_expense'))
            ->groupBy('bulties.vehicle_id');

        $otherSub = DB::table('trip_other_amount_details')
            ->join('trips', 'trips.id', '=', 'trip_other_amount_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->whereBetween('trip_other_amount_details.date', [$fromDate, $toDate])
            ->whereNull('bulties.deleted_at')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(SUM(trip_other_amount_details.amount),0) as other_expense'))
            ->groupBy('bulties.vehicle_id');

        $advanceSub = DB::table('trip_advance_details')
            ->join('trips', 'trips.id', '=', 'trip_advance_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->whereBetween('trip_advance_details.date', [$fromDate, $toDate])
            ->whereNull('bulties.deleted_at')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(SUM(trip_advance_details.advance_amount),0) as advance_expense'))
            ->groupBy('bulties.vehicle_id');

        $maintenanceSub = DB::table('maintenance_history')
            ->whereBetween('service_date', [$fromDate, $toDate])
            ->select('vehicle_id', DB::raw('COALESCE(SUM(cost),0) as maintenance_cost'))
            ->groupBy('vehicle_id');

        $breakdownSub = DB::table('breakdowns')
            ->whereBetween('breakdown_date', [$fromDate, $toDate])
            ->select('vehicle_id', DB::raw('COALESCE(SUM(repair_cost),0) as breakdown_cost'))
            ->groupBy('vehicle_id');

        $sparePartSub = DB::table('spare_parts')
            ->whereBetween('part_change_date', [$fromDate, $toDate])
            ->select('vehicle_id', DB::raw('COALESCE(SUM(amount),0) as spare_part_cost'))
            ->groupBy('vehicle_id');

        $vehicles = Vehicle::fromSub($vehicleSubQuery, 'vehicles')
            ->leftJoinSub($fuelSub, 'fuel', 'fuel.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($fasttagSub, 'fasttag', 'fasttag.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($adblueSub, 'adblue', 'adblue.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($otherSub, 'other', 'other.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($advanceSub, 'adv', 'adv.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($maintenanceSub, 'maint', 'maint.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($breakdownSub, 'bd', 'bd.vehicle_id', '=', 'vehicles.id')
            ->leftJoinSub($sparePartSub, 'sp', 'sp.vehicle_id', '=', 'vehicles.id')
            ->select('vehicles.*',
                DB::raw('COALESCE(fuel.fuel_expense,0) as fuel_expense'),
                DB::raw('COALESCE(fasttag.fasttag_expense,0) as fasttag_expense'),
                DB::raw('COALESCE(adblue.adblue_expense,0) as adblue_expense'),
                DB::raw('COALESCE(other.other_expense,0) as other_expense'),
                DB::raw('COALESCE(adv.advance_expense,0) as advance_expense'),
                DB::raw('COALESCE(maint.maintenance_cost,0) as maintenance_cost'),
                DB::raw('COALESCE(bd.breakdown_cost,0) as breakdown_cost'),
                DB::raw('COALESCE(sp.spare_part_cost,0) as spare_part_cost')
            )
            ->get()
            ->map(function ($vehicle) {
                $vehicle->total_expense = $vehicle->fuel_expense + $vehicle->fasttag_expense
                    + $vehicle->adblue_expense + $vehicle->other_expense + $vehicle->advance_expense
                    + $vehicle->maintenance_cost + $vehicle->breakdown_cost + $vehicle->spare_part_cost;
                return $vehicle;
            })
            ->filter(fn($v) => $v->total_expense > 0)
            ->sortByDesc('total_expense')
            ->values();

        // Recent expense entries
        $recentFuelDetails = (clone $tripFuelQuery)->with('trip.builty.vehicle', 'fuelCompany', 'fuelPump')
            ->orderBy('date', 'desc')->limit(20)->get();
        $recentFastTagDetails = (clone $tripFastTagQuery)->with('trip.builty.vehicle')
            ->orderBy('transaction_time', 'desc')->limit(20)->get();
        $recentAdBlueDetails = (clone $tripAdBlueQuery)->with('trip.builty.vehicle', 'adblueCompany')
            ->orderBy('date', 'desc')->limit(20)->get();
        $recentOtherDetails = (clone $tripOtherQuery)->with('trip.builty.vehicle')
            ->orderBy('date', 'desc')->limit(20)->get();

        $totalTripExpenses = $totalFuelAmt + $totalFastTag + $totalAdBlue + $totalOtherExp + $totalTripAdvance;
        $totalMaintenanceExpenses = $totalMaintenance + $totalBreakdown + $totalSparePart;
        $grandTotal = $totalTripExpenses + $totalMaintenanceExpenses;

        return view('admin.reports.expense_management', compact(
            'totalFuelAmt', 'totalFuelQty', 'totalFastTag', 'totalAdBlue', 'totalOtherExp', 'totalTripAdvance',
            'totalMaintenance', 'totalBreakdown', 'totalSparePart',
            'totalTripExpenses', 'totalMaintenanceExpenses', 'grandTotal',
            'vehicles', 'recentFuelDetails', 'recentFastTagDetails',
            'recentAdBlueDetails', 'recentOtherDetails',
            'fromDate', 'toDate', 'companyId'
        ));
    }

    public function vehicleDocumentReport(Request $request)
    {
        if (!auth()->user()->can('view vehicle document report') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $companyName = 'N/A';
        if ($user->isSuperAdmin()) {
            $companyId = $request->filled('company_id') ? $request->company_id : session('current_company_id');
            if ($companyId && $companyId !== 'all') {
                $comp = Company::find($companyId);
                $companyName = $comp ? $comp->name : 'N/A';
            } else {
                $companyName = 'All Companies';
            }
        } else {
            $companyName = $user->company ? $user->company->name : 'N/A';
        }

        $thresholdDays = $request->filled('threshold_days') ? (int)$request->threshold_days : 30;

        $documentFields = [
            'insurance_expiry' => 'Insurance',
            'fitness_expiry' => 'Fitness Certificate',
            'permit_expiry' => 'Permit',
            'pollution_expiry' => 'Pollution Certificate',
        ];

        $selectedDoc = $request->input('document_type');
        $threshold = now()->addDays($thresholdDays)->toDateString();

        $query = Vehicle::where('status', 'active');

        if ($request->filled('vehicle_id')) {
            $query->where('id', $request->vehicle_id);
        }

        $documents = collect();
        $vehicleFields = array_keys($documentFields);

        $whereConditions = [];
        $bindings = [];
        foreach ($vehicleFields as $field) {
            if ($selectedDoc && $selectedDoc !== $field) {
                continue;
            }
            $whereConditions[] = "($field IS NOT NULL AND $field <= ?)";
            $bindings[] = $threshold;
        }

        if (!empty($whereConditions)) {
            $unionSql = '';
            $unionBindings = [];
            $first = true;
            foreach ($vehicleFields as $field) {
                if ($selectedDoc && $selectedDoc !== $field) {
                    continue;
                }
                $label = $documentFields[$field];
                $sql = "(SELECT id as vehicle_id, vehicle_number, ? as document, ? as document_field, $field as expiry_date, DATEDIFF(?, $field) as days_left FROM vehicles WHERE status = 'active' AND $field IS NOT NULL AND $field <= ?)";
                if ($request->filled('vehicle_id')) {
                    $sql .= " AND id = ?";
                    $unionBindings = array_merge($unionBindings, [$label, $field, $threshold, $threshold, (int)$request->vehicle_id]);
                } else {
                    $unionBindings = array_merge($unionBindings, [$label, $field, $threshold, $threshold]);
                }
                if ($first) {
                    $unionSql = $sql;
                    $first = false;
                } else {
                    $unionSql .= " UNION ALL " . $sql;
                }
            }
            $documents = collect(DB::select($unionSql, $unionBindings))
                ->map(function ($d) use ($companyName) {
                    $arr = (array) $d;
                    $arr['company_name'] = $companyName;
                    return $arr;
                })
                ->sortBy('days_left')
                ->values();
        }

        $documents = $documents->sortBy('days_left')->values();

        $totalExpired = $documents->filter(fn($d) => $d['days_left'] <= 0)->count();
        $totalWarning = $documents->filter(fn($d) => $d['days_left'] > 0 && $d['days_left'] <= 7)->count();
        $totalUpcoming = $documents->filter(fn($d) => $d['days_left'] > 7)->count();

        return view('admin.reports.vehicle_documents', compact(
            'documents', 'documentFields', 'thresholdDays',
            'totalExpired', 'totalWarning', 'totalUpcoming'
        ));
    }

    public function usersReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $data = $this->getUserReport($request, $companyId);

        return view('admin.reports.users', $data);
    }

    public function companiesReport(Request $request)
    {
        $data = $this->getCompanyReport($request);

        return view('admin.reports.companies', $data);
    }

    public function activityReport(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $data = $this->getActivityReport($request, $companyId);

        return view('admin.reports.activity', $data);
    }

    public function rolesReport(Request $request)
    {
        $data = $this->getRoleReport($request);

        return view('admin.reports.roles', $data);
    }

    private function getUserReport(Request $request, $companyId = null)
    {
        $query = User::with(['company', 'branch', 'roles']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        $roleStats = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->get();

        $companies = Company::where('status', 'active')->get();

        return [
            'users' => $users,
            'roleStats' => $roleStats,
            'companies' => $companies,
        ];
    }

    private function getCompanyReport(Request $request)
    {
        $query = Company::withCount(['branches', 'users']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $companies = $query->orderBy('created_at', 'desc')->paginate(20);

        $statusStats = Company::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'companies' => $companies,
            'statusStats' => $statusStats,
        ];
    }

    private function getActivityReport(Request $request, $companyId = null)
    {
        $query = ActivityLog::with(['user', 'company', 'branch']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        $actions = ActivityLog::distinct()->pluck('action');
        $users = User::active()->get();

        return [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
        ];
    }

    private function getRoleReport(Request $request)
    {
        $roles = Role::with(['permissions', 'users'])->paginate(20);

        $permissionUsage = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('permissions.name', DB::raw('COUNT(*) as role_count'))
            ->groupBy('permissions.name')
            ->orderBy('role_count', 'desc')
            ->limit(20)
            ->get();

        return [
            'roles' => $roles,
            'permissionUsage' => $permissionUsage,
        ];
    }

    public function exportUsers(Request $request)
    {
        $query = User::with(['company', 'branch', 'roles'])->select('id', 'first_name', 'last_name', 'email', 'phone', 'company_id', 'branch_id', 'status', 'created_at');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->get();

        $csvHeader = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Company', 'Branch', 'Status', 'Created At'];
        $csvData = [];

        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->first_name,
                $user->last_name,
                $user->email,
                $user->phone,
                $user->company->name ?? 'N/A',
                $user->branch->name ?? 'N/A',
                $user->status,
                $user->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $fileName = 'users_report_' . now()->format('Y-m-d_His') . '.csv';
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        $file = fopen($filePath, 'w');
        fputcsv($file, $csvHeader);
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        ActivityLog::log('report_exported', "Exported users report");

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportCompanies(Request $request)
    {
        $query = Company::withCount(['branches', 'users']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $companies = $query->get();

        $csvHeader = ['ID', 'Name', 'Email', 'Phone', 'Branches', 'Users', 'Status', 'Created At'];
        $csvData = [];

        foreach ($companies as $company) {
            $csvData[] = [
                $company->id,
                $company->name,
                $company->email ?? 'N/A',
                $company->phone ?? 'N/A',
                $company->branches_count,
                $company->users_count,
                $company->status,
                $company->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $fileName = 'companies_report_' . now()->format('Y-m-d_His') . '.csv';
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        $file = fopen($filePath, 'w');
        fputcsv($file, $csvHeader);
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        ActivityLog::log('report_exported', "Exported companies report");

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportActivity(Request $request)
    {
        $query = ActivityLog::with(['user', 'company'])->select('id', 'user_id', 'action', 'description', 'company_id', 'ip_address', 'created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->get();

        $csvHeader = ['ID', 'User', 'Action', 'Description', 'Company', 'IP Address', 'Date'];
        $csvData = [];

        foreach ($logs as $log) {
            $csvData[] = [
                $log->id,
                $log->user->full_name ?? 'System',
                $log->action,
                $log->description,
                $log->company->name ?? 'N/A',
                $log->ip_address ?? 'N/A',
                $log->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $fileName = 'activity_report_' . now()->format('Y-m-d_His') . '.csv';
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        $file = fopen($filePath, 'w');
        fputcsv($file, $csvHeader);
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        ActivityLog::log('report_exported', "Exported activity report");

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function gstTaxReport(Request $request)
    {
        if (!auth()->user()->can('view gst tax report') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? $request->to_date : now()->toDateString();

        $query = Bulty::with('gstMaster', 'consignor', 'consignee', 'vehicle', 'originCity', 'destinationCity')
            ->where('status', 'delivered')
            ->whereNotNull('gst_master_id')
            ->whereBetween('lr_date', [$fromDate, $toDate]);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('gst_master_id')) {
            $query->where('gst_master_id', $request->gst_master_id);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $bulties = $query->orderBy('lr_date', 'desc')->get();

        $totalBills = $bulties->count();
        $totalFreight = $bulties->sum('freight_charges');
        $totalGst = $bulties->sum('gst_amount');
        $totalOtherCharges = $bulties->sum('other_charges');
        $totalAmount = $bulties->sum('total_amount');

        $gstBreakdown = Bulty::select('gst_master_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(freight_charges),0) as freight'),
                DB::raw('COALESCE(SUM(gst_amount),0) as gst'),
                DB::raw('COALESCE(SUM(other_charges),0) as other'),
                DB::raw('COALESCE(SUM(total_amount),0) as total')
            )
            ->where('status', 'delivered')
            ->whereNotNull('gst_master_id')
            ->whereBetween('lr_date', [$fromDate, $toDate])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->when($request->filled('gst_master_id'), fn($q) => $q->where('gst_master_id', $request->gst_master_id))
            ->when($request->filled('vehicle_id'), fn($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->groupBy('gst_master_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $rate = GstMaster::find($row->gst_master_id)?->gst_rate ?? 'N/A';
                return [$rate => [
                    'count' => $row->count,
                    'freight' => $row->freight,
                    'gst' => $row->gst,
                    'other' => $row->other,
                    'total' => $row->total,
                ]];
            });

        $gstMasters = GstMaster::where('status', 'active')->orderBy('gst_rate')->get();
        $vehicles = Vehicle::where('status', 'active')
            ->orderBy('vehicle_number')->get();

        return view('admin.reports.gst_tax', compact(
            'bulties', 'fromDate', 'toDate', 'companyId',
            'totalBills', 'totalFreight', 'totalGst', 'totalOtherCharges', 'totalAmount',
            'gstBreakdown', 'gstMasters', 'vehicles'
        ));
    }

    public function profitLossReport(Request $request)
    {
        if (!auth()->user()->can('view profit loss report') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $selectedYear = $request->filled('year') ? $request->year : session('current_year', date('Y'));
        if ($selectedYear === 'all') {
            $selectedYear = null;
        }

        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate = $request->filled('to_date') ? $request->to_date : null;

        $baseQuery = Bulty::whereNotIn('status', ['pending', 'planned'])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->when($selectedYear, fn($q, $y) => $q->whereYear('lr_date', $y))
            ->when($fromDate, fn($q, $d) => $q->whereDate('lr_date', '>=', $d))
            ->when($toDate, fn($q, $d) => $q->whereDate('lr_date', '<=', $d));

        $totalIncome = (clone $baseQuery)->sum('total_amount');
        $totalCommission = (clone $baseQuery)->sum('bilty_commission');
        $totalAdvance = (clone $baseQuery)->sum('advance_amount');

        $bultyIds = (clone $baseQuery)->pluck('id');

        $fuelExpense = max((float)TripFuelDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('fuel_amount'));
        $fasttagExpense = max((float)TripFastTagDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('fasttag_total_amount'));
        $adblueExpense = max((float)TripAdBlueDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('adblue_total_amount'));
        $otherTripExpense = max((float)TripOtherAmountDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('other_amount'));
        $tripAdvance = max((float)TripAdvanceDetail::whereIn('builty_id', $bultyIds)->sum('advance_amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('advance_total_amount'));

        $totalTripExpenses = $fuelExpense + $fasttagExpense + $adblueExpense + $otherTripExpense + $tripAdvance;
        $totalExpenses = $totalTripExpenses + $totalCommission;
        $netProfit = $totalIncome - $totalExpenses;

        $summary = [
            'total_income' => round($totalIncome, 0),
            'total_advance' => round($totalAdvance, 0),
            'total_trip_advance' => round($tripAdvance, 0),
            'total_commission' => round($totalCommission, 0),
            'fuel_expense' => round($fuelExpense, 0),
            'fasttag_expense' => round($fasttagExpense, 0),
            'adblue_expense' => round($adblueExpense, 0),
            'other_trip_expense' => round($otherTripExpense, 0),
            'total_trip_expenses' => round($totalTripExpenses, 0),
            'total_expenses' => round($totalExpenses, 0),
            'net_profit' => round($netProfit, 0),
        ];

        // Monthly breakdown for chart (single aggregated queries instead of loop)
        $monthlyData = [];
        $months = [];

        if ($selectedYear) {
            $chartStart = Carbon::createFromDate($selectedYear, 1, 1)->startOfMonth();
            $chartEnd = Carbon::createFromDate($selectedYear, 12, 1)->startOfMonth();
        } elseif ($fromDate && $toDate) {
            $chartStart = Carbon::parse($fromDate)->startOfMonth();
            $chartEnd = Carbon::parse($toDate)->startOfMonth();
        } elseif ($fromDate) {
            $chartStart = Carbon::parse($fromDate)->startOfMonth();
            $chartEnd = Carbon::now()->startOfMonth();
        } elseif ($toDate) {
            $chartStart = Carbon::parse($toDate)->subMonths(11)->startOfMonth();
            $chartEnd = Carbon::parse($toDate)->startOfMonth();
        } else {
            $chartStart = Carbon::now()->subMonths(11)->startOfMonth();
            $chartEnd = Carbon::now()->startOfMonth();
        }

        if ($chartStart->diffInMonths($chartEnd) > 24) {
            $chartStart = $chartEnd->copy()->subMonths(24);
        }

        $chartStartStr = $chartStart->toDateString();
        $chartEndStr = $chartEnd->copy()->endOfMonth()->toDateString();

        $bultyBaseWhere = "status NOT IN ('pending','planned')";
        $bultyParams = [];
        if ($companyId && $companyId !== 'all') {
            $bultyBaseWhere .= " AND company_id = ?";
            $bultyParams[] = $companyId;
        }

        // Single query for bulty income + commission by year/month
        $monthlyBulties = DB::select(
            "SELECT YEAR(lr_date) as y, MONTH(lr_date) as m,
                    COALESCE(SUM(total_amount),0) as income,
                    COALESCE(SUM(bilty_commission),0) as commission
             FROM bulties
             WHERE $bultyBaseWhere AND lr_date >= ? AND lr_date <= ?
             GROUP BY YEAR(lr_date), MONTH(lr_date)
             ORDER BY y, m",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        // Single query for trip-level totals by year/month
        $monthlyTrips = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m,
                    COALESCE(SUM(t.fuel_amount),0) as trip_fuel,
                    COALESCE(SUM(t.fasttag_total_amount),0) as trip_fasttag,
                    COALESCE(SUM(t.adblue_total_amount),0) as trip_adblue,
                    COALESCE(SUM(t.other_amount),0) as trip_other,
                    COALESCE(SUM(t.advance_total_amount),0) as trip_advance
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             WHERE b.$bultyBaseWhere AND b.lr_date >= ? AND b.lr_date <= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)
             ORDER BY y, m",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        // Separate queries per detail sub-table to avoid Cartesian product multiplication
        $monthlyFuel = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tfd.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_fuel_details tfd ON tfd.trip_id = t.id
             WHERE b.$bultyBaseWhere AND b.lr_date >= ? AND b.lr_date <= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        $monthlyFasttag = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tft.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_fast_tag_details tft ON tft.trip_id = t.id
             WHERE b.$bultyBaseWhere AND b.lr_date >= ? AND b.lr_date <= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        $monthlyAdblue = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tad.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_adblue_details tad ON tad.trip_id = t.id
             WHERE b.$bultyBaseWhere AND b.lr_date >= ? AND b.lr_date <= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        $monthlyOther = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(toad.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_other_amount_details toad ON toad.trip_id = t.id
             WHERE b.$bultyBaseWhere AND b.lr_date >= ? AND b.lr_date <= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        $monthlyAdv = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tad2.advance_amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_advance_details tad2 ON tad2.trip_id = t.id
             WHERE b.$bultyBaseWhere AND b.lr_date >= ? AND b.lr_date <= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$chartStartStr, $chartEndStr])
        );

        $monthlyBultyMap = [];
        foreach ($monthlyBulties as $r) { $monthlyBultyMap[$r->y . '-' . $r->m] = $r; }
        $monthlyTripMap = [];
        foreach ($monthlyTrips as $r) { $monthlyTripMap[$r->y . '-' . $r->m] = $r; }
        $monthlyFuelMap = [];
        foreach ($monthlyFuel as $r) { $monthlyFuelMap[$r->y . '-' . $r->m] = (float)$r->amt; }
        $monthlyFasttagMap = [];
        foreach ($monthlyFasttag as $r) { $monthlyFasttagMap[$r->y . '-' . $r->m] = (float)$r->amt; }
        $monthlyAdblueMap = [];
        foreach ($monthlyAdblue as $r) { $monthlyAdblueMap[$r->y . '-' . $r->m] = (float)$r->amt; }
        $monthlyOtherMap = [];
        foreach ($monthlyOther as $r) { $monthlyOtherMap[$r->y . '-' . $r->m] = (float)$r->amt; }
        $monthlyAdvMap = [];
        foreach ($monthlyAdv as $r) { $monthlyAdvMap[$r->y . '-' . $r->m] = (float)$r->amt; }

        $currentMonth = $chartStart->copy();
        while ($currentMonth <= $chartEnd) {
            $key = $currentMonth->year . '-' . $currentMonth->month;
            $months[] = $currentMonth->format('M Y');

            $mb = $monthlyBultyMap[$key] ?? null;
            $mt = $monthlyTripMap[$key] ?? null;

            $mIncome = (float)($mb->income ?? 0);
            $mComm = (float)($mb->commission ?? 0);

            $mFuel = max($monthlyFuelMap[$key] ?? 0, (float)($mt->trip_fuel ?? 0));
            $mFasttag = max($monthlyFasttagMap[$key] ?? 0, (float)($mt->trip_fasttag ?? 0));
            $mAdblue = max($monthlyAdblueMap[$key] ?? 0, (float)($mt->trip_adblue ?? 0));
            $mOther = max($monthlyOtherMap[$key] ?? 0, (float)($mt->trip_other ?? 0));
            $mTripAdv = max($monthlyAdvMap[$key] ?? 0, (float)($mt->trip_advance ?? 0));

            $mTripExp = $mFuel + $mFasttag + $mAdblue + $mOther + $mTripAdv;

            $monthlyData[] = [
                'income' => round($mIncome, 0),
                'expense' => round($mTripExp + $mComm, 0),
            ];

            $currentMonth->addMonth();
        }

        return view('admin.reports.profit_loss', compact(
            'summary', 'fromDate', 'toDate', 'companyId', 'selectedYear',
            'months', 'monthlyData'
        ));
    }

    private function getFilteredVehicleQuery(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $vehicleGroupCols = [
            'vehicles.id',
            'vehicles.vehicle_number', 'vehicles.vehicle_type', 'vehicles.make_model',
            'vehicles.capacity_tons', 'vehicles.owner_name', 'vehicles.owner_phone',
            'vehicles.insurance_expiry', 'vehicles.fitness_expiry', 'vehicles.permit_expiry',
            'vehicles.pollution_expiry', 'vehicles.status',
            'vehicles.registration_cert', 'vehicles.insurance_doc',
            'vehicles.fitness_doc', 'vehicles.permit_doc', 'vehicles.pollution_cert',
            'vehicles.created_at', 'vehicles.updated_at', 'vehicles.deleted_at',
        ];

        $fuelSubquery = DB::table('trip_fuel_details')
            ->select('trip_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(amount) as total_amt')
            )
            ->groupBy('trip_id');

        $kmSubquery = DB::table('trip_fuel_details')
            ->join('trips', 'trips.id', '=', 'trip_fuel_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(MAX(trip_fuel_details.km) - MIN(trip_fuel_details.km), 0) as total_km'))
            ->whereNull('bulties.deleted_at')
            ->whereNotIn('bulties.status', ['pending', 'planned']);

        if ($companyId && $companyId !== 'all') {
            $kmSubquery->where('bulties.company_id', $companyId);
        }
        if ($request->filled('vehicle_id')) {
            $kmSubquery->where('bulties.vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('date_from')) {
            $kmSubquery->whereDate('bulties.lr_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $kmSubquery->whereDate('bulties.lr_date', '<=', $request->date_to);
        }
        $kmSubquery->groupBy('bulties.vehicle_id');

        return Vehicle::query()
            ->select('vehicles.*',
                DB::raw('COUNT(bulties.id) as total_trips'),
                DB::raw('COALESCE(SUM(fuel_summary.total_qty), 0) as total_fuel_qty'),
                DB::raw('COALESCE(SUM(fuel_summary.total_amt), 0) as total_fuel_amount'),
                DB::raw('COALESCE(MAX(km_summary.total_km), 0) as total_km'),
                DB::raw('COALESCE(SUM(trips.advance_total_amount), 0) as total_advance')
            )
            ->leftJoin('bulties', function ($join) {
                $join->on('bulties.vehicle_id', '=', 'vehicles.id')
                    ->whereNull('bulties.deleted_at')
                    ->whereNotIn('bulties.status', ['pending', 'planned']);
            })
            ->leftJoin('trips', 'trips.builty_id', '=', 'bulties.id')
            ->leftJoinSub($fuelSubquery, 'fuel_summary', function ($join) {
                $join->on('fuel_summary.trip_id', '=', 'trips.id');
            })
            ->leftJoinSub($kmSubquery, 'km_summary', function ($join) {
                $join->on('km_summary.vehicle_id', '=', 'vehicles.id');
            })
            ->groupBy($vehicleGroupCols);
    }

    public function exportVehicle(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = $this->getFilteredVehicleQuery($request);

        if ($companyId && $companyId !== 'all') {
            $query->where('bulties.company_id', $companyId);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicles.id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('bulties.lr_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('bulties.lr_date', '<=', $request->date_to);
        }

        $vehicles = $query->orderBy('vehicles.vehicle_number')->get();

        if ($format === 'pdf') {
            $title = 'Vehicle Performance Report';
            $pdf = Pdf::loadView('admin.reports.pdf.vehicle', compact('vehicles', 'title'));
            return $pdf->download('vehicle_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['#', 'Vehicle', 'Type', 'Trips', 'Total Fuel (L)', 'Total Amount (Rs)', 'Total KM', 'Avg KM/L'];
        $data = $vehicles->values()->map(fn($v, $i) => [
            $i + 1, $v->vehicle_number, $v->vehicle_type ?? '-',
            $v->total_trips, number_format($v->total_fuel_qty, 2), number_format($v->total_fuel_amount, 2),
            number_format($v->total_km, 2),
            $v->total_fuel_qty > 0 ? round($v->total_km / $v->total_fuel_qty, 2) : 0,
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Vehicle Performance Report'), 'vehicle_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportTrip(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;

        $query = Bulty::with(['vehicle','trip.fuelDetails','trip.fastTagDetails','trip.adblueDetails','trip.otherAmountDetails'])
            ->whereNotIn('status', ['pending', 'planned']);
        if ($companyId && $companyId !== 'all') $query->where('company_id', $companyId);
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('date_from')) $query->whereDate('lr_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('lr_date', '<=', $request->date_to);
        if ($request->filled('trip_status')) $query->whereHas('trip', fn($q) => $q->where('status', $request->trip_status));

        $trips = $query->orderBy('lr_date', 'desc')->get();

        if ($format === 'pdf') {
            $title = 'Trip Report';
            $pdf = Pdf::loadView('admin.reports.pdf.trip', compact('trips', 'title'))->setPaper('a4', 'landscape');
            return $pdf->download('trip_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['LR No', 'Date', 'Vehicle', 'Consignor', 'Consignee', 'Route', 'Freight', 'GST', 'Other', 'Total', 'Bilty Advance', 'Trip Advance', 'Trip Status', 'Fuel (L)', 'Fuel Amt', 'FastTag', 'AdBlue', 'Other'];
        $data = $trips->map(fn($b) => [
            $b->lr_no, $b->lr_date?->format('d-m-Y') ?? '-', $b->vehicle?->vehicle_number ?? '-',
            $b->consignor?->name ?? '-', $b->consignee?->name ?? '-',
            ($b->originCity?->name ?? $b->from_city) . ' → ' . ($b->destinationCity?->name ?? $b->to_city),
            number_format($b->freight_charges, 0), number_format($b->gst_amount, 0),
            number_format($b->other_charges, 0), number_format($b->total_amount, 0),
            number_format($b->advance_amount, 0), number_format($b->trip?->advance_total_amount ?? 0, 0),
            $b->trip ? ucfirst($b->trip->status) : '-',
            number_format($b->trip?->fuelDetails->sum('quantity') ?? 0, 2),
            number_format($b->trip?->fuelDetails->sum('amount') ?? 0, 2),
            $b->trip ? number_format($b->trip->fasttag_total_amount, 2) : '-',
            $b->trip ? number_format($b->trip->adblue_total_amount, 2) : '-',
            $b->trip ? number_format($b->trip->other_amount, 2) : '-',
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Trip Report'), 'trip_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportDriverTrip(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;

        $query = Bulty::with(['driver','vehicle','trip.fuelDetails','trip.fastTagDetails','trip.adblueDetails','trip.otherAmountDetails','trip.advanceDetails'])
            ->whereNotNull('driver_id')->whereNotIn('status', ['pending', 'planned']);
        if ($companyId && $companyId !== 'all') $query->where('company_id', $companyId);
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        if ($request->filled('date_from')) $query->whereDate('lr_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('lr_date', '<=', $request->date_to);

        $trips = $request->filled('driver_id') ? $query->orderBy('driver_id')->get() : collect([]);

        if ($format === 'pdf') {
            $title = 'Driver-wise Trip Report';
            $pdf = Pdf::loadView('admin.reports.pdf.driver_trip', compact('trips', 'title'));
            return $pdf->download('driver_trip_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['Driver', 'LR No', 'Vehicle', 'Fuel (L)', 'Fuel Amt', 'FastTag', 'AdBlue', 'Other', 'Advance'];
        $data = $trips->map(fn($b) => [
            $b->driver?->name ?? 'N/A', $b->lr_no, $b->vehicle?->vehicle_number ?? '-',
            number_format($b->trip?->fuelDetails->sum('quantity') ?? 0, 2),
            number_format($b->trip?->fuelDetails->sum('amount') ?? 0, 2),
            $b->trip ? number_format($b->trip->fasttag_total_amount, 2) : '-',
            $b->trip ? number_format($b->trip->adblue_total_amount, 2) : '-',
            $b->trip ? number_format($b->trip->other_amount, 2) : '-',
            $b->trip ? number_format($b->trip->advance_total_amount, 2) : '-',
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Driver Trip Report'), 'driver_trip_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportCustomerLedger(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;

        $selectedConsignee = null;
        $transactions = collect([]);
        $summary = null;

        if ($request->filled('consignee_id')) {
            $selectedConsignee = Consignee::find($request->consignee_id);
            $query = Bulty::with(['originCity', 'destinationCity', 'vehicle'])
                ->where('consignee_id', $request->consignee_id)->whereNotIn('status', ['pending', 'planned']);
            if ($companyId && $companyId !== 'all') $query->where('company_id', $companyId);
            $transactions = $query->orderBy('lr_date', 'desc')->get();

            $sQuery = Bulty::where('consignee_id', $request->consignee_id)->whereNotIn('status', ['pending', 'planned']);
            if ($companyId && $companyId !== 'all') $sQuery->where('company_id', $companyId);
            $summary = $sQuery->select(DB::raw('COUNT(*) as total_lr'),
                DB::raw('COALESCE(SUM(freight_charges),0) as total_freight'),
                DB::raw('COALESCE(SUM(gst_amount),0) as total_gst'),
                DB::raw('COALESCE(SUM(other_charges),0) as total_other'),
                DB::raw('COALESCE(SUM(total_amount),0) as total_amount'),
                DB::raw('COALESCE(SUM(advance_amount),0) as total_advance'),
                DB::raw('COALESCE(SUM(remaining_amount),0) as total_remaining'))->first();
        }

        if ($format === 'pdf') {
            if (!$selectedConsignee) {
                return redirect()->back()->with('error', 'Please select a customer to export.');
            }
            $title = 'Customer Ledger Report';
            $pdf = Pdf::loadView('admin.reports.pdf.customer_ledger', compact('selectedConsignee', 'transactions', 'summary', 'title'));
            return $pdf->download('customer_ledger_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['LR No', 'Date', 'From → To', 'Vehicle', 'Freight', 'GST', 'Other', 'Total', 'Paid', 'Due'];
        $data = $transactions->map(fn($b) => [
            $b->lr_no, $b->lr_date?->format('d-m-Y') ?? '-',
            ($b->originCity?->name ?? $b->from_city) . ' → ' . ($b->destinationCity?->name ?? $b->to_city),
            $b->vehicle?->vehicle_number ?? '-',
            number_format($b->freight_charges, 2), number_format($b->gst_amount, 2),
            number_format($b->other_charges, 2), number_format($b->total_amount, 2),
            number_format($b->advance_amount, 2),
            number_format($b->remaining_amount ?? ($b->total_amount - $b->advance_amount), 2),
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Customer Ledger Report'), 'customer_ledger_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportTripReports(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;

        $query = Bulty::with(['vehicle','driver','consignor','consignee','originCity','destinationCity',
            'trip.fuelDetails','trip.fastTagDetails','trip.adblueDetails','trip.otherAmountDetails'])
            ->whereNotIn('status', ['pending', 'planned'])->select('bulties.*');
        if ($companyId && $companyId !== 'all') $query->where('company_id', $companyId);
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('date_from')) $query->whereDate('lr_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('lr_date', '<=', $request->date_to);
        if ($request->filled('trip_status')) $query->whereHas('trip', fn($q) => $q->where('status', $request->trip_status));

        $trips = $query->orderBy('lr_date', 'desc')->get();

        if ($format === 'pdf') {
            $title = 'Detailed Trip Report';
            $pdf = Pdf::loadView('admin.reports.pdf.trip_reports', compact('trips', 'title'))->setPaper('a4', 'landscape');
            return $pdf->download('trip_reports_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['LR No', 'Date', 'Vehicle', 'Driver', 'Route', 'Freight', 'GST', 'Other', 'Total', 'Bilty Advance', 'Trip Advance', 'Trip Status', 'Fuel Exp', 'FastTag', 'AdBlue', 'Other Exp', 'Net Profit'];
        $data = $trips->map(function($b) {
            $trip = $b->trip;
            $totalFuelAmt = $trip?->fuelDetails->sum('amount') ?? 0;
            $totalExpenses = $totalFuelAmt + ($trip?->fasttag_total_amount ?? 0) + ($trip?->adblue_total_amount ?? 0) + ($trip?->other_amount ?? 0) + ($trip?->advance_total_amount ?? 0);
            $netProfit = $b->total_amount - $totalExpenses;
            return [
                $b->lr_no, $b->lr_date?->format('d-m-Y') ?? '-', $b->vehicle?->vehicle_number ?? '-',
                $b->driver?->name ?? '-',
                ($b->originCity?->name ?? $b->from_city) . ' → ' . ($b->destinationCity?->name ?? $b->to_city),
                number_format($b->freight_charges, 0), number_format($b->gst_amount, 0),
                number_format($b->other_charges, 0), number_format($b->total_amount, 0),
                number_format($b->advance_amount, 0), number_format($trip?->advance_total_amount ?? 0, 0), $trip ? ucfirst($trip->status) : '-',
                number_format($totalFuelAmt, 0), number_format($trip?->fasttag_total_amount ?? 0, 0),
                number_format($trip?->adblue_total_amount ?? 0, 0), number_format($trip?->other_amount ?? 0, 0),
                number_format($netProfit, 0),
            ];
        })->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Detailed Trip Report'), 'trip_reports_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportFuel(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;

        $query = TripFuelDetail::with(['fuelPump','fuelCompany','trip.builty.vehicle']);
        if ($companyId && $companyId !== 'all') $query->whereHas('trip.builty', fn($q) => $q->where('company_id', $companyId));
        if ($request->filled('vehicle_id')) $query->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        if ($request->filled('fuel_company_id')) $query->where('fuel_company_id', $request->fuel_company_id);
        if ($request->filled('fuel_pump_id')) $query->where('fuel_pump_id', $request->fuel_pump_id);
        if ($request->filled('payment_type')) $query->where('payment_type', $request->payment_type);
        if ($request->filled('date_from')) $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('date', '<=', $request->date_to);

        $fuelDetails = $query->orderBy('date', 'desc')->get();
        $summary = (clone $query)->select(DB::raw('COALESCE(SUM(quantity),0) as total_qty'), DB::raw('COALESCE(SUM(amount),0) as total_amount'), DB::raw('COALESCE(SUM(km),0) as total_km'))->first();

        if ($format === 'pdf') {
            $title = 'Fuel Report';
            $pdf = Pdf::loadView('admin.reports.pdf.fuel', compact('fuelDetails', 'summary', 'title'));
            return $pdf->download('fuel_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['Date', 'Vehicle', 'Pump', 'Company', 'Payment Type', 'Qty (L)', 'Rate', 'Amount', 'KM', 'LR No'];
        $data = $fuelDetails->map(fn($fd) => [
            $fd->date?->format('d-m-Y') ?? '-', $fd->trip?->builty?->vehicle?->vehicle_number ?? '-',
            $fd->fuelPump?->name ?? '-', $fd->fuelCompany?->name ?? '-',
            ucfirst($fd->payment_type ?? '-'),
            number_format($fd->quantity, 2), number_format($fd->rate, 2),
            number_format($fd->amount, 2), number_format($fd->km, 2),
            $fd->trip?->builty?->lr_no ?? '-',
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Fuel Report'), 'fuel_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportAdBlue(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;

        $query = TripAdBlueDetail::with(['adblueCompany','trip.builty.vehicle']);
        if ($companyId && $companyId !== 'all') $query->whereHas('trip.builty', fn($q) => $q->where('company_id', $companyId));
        if ($request->filled('vehicle_id')) $query->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $request->vehicle_id));
        if ($request->filled('adblue_company_id')) $query->where('adblue_company_id', $request->adblue_company_id);
        if ($request->filled('payment_type')) $query->where('payment_type', $request->payment_type);
        if ($request->filled('date_from')) $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('date', '<=', $request->date_to);

        $adblueDetails = $query->orderBy('date', 'desc')->get();
        $summary = (clone $query)->select(DB::raw('COALESCE(SUM(quantity),0) as total_qty'), DB::raw('COALESCE(SUM(amount),0) as total_amount'), DB::raw('COALESCE(SUM(km),0) as total_km'))->first();

        if ($format === 'pdf') {
            $title = 'AdBlue Report';
            $pdf = Pdf::loadView('admin.reports.pdf.adblue', compact('adblueDetails', 'summary', 'title'));
            return $pdf->download('adblue_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['Date', 'Vehicle', 'Company', 'Payment Type', 'Qty (L)', 'Rate', 'Amount', 'KM', 'LR No'];
        $data = $adblueDetails->map(fn($ad) => [
            $ad->date?->format('d-m-Y') ?? '-', $ad->trip?->builty?->vehicle?->vehicle_number ?? '-',
            $ad->adblueCompany?->name ?? '-',
            ucfirst($ad->payment_type ?? '-'),
            number_format($ad->quantity, 2), number_format($ad->rate, 2),
            number_format($ad->amount, 2), number_format($ad->km, 2),
            $ad->trip?->builty?->lr_no ?? '-',
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'AdBlue Report'), 'adblue_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportVehicleUtilization(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $vehicleGroupCols = [
            'vehicles.id',
            'vehicles.vehicle_number', 'vehicles.vehicle_type', 'vehicles.make_model',
            'vehicles.capacity_tons', 'vehicles.owner_name', 'vehicles.owner_phone',
            'vehicles.insurance_expiry', 'vehicles.fitness_expiry', 'vehicles.permit_expiry',
            'vehicles.pollution_expiry', 'vehicles.status',
            'vehicles.registration_cert', 'vehicles.insurance_doc',
            'vehicles.fitness_doc', 'vehicles.permit_doc', 'vehicles.pollution_cert',
            'vehicles.created_at', 'vehicles.updated_at', 'vehicles.deleted_at',
        ];

        $fuelSubquery = DB::table('trip_fuel_details')
            ->select('trip_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(amount) as total_amt')
            )
            ->groupBy('trip_id');

        $advanceSubquery = DB::table('trip_advance_details')
            ->select('trip_id', DB::raw('SUM(advance_amount) as total_advance'))
            ->groupBy('trip_id');

        $kmSubquery = DB::table('trip_fuel_details')
            ->join('trips', 'trips.id', '=', 'trip_fuel_details.trip_id')
            ->join('bulties', 'bulties.id', '=', 'trips.builty_id')
            ->select('bulties.vehicle_id', DB::raw('COALESCE(MAX(trip_fuel_details.km) - MIN(trip_fuel_details.km), 0) as total_km'))
            ->whereNull('bulties.deleted_at')
            ->whereNotIn('bulties.status', ['pending', 'planned']);

        if ($companyId && $companyId !== 'all') {
            $kmSubquery->where('bulties.company_id', $companyId);
        }
        if ($request->filled('vehicle_id')) {
            $kmSubquery->where('bulties.vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('date_from')) {
            $kmSubquery->whereDate('bulties.lr_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $kmSubquery->whereDate('bulties.lr_date', '<=', $request->date_to);
        }
        $kmSubquery->groupBy('bulties.vehicle_id');

        $query = Vehicle::query()
            ->select('vehicles.*',
                DB::raw('COUNT(bulties.id) as total_trips'),
                DB::raw('COALESCE(SUM(fuel_summary.total_qty), 0) as total_fuel_qty'),
                DB::raw('COALESCE(SUM(fuel_summary.total_amt), 0) as total_fuel_amount'),
                DB::raw('COALESCE(MAX(km_summary.total_km), 0) as total_km'),
                DB::raw('COALESCE(SUM(bulties.total_amount), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(trips.fasttag_total_amount), 0) as total_fasttag'),
                DB::raw('COALESCE(SUM(trips.adblue_total_amount), 0) as total_adblue'),
                DB::raw('COALESCE(SUM(trips.other_amount), 0) as total_other_expense'),
                DB::raw('COALESCE(SUM(advance_summary.total_advance), 0) as total_advance')
            )
            ->leftJoin('bulties', function ($join) {
                $join->on('bulties.vehicle_id', '=', 'vehicles.id')
                    ->whereNull('bulties.deleted_at')
                    ->whereNotIn('bulties.status', ['pending', 'planned']);
            })
            ->leftJoin('trips', 'trips.builty_id', '=', 'bulties.id')
            ->leftJoinSub($fuelSubquery, 'fuel_summary', function ($join) {
                $join->on('fuel_summary.trip_id', '=', 'trips.id');
            })
            ->leftJoinSub($advanceSubquery, 'advance_summary', function ($join) {
                $join->on('advance_summary.trip_id', '=', 'trips.id');
            })
            ->leftJoinSub($kmSubquery, 'km_summary', function ($join) {
                $join->on('km_summary.vehicle_id', '=', 'vehicles.id');
            })
            ->groupBy($vehicleGroupCols);

        if ($companyId && $companyId !== 'all') {
            $query->where('bulties.company_id', $companyId);
        }

        if ($request->filled('vehicle_id')) $query->where('vehicles.id', $request->vehicle_id);
        if ($request->filled('date_from')) $query->whereDate('bulties.lr_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('bulties.lr_date', '<=', $request->date_to);

        $vehicles = $query->orderBy('vehicles.vehicle_number')->get();

        if ($format === 'pdf') {
            $title = 'Vehicle Utilization Report';
            $pdf = Pdf::loadView('admin.reports.pdf.vehicle_utilization', compact('vehicles', 'title'));
            return $pdf->download('vehicle_utilization_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['Vehicle', 'Type', 'Trips', 'Total KM', 'Total Fuel (L)', 'Avg KM/L', 'Revenue', 'Advance', 'Fuel Cost', 'FastTag', 'AdBlue', 'Other Exp', 'Total Exp', 'Net Revenue'];
        $data = $vehicles->map(function($v) {
            $avgKmL = $v->total_fuel_qty > 0 ? round($v->total_km / $v->total_fuel_qty, 2) : 0;
            $totalExp = $v->total_fuel_amount + $v->total_fasttag + $v->total_adblue + $v->total_other_expense + $v->total_advance;
            $netRevenue = $v->total_revenue - $totalExp;
            return [
                $v->vehicle_number, $v->vehicle_type ?? '-', $v->total_trips, number_format($v->total_km, 2),
                number_format($v->total_fuel_qty, 2), $avgKmL > 0 ? $avgKmL : 0,
                number_format($v->total_revenue, 0), number_format($v->total_advance, 0),
                number_format($v->total_fuel_amount, 0),
                number_format($v->total_fasttag, 0), number_format($v->total_adblue, 0),
                number_format($v->total_other_expense, 0), number_format($totalExp, 0), number_format($netRevenue, 0),
            ];
        })->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Vehicle Utilization Report'), 'vehicle_utilization_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportMis(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;
        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate = $request->filled('to_date') ? $request->to_date : null;

        $baseQuery = Bulty::query()
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->when($fromDate, fn($q, $d) => $q->whereDate('lr_date', '>=', $d))
            ->when($toDate, fn($q, $d) => $q->whereDate('lr_date', '<=', $d));

        $totalLR = (clone $baseQuery)->count();
        $totalRevenue = (clone $baseQuery)->whereNotIn('status', ['pending', 'planned'])->sum('total_amount');
        $totalAdvance = (clone $baseQuery)->whereNotIn('status', ['pending', 'planned'])->sum('advance_amount');
        $totalDue = (clone $baseQuery)->whereNotIn('status', ['pending', 'planned'])->sum('remaining_amount');
        $totalVehicles = Vehicle::where('status', 'active')->count();
        $totalDrivers = Driver::where('status', 'active')->count();
        $activeTrips = Trip::when($companyId && $companyId !== 'all', fn($q) => $q->whereHas('builty', fn($q) => $q->where('company_id', $companyId)))->whereIn('status', ['pending', 'complete'])->count();
        $thisMonth = now()->startOfMonth();
        $monthLR = (clone $baseQuery)->where('created_at', '>=', $thisMonth)->count();
        $monthRevenue = (clone $baseQuery)->whereNotIn('status', ['pending', 'planned'])->where('created_at', '>=', $thisMonth)->sum('total_amount');

        $bultyIds = (clone $baseQuery)->whereNotIn('status', ['pending', 'planned'])->pluck('id');

        $totalFuelQty = TripFuelDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('quantity');
        $totalFuelAmt = TripFuelDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');
        $totalFastTag = TripFastTagDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('transaction_time', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('transaction_time', '<=', $toDate))
            ->sum('amount');
        $totalAdBlue = TripAdBlueDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');
        $totalOtherExp = TripOtherAmountDetail::whereIn('builty_id', $bultyIds)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->sum('amount');
        $totalTripAdvance = Trip::whereIn('builty_id', $bultyIds)->sum('advance_total_amount');

        $topVehicles = Bulty::select('vehicle_id', DB::raw('COUNT(*) as trip_count'), DB::raw('COALESCE(SUM(total_amount),0) as revenue'), DB::raw('COALESCE(SUM(freight_charges),0) as freight'))
            ->whereNotNull('vehicle_id')->whereNotIn('status', ['pending', 'planned'])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->groupBy('vehicle_id')->orderByDesc('trip_count')->limit(10)->with('vehicle')->get();

        $recentTrips = Bulty::with(['vehicle','driver','originCity','destinationCity','trip'])
            ->whereNotIn('status', ['pending', 'planned'])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->orderBy('created_at', 'desc')->limit(10)->get();

        if ($format === 'pdf') {
            $title = 'MIS Report';
            $pdf = Pdf::loadView('admin.reports.pdf.mis', compact(
                'totalLR','totalRevenue','totalAdvance','totalDue','totalVehicles','totalDrivers','activeTrips',
                'monthLR','monthRevenue','totalFuelQty','totalFuelAmt','totalFastTag','totalAdBlue','totalOtherExp',
                'totalTripAdvance','topVehicles','recentTrips','title'
            ));
            return $pdf->download('mis_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['Metric', 'Value'];
        $data = [
            ['TOTAL LR', $totalLR], ['Total Revenue', number_format($totalRevenue, 0)],
            ['Total Advance', number_format($totalAdvance, 0)], ['Total Due', number_format($totalDue, 0)],
            ['Total Vehicles', $totalVehicles], ['Total Drivers', $totalDrivers],
            ['Active Trips', $activeTrips], ['This Month LR', $monthLR],
            ['Month Revenue', number_format($monthRevenue, 0)],
            ['Total Fuel', number_format($totalFuelQty, 2) . ' L / ₹ ' . number_format($totalFuelAmt, 0)],
            ['FastTag', '₹ ' . number_format($totalFastTag, 0)],
            ['AdBlue', '₹ ' . number_format($totalAdBlue, 0)],
            ['Other Expenses', '₹ ' . number_format($totalOtherExp, 0)],
            [],
            ['TOP VEHICLES', 'Trips', 'Freight', 'Revenue'],
        ];
        foreach ($topVehicles as $i => $tv) {
            $data[] = [
                $tv->vehicle?->vehicle_number ?? 'Unknown',
                $tv->trip_count,
                number_format($tv->freight, 0),
                number_format($tv->revenue, 0),
            ];
        }

        return Excel::download(new ReportExport($headings, $data, 'MIS Report'), 'mis_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportExpense(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? $request->to_date : now()->toDateString();
        $companyFilter = fn($q) => $q->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId));
        $tripCf = fn($q) => $q->when($companyId && $companyId !== 'all', fn($q) => $q->whereHas('trip.builty', fn($q) => $q->where('company_id', $companyId)));

        $tripFuelQuery = TripFuelDetail::whereBetween('date', [$fromDate, $toDate]); $tripCf($tripFuelQuery);
        $tripFastTagQuery = TripFastTagDetail::where(function($q) use ($fromDate, $toDate) {
            $q->whereBetween('transaction_time', [$fromDate, $toDate . ' 23:59:59'])
              ->orWhere(function($q2) use ($fromDate, $toDate) {
                  $q2->whereNull('transaction_time')
                     ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
              });
        }); $tripCf($tripFastTagQuery);
        $tripAdBlueQuery = TripAdBlueDetail::whereBetween('date', [$fromDate, $toDate]); $tripCf($tripAdBlueQuery);
        $tripOtherQuery = TripOtherAmountDetail::whereBetween('date', [$fromDate, $toDate]); $tripCf($tripOtherQuery);
        $tripAdvanceQuery = TripAdvanceDetail::whereBetween('date', [$fromDate, $toDate]); $tripCf($tripAdvanceQuery);

        $vehicleId = $request->vehicle_id;
        if ($request->filled('vehicle_id')) {
            $tripFuelQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripFastTagQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripAdBlueQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripOtherQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
            $tripAdvanceQuery->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $vehicleId));
        }

        $totalFuelAmt = (clone $tripFuelQuery)->sum('amount');
        $totalFuelQty = (clone $tripFuelQuery)->sum('quantity');
        $totalFastTag = (clone $tripFastTagQuery)->sum('amount');
        $totalAdBlue = (clone $tripAdBlueQuery)->sum('amount');
        $totalOtherExp = (clone $tripOtherQuery)->sum('amount');
        $totalTripAdvance = (clone $tripAdvanceQuery)->sum('advance_amount');

        $maintenanceQuery = MaintenanceHistory::whereBetween('service_date', [$fromDate, $toDate]); $companyFilter($maintenanceQuery);
        $breakdownQuery = Breakdown::whereBetween('breakdown_date', [$fromDate, $toDate]); $companyFilter($breakdownQuery);
        $sparePartQuery = SparePart::whereBetween('part_change_date', [$fromDate, $toDate]); $companyFilter($sparePartQuery);

        if ($request->filled('vehicle_id')) {
            $maintenanceQuery->where('vehicle_id', $vehicleId);
            $breakdownQuery->where('vehicle_id', $vehicleId);
            $sparePartQuery->where('vehicle_id', $vehicleId);
        }

        $totalMaintenance = (clone $maintenanceQuery)->sum('cost');
        $totalBreakdown = (clone $breakdownQuery)->sum('repair_cost');
        $totalSparePart = (clone $sparePartQuery)->sum('amount');
        $totalTripExpenses = $totalFuelAmt + $totalFastTag + $totalAdBlue + $totalOtherExp;
        $totalMaintenanceExpenses = $totalMaintenance + $totalBreakdown + $totalSparePart;
        $grandTotal = $totalTripExpenses + $totalMaintenanceExpenses;

        $vehicles = Vehicle::where('status', 'active')
            ->when($request->filled('vehicle_id'), fn($q) => $q->where('id', $vehicleId))
            ->get()->map(function($v) use ($fromDate, $toDate) {
                $v->fuel_expense = TripFuelDetail::whereBetween('date', [$fromDate, $toDate])->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $v->id))->sum('amount');
                $v->fasttag_expense = TripFastTagDetail::where(function($q) use ($fromDate, $toDate) {
                        $q->whereBetween('transaction_time', [$fromDate, $toDate . ' 23:59:59'])
                          ->orWhere(function($q2) use ($fromDate, $toDate) {
                              $q2->whereNull('transaction_time')
                                 ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59']);
                          });
                    })->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $v->id))->sum('amount');
                $v->adblue_expense = TripAdBlueDetail::whereBetween('date', [$fromDate, $toDate])->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $v->id))->sum('amount');
                $v->other_expense = TripOtherAmountDetail::whereBetween('date', [$fromDate, $toDate])->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $v->id))->sum('amount');
                $v->advance_expense = TripAdvanceDetail::whereBetween('date', [$fromDate, $toDate])->whereHas('trip.builty', fn($q) => $q->where('vehicle_id', $v->id))->sum('advance_amount');
                $v->maintenance_cost = MaintenanceHistory::where('vehicle_id', $v->id)->whereBetween('service_date', [$fromDate, $toDate])->sum('cost');
                $v->breakdown_cost = Breakdown::where('vehicle_id', $v->id)->whereBetween('breakdown_date', [$fromDate, $toDate])->sum('repair_cost');
                $v->spare_part_cost = SparePart::where('vehicle_id', $v->id)->whereBetween('part_change_date', [$fromDate, $toDate])->sum('amount');
                $v->total_expense = $v->fuel_expense + $v->fasttag_expense + $v->adblue_expense + $v->other_expense + $v->advance_expense + $v->maintenance_cost + $v->breakdown_cost + $v->spare_part_cost;
                return $v;
            })->filter(fn($v) => $v->total_expense > 0)->sortByDesc('total_expense')->values();

        $totalTripExpenses = $totalFuelAmt + $totalFastTag + $totalAdBlue + $totalOtherExp + $totalTripAdvance;
        $totalMaintenanceExpenses = $totalMaintenance + $totalBreakdown + $totalSparePart;
        $grandTotal = $totalTripExpenses + $totalMaintenanceExpenses;

        if ($format === 'pdf') {
            $title = 'Expense Management Report';
            $pdf = Pdf::loadView('admin.reports.pdf.expense_management', compact(
                'totalFuelAmt','totalFuelQty','totalFastTag','totalAdBlue','totalOtherExp','totalTripAdvance',
                'totalMaintenance','totalBreakdown','totalSparePart',
                'totalTripExpenses','totalMaintenanceExpenses','grandTotal','vehicles','title'
            ));
            return $pdf->download('expense_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['#', 'Vehicle', 'Fuel', 'FastTag', 'AdBlue', 'Other Trip', 'Advance', 'Maintenance', 'Breakdown', 'Spare Parts', 'Total'];
        $data = $vehicles->map(fn($v, $i) => [
            $i + 1, $v->vehicle_number,
            '₹ ' . number_format($v->fuel_expense, 0), '₹ ' . number_format($v->fasttag_expense, 0),
            '₹ ' . number_format($v->adblue_expense, 0), '₹ ' . number_format($v->other_expense, 0),
            '₹ ' . number_format($v->advance_expense, 0),
            '₹ ' . number_format($v->maintenance_cost, 0), '₹ ' . number_format($v->breakdown_cost, 0),
            '₹ ' . number_format($v->spare_part_cost, 0), '₹ ' . number_format($v->total_expense, 0),
        ])->toArray();
        return Excel::download(new ReportExport($headings, $data, 'Expense Management Report'), 'expense_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportVehicleDocuments(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyName = 'N/A';
        if ($user->isSuperAdmin()) {
            $companyId = $request->filled('company_id') ? $request->company_id : session('current_company_id');
            if ($companyId && $companyId !== 'all') {
                $comp = Company::find($companyId);
                $companyName = $comp ? $comp->name : 'N/A';
            } else {
                $companyName = 'All Companies';
            }
        } else {
            $companyName = $user->company ? $user->company->name : 'N/A';
        }

        $thresholdDays = $request->filled('threshold_days') ? (int)$request->threshold_days : 30;
        $documentFields = ['insurance_expiry' => 'Insurance','fitness_expiry' => 'Fitness Certificate','permit_expiry' => 'Permit','pollution_expiry' => 'Pollution Certificate'];
        $selectedDoc = $request->input('document_type');
        $query = Vehicle::where('status', 'active');
        if ($request->filled('vehicle_id')) $query->where('id', $request->vehicle_id);
        $documents = collect();
        $threshold = now()->addDays($thresholdDays);
        $query->chunk(100, function ($vehicles) use (&$documents, $documentFields, $selectedDoc, $threshold, $companyName) {
            foreach ($vehicles as $vehicle) {
                foreach ($documentFields as $field => $label) {
                    if ($selectedDoc && $selectedDoc !== $field) continue;
                    $expiryDate = $vehicle->$field;
                    if ($expiryDate && $expiryDate <= $threshold) {
                        $documents->push(['vehicle_number' => $vehicle->vehicle_number, 'vehicle_id' => $vehicle->id,
                            'company_name' => $companyName, 'document' => $label,
                            'document_field' => $field, 'expiry_date' => $expiryDate, 'days_left' => now()->diffInDays($expiryDate, false)]);
                    }
                }
            }
        });
        $documents = $documents->sortBy('days_left')->values();
        $totalExpired = $documents->filter(fn($d) => $d['days_left'] <= 0)->count();
        $totalWarning = $documents->filter(fn($d) => $d['days_left'] > 0 && $d['days_left'] <= 7)->count();
        $totalUpcoming = $documents->filter(fn($d) => $d['days_left'] > 7)->count();

        if ($format === 'pdf') {
            $title = 'Vehicle Document Expiry Report';
            $pdf = Pdf::loadView('admin.reports.pdf.vehicle_documents', compact(
                'documents', 'documentFields', 'thresholdDays', 'totalExpired', 'totalWarning', 'totalUpcoming', 'title'
            ));
            return $pdf->download('vehicle_documents_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['#', 'Vehicle', 'Company', 'Document', 'Expiry Date', 'Days Left'];
        $data = [];
        foreach ($documents as $i => $doc) {
            $data[] = [
                $i + 1, $doc['vehicle_number'], $doc['company_name'], $doc['document'],
                \Carbon\Carbon::parse($doc['expiry_date'])->format('d-m-Y'),
                $doc['days_left'] <= 0 ? 'Expired' : $doc['days_left'] . ' days',
            ];
        }
        return Excel::download(new ReportExport($headings, $data, 'Vehicle Document Expiry Report'), 'vehicle_documents_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportGstTax(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? $request->to_date : now()->toDateString();
        $query = Bulty::with('gstMaster','consignor','consignee','vehicle','originCity','destinationCity')
            ->where('status', 'delivered')->whereNotNull('gst_master_id')->whereBetween('lr_date', [$fromDate, $toDate]);
        if ($companyId && $companyId !== 'all') $query->where('company_id', $companyId);
        if ($request->filled('gst_master_id')) $query->where('gst_master_id', $request->gst_master_id);
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        $bulties = $query->orderBy('lr_date', 'desc')->get();
        $totalBills = $bulties->count();
        $totalFreight = $bulties->sum('freight_charges');
        $totalGst = $bulties->sum('gst_amount');
        $totalOtherCharges = $bulties->sum('other_charges');
        $totalAmount = $bulties->sum('total_amount');
        $gstBreakdown = $bulties->groupBy(fn($b) => $b->gstMaster?->gst_rate ?? 'N/A')
            ->map(fn($group) => ['count' => $group->count(),'freight' => $group->sum('freight_charges'),'gst' => $group->sum('gst_amount'),'other' => $group->sum('other_charges'),'total' => $group->sum('total_amount')]);

        if ($format === 'pdf') {
            $title = 'GST & Tax Report';
            $pdf = Pdf::loadView('admin.reports.pdf.gst_tax', compact(
                'bulties','fromDate','toDate','totalBills','totalFreight','totalGst','totalOtherCharges','totalAmount','gstBreakdown','title'
            ));
            return $pdf->download('gst_tax_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['#', 'LR No', 'Date', 'Consignor', 'Consignee', 'From → To', 'Vehicle', 'Freight', 'GST Rate', 'GST', 'Other', 'Total'];
        $data = [];
        foreach ($bulties as $i => $b) {
            $data[] = [
                $i + 1, $b->lr_no, $b->lr_date->format('d-m-Y'), $b->consignor?->name ?? '-',
                $b->consignee?->name ?? '-',
                ($b->originCity?->name ?? '-') . ' → ' . ($b->destinationCity?->name ?? '-'),
                $b->vehicle?->vehicle_number ?? '-',
                number_format($b->freight_charges, 0), $b->gstMaster?->gst_rate ?? 'N/A',
                number_format($b->gst_amount, 0), number_format($b->other_charges, 0),
                number_format($b->total_amount, 0),
            ];
        }
        return Excel::download(new ReportExport($headings, $data, 'GST & Tax Report'), 'gst_tax_report_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportProfitLoss(Request $request, $format = 'excel')
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? ($request->filled('company_id') ? $request->company_id : session('current_company_id')) : $user->company_id;
        $selectedYear = $request->filled('year') ? $request->year : session('current_year', date('Y'));
        if ($selectedYear === 'all') $selectedYear = null;
        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate = $request->filled('to_date') ? $request->to_date : null;
        $baseQuery = Bulty::whereNotIn('status', ['pending', 'planned'])
            ->when($companyId && $companyId !== 'all', fn($q) => $q->where('company_id', $companyId))
            ->when($selectedYear, fn($q, $y) => $q->whereYear('lr_date', $y))
            ->when($fromDate, fn($q, $d) => $q->whereDate('lr_date', '>=', $d))
            ->when($toDate, fn($q, $d) => $q->whereDate('lr_date', '<=', $d));
        $totalIncome = (clone $baseQuery)->sum('total_amount');
        $totalCommission = (clone $baseQuery)->sum('bilty_commission');
        $bultyIds = (clone $baseQuery)->pluck('id');
        $fuelExpense = max((float)TripFuelDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('fuel_amount'));
        $fasttagExpense = max((float)TripFastTagDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('fasttag_total_amount'));
        $adblueExpense = max((float)TripAdBlueDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('adblue_total_amount'));
        $otherTripExpense = max((float)TripOtherAmountDetail::whereIn('builty_id', $bultyIds)->sum('amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('other_amount'));
        $tripAdvance = max((float)TripAdvanceDetail::whereIn('builty_id', $bultyIds)->sum('advance_amount'), (float)Trip::whereIn('builty_id', $bultyIds)->sum('advance_total_amount'));
        $totalTripExpenses = $fuelExpense + $fasttagExpense + $adblueExpense + $otherTripExpense + $tripAdvance;
        $totalExpenses = $totalTripExpenses + $totalCommission;
        $netProfit = $totalIncome - $totalExpenses;
        $summary = [
            'total_income' => round($totalIncome, 0), 'total_commission' => round($totalCommission, 0),
            'fuel_expense' => round($fuelExpense, 0), 'fasttag_expense' => round($fasttagExpense, 0),
            'adblue_expense' => round($adblueExpense, 0), 'other_trip_expense' => round($otherTripExpense, 0),
            'total_trip_advance' => round($tripAdvance, 0),
            'total_trip_expenses' => round($totalTripExpenses, 0), 'total_expenses' => round($totalExpenses, 0),
            'net_profit' => round($netProfit, 0),
        ];

        if ($format === 'pdf') {
            $title = 'Profit & Loss Report';
            $pdf = Pdf::loadView('admin.reports.pdf.profit_loss', compact('summary', 'title'));
            return $pdf->download('profit_loss_report_' . now()->format('Y-m-d') . '.pdf');
        }

        $headings = ['Category', 'Amount (₹)'];
        $data = [
            ['Total Income', number_format($summary['total_income'], 0)],
            ['Fuel Expense', number_format($summary['fuel_expense'], 0)],
            ['FastTag Expense', number_format($summary['fasttag_expense'], 0)],
            ['AdBlue Expense', number_format($summary['adblue_expense'], 0)],
            ['Other Trip Expense', number_format($summary['other_trip_expense'], 0)],
            ['Trip Advance', number_format($summary['total_trip_advance'], 0)],
            ['Bilty Commission', number_format($summary['total_commission'], 0)],
            ['Total Expenses', number_format($summary['total_expenses'], 0)],
            ['Net Profit/Loss', number_format($summary['net_profit'], 0)],
        ];
        return Excel::download(new ReportExport($headings, $data, 'Profit & Loss Report'), 'profit_loss_report_' . now()->format('Y-m-d') . '.xlsx');
    }
}
