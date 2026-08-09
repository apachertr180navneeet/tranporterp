<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Bulty;
use App\Models\Trip;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $kpi = $this->getSuperAdminKPIs();
        } elseif ($user->isCompanyAdmin()) {
            $kpi = $this->getCompanyAdminKPIs($user->company_id);
        } else {
            $kpi = $this->getBranchManagerKPIs($user->branch_id);
        }

        $query = ActivityLog::with(['user', 'company', 'branch'])->orderBy('created_at', 'desc')->limit(10);

        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        $recentActivities = $query->get();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        return view('admin.dashboard.index', compact('kpi', 'recentActivities', 'todayAttendance'));
    }

    private function getSuperAdminKPIs()
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $totalBranches = Branch::count();
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $totalRoles = DB::table('roles')->count();
        $totalPermissions = DB::table('permissions')->count();

        $recentLogins = User::whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
            ->limit(5)
            ->get();

        $companyStats = Company::select('companies.name', DB::raw('COUNT(branches.id) as branch_count'))
            ->leftJoin('branches', 'companies.id', '=', 'branches.company_id')
            ->groupBy('companies.id', 'companies.name')
            ->orderBy('branch_count', 'desc')
            ->limit(10)
            ->get();

        // Transport stats (no company scope for super admin)
        $totalVehicles = Vehicle::withoutGlobalScopes()->count();
        $totalDrivers  = Driver::withoutGlobalScopes()->count();
        $totalBulties  = Bulty::withoutGlobalScopes()->count();
        $totalTrips    = Trip::count();

        // Monthly LR trend – last 6 months
        $monthlyLRs = $this->getMonthlyLRTrend();

        // Monthly P&L trend – last 12 months (for chart)
        $companyId = session('current_company_id');
        $monthlyPnL = $this->getMonthlyPnLTrend($companyId);

        // Expiring documents (single SQL query instead of loading all vehicles)
        $threshold = now()->addDays(30);
        $expiringDocuments = collect();
        $vehicleFields = [
            'insurance_expiry' => 'Insurance',
            'fitness_expiry' => 'Fitness Certificate',
            'permit_expiry' => 'Permit',
            'pollution_expiry' => 'Pollution Certificate',
        ];
        $thresholdDate = $threshold->toDateString();
        $companyName = 'N/A';
        if ($companyId && $companyId !== 'all') {
            $comp = Company::find($companyId);
            $companyName = $comp ? $comp->name : 'N/A';
        } else {
            $companyName = 'All Companies';
        }

        $unionSql = '';
        $unionBindings = [];
        $first = true;
        foreach ($vehicleFields as $field => $label) {
            $sql = "(SELECT v.id as vehicle_id, v.vehicle_number, ? as company_name, ? as document, ? as document_field, v.$field as expiry_date, DATEDIFF(?, v.$field) as days_left FROM vehicles v WHERE v.status = 'active' AND v.$field IS NOT NULL AND v.$field <= ?)";
            $unionBindings = array_merge($unionBindings, [$companyName, $label, $field, $thresholdDate, $thresholdDate]);
            if ($first) {
                $unionSql = $sql;
                $first = false;
            } else {
                $unionSql .= " UNION ALL " . $sql;
            }
        }
        $expiringDocuments = collect(DB::select($unionSql, $unionBindings))
            ->map(fn($d) => (array) $d)
            ->sortBy('days_left')
            ->values();

        return [
            'total_companies'    => $totalCompanies,
            'active_companies'   => $activeCompanies,
            'total_branches'     => $totalBranches,
            'total_users'        => $totalUsers,
            'active_users'       => $activeUsers,
            'total_roles'        => $totalRoles,
            'total_permissions'  => $totalPermissions,
            'recent_logins'      => $recentLogins,
            'company_stats'      => $companyStats,
            // transport
            'total_vehicles'     => $totalVehicles,
            'total_drivers'      => $totalDrivers,
            'total_bulties'      => $totalBulties,
            'total_trips'        => $totalTrips,
            'monthly_lrs'        => $monthlyLRs,
            'monthly_pnl'        => $monthlyPnL,
            'expiring_documents' => $expiringDocuments,
        ];
    }

    /** Last 6 months LR count (all companies, no scope). */
    private function getMonthlyLRTrend(): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $monthlyCounts = Bulty::withoutGlobalScopes()
            ->select(DB::raw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as count'))
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get()
            ->keyBy(fn($r) => $r->y . '-' . $r->m);

        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            $key = $date->year . '-' . $date->month;
            $counts[] = (int)($monthlyCounts[$key]->count ?? 0);
        }
        return ['months' => $months, 'counts' => $counts];
    }

    /** Monthly P&L trend – last 12 months (for dashboard chart). */
    private function getMonthlyPnLTrend($companyId = null, $branchId = null): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];

        $twelveMonthsAgo = Carbon::now()->subMonths(11)->startOfMonth();

        $bultyWhere = "status NOT IN ('pending','planned')";
        $bultyParams = [];
        if ($companyId && $companyId !== 'all') {
            $bultyWhere .= " AND company_id = ?";
            $bultyParams[] = $companyId;
        }
        if ($branchId) {
            $bultyWhere .= " AND branch_id = ?";
            $bultyParams[] = $branchId;
        }

        $monthlyBulties = DB::select(
            "SELECT YEAR(lr_date) as y, MONTH(lr_date) as m,
                    COALESCE(SUM(total_amount),0) as income,
                    COALESCE(SUM(bilty_commission),0) as commission
             FROM bulties
             WHERE $bultyWhere AND lr_date >= ?
             GROUP BY YEAR(lr_date), MONTH(lr_date)
             ORDER BY y, m",
            array_merge($bultyParams, [$twelveMonthsAgo])
        );

        $monthlyTrips = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m,
                    COALESCE(SUM(t.fuel_amount),0) as trip_fuel,
                    COALESCE(SUM(t.fasttag_total_amount),0) as trip_fasttag,
                    COALESCE(SUM(t.adblue_total_amount),0) as trip_adblue,
                    COALESCE(SUM(t.other_amount),0) as trip_other,
                    COALESCE(SUM(t.advance_total_amount),0) as trip_advance
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             WHERE b.$bultyWhere AND b.lr_date >= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)
             ORDER BY y, m",
            array_merge($bultyParams, [$twelveMonthsAgo])
        );

        $monthlyFuel = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tfd.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_fuel_details tfd ON tfd.trip_id = t.id
             WHERE b.$bultyWhere AND b.lr_date >= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$twelveMonthsAgo])
        );

        $monthlyFasttag = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tft.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_fast_tag_details tft ON tft.trip_id = t.id
             WHERE b.$bultyWhere AND b.lr_date >= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$twelveMonthsAgo])
        );

        $monthlyAdblue = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tad.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_adblue_details tad ON tad.trip_id = t.id
             WHERE b.$bultyWhere AND b.lr_date >= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$twelveMonthsAgo])
        );

        $monthlyOther = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(toad.amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_other_amount_details toad ON toad.trip_id = t.id
             WHERE b.$bultyWhere AND b.lr_date >= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$twelveMonthsAgo])
        );

        $monthlyAdv = DB::select(
            "SELECT YEAR(b.lr_date) as y, MONTH(b.lr_date) as m, COALESCE(SUM(tad2.advance_amount),0) as amt
             FROM bulties b
             JOIN trips t ON t.builty_id = b.id
             JOIN trip_advance_details tad2 ON tad2.trip_id = t.id
             WHERE b.$bultyWhere AND b.lr_date >= ?
             GROUP BY YEAR(b.lr_date), MONTH(b.lr_date)",
            array_merge($bultyParams, [$twelveMonthsAgo])
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

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->year . '-' . $date->month;
            $months[] = $date->format('M Y');

            $mb = $monthlyBultyMap[$key] ?? null;
            $mt = $monthlyTripMap[$key] ?? null;

            $income = (float)($mb->income ?? 0);
            $commission = (float)($mb->commission ?? 0);

            $fuel = max($monthlyFuelMap[$key] ?? 0, (float)($mt->trip_fuel ?? 0));
            $fasttag = max($monthlyFasttagMap[$key] ?? 0, (float)($mt->trip_fasttag ?? 0));
            $adblue = max($monthlyAdblueMap[$key] ?? 0, (float)($mt->trip_adblue ?? 0));
            $other = max($monthlyOtherMap[$key] ?? 0, (float)($mt->trip_other ?? 0));
            $advance = max($monthlyAdvMap[$key] ?? 0, (float)($mt->trip_advance ?? 0));

            $tripExpenses = $fuel + $fasttag + $adblue + $other + $advance;

            $incomeData[] = round($income, 0);
            $expenseData[] = round($tripExpenses + $commission, 0);
        }
        return ['months' => $months, 'income' => $incomeData, 'expense' => $expenseData];
    }

    private function getCompanyAdminKPIs($companyId)
    {
        $company = Company::find($companyId);
        $totalBranches = Branch::where('company_id', $companyId)->count();
        $totalUsers    = User::where('company_id', $companyId)->count();
        $activeUsers   = User::where('company_id', $companyId)->where('status', 'active')->count();

        $totalVehicles = Vehicle::withoutGlobalScopes()->count();
        $totalDrivers  = Driver::withoutGlobalScopes()->count();
        $totalBulties  = Bulty::withoutGlobalScopes()->where('company_id', $companyId)->count();
        $totalTrips    = Bulty::withoutGlobalScopes()->where('company_id', $companyId)
            ->has('trip')->count();

        $monthlyLRs = $this->getCompanyMonthlyLRTrend($companyId);
        $monthlyPnL = $this->getMonthlyPnLTrend($companyId);

        return [
            'company'        => $company,
            'total_branches' => $totalBranches,
            'total_users'    => $totalUsers,
            'active_users'   => $activeUsers,
            'total_vehicles' => $totalVehicles,
            'total_drivers'  => $totalDrivers,
            'total_bulties'  => $totalBulties,
            'total_trips'    => $totalTrips,
            'monthly_lrs'    => $monthlyLRs,
            'monthly_pnl'    => $monthlyPnL,
        ];
    }

    private function getCompanyMonthlyLRTrend($companyId): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $monthlyCounts = Bulty::withoutGlobalScopes()
            ->select(DB::raw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get()
            ->keyBy(fn($r) => $r->y . '-' . $r->m);

        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            $key = $date->year . '-' . $date->month;
            $counts[] = (int)($monthlyCounts[$key]->count ?? 0);
        }
        return ['months' => $months, 'counts' => $counts];
    }

    private function getBranchManagerKPIs($branchId)
    {
        $branch      = Branch::find($branchId);
        $companyId   = $branch->company_id ?? null;
        $totalUsers  = User::where('branch_id', $branchId)->count();
        $activeUsers = User::where('branch_id', $branchId)->where('status', 'active')->count();

        $totalVehicles = Vehicle::withoutGlobalScopes()->count();
        $totalDrivers  = Driver::withoutGlobalScopes()->count();
        $totalBulties  = Bulty::withoutGlobalScopes()->where('branch_id', $branchId)->count();
        $totalTrips    = Bulty::withoutGlobalScopes()->where('branch_id', $branchId)
            ->has('trip')->count();

        $monthlyLRs = $this->getBranchMonthlyLRTrend($branchId);
        $monthlyPnL = $this->getMonthlyPnLTrend($companyId, $branchId);

        return [
            'branch'         => $branch,
            'total_users'    => $totalUsers,
            'active_users'   => $activeUsers,
            'total_vehicles' => $totalVehicles,
            'total_drivers'  => $totalDrivers,
            'total_bulties'  => $totalBulties,
            'total_trips'    => $totalTrips,
            'monthly_lrs'    => $monthlyLRs,
            'monthly_pnl'    => $monthlyPnL,
        ];
    }

    private function getBranchMonthlyLRTrend($branchId): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $monthlyCounts = Bulty::withoutGlobalScopes()
            ->select(DB::raw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as count'))
            ->where('branch_id', $branchId)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get()
            ->keyBy(fn($r) => $r->y . '-' . $r->m);

        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            $key = $date->year . '-' . $date->month;
            $counts[] = (int)($monthlyCounts[$key]->count ?? 0);
        }
        return ['months' => $months, 'counts' => $counts];
    }

    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with(['user', 'company', 'branch']);

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

        if (!auth()->user()->isSuperAdmin()) {
            $query->where('company_id', auth()->user()->company_id);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);
        $actions = cache()->remember('activity_log_actions', 3600, fn() => ActivityLog::distinct()->pluck('action'));
        $users = User::active()->get();

        return view('admin.activity_logs.index', compact('logs', 'actions', 'users'));
    }

    public function systemSettings()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('admin.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_timezone' => 'required|string',
            'app_email' => 'nullable|email',
            'app_phone' => 'nullable|string|max:50',
            'app_address' => 'nullable|string',
            'app_logo' => 'nullable|image|max:2048',
        ]);

        $keys = ['app_name', 'app_timezone', 'app_email', 'app_phone', 'app_address'];

        foreach ($keys as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => $request->input($key) ?? '']);
        }

        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('settings', 'uploads');
            Setting::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);
        }

        ActivityLog::log('settings_update', 'System settings updated');

        return back()->with('success', 'Settings updated successfully.');
    }

    public function switchCompany(Request $request)
    {
        $companyId = $request->company_id;

        if ($companyId === 'all') {
            if (!auth()->user()->isSuperAdmin()) {
                return back()->with('error', 'Unauthorized: only super admin can view all companies.');
            }
            session(['current_company_id' => 'all', 'current_branch_id' => null]);
        } elseif ($companyId) {
            $user = auth()->user();
            if (!$user->isSuperAdmin() && !$user->canAccessCompany($companyId)) {
                return back()->with('error', 'Unauthorized company access.');
            }
            session(['current_company_id' => $companyId, 'current_branch_id' => null]);
        }

        return redirect()->back();
    }

    public function switchYear(Request $request)
    {
        $year = $request->year;
        if ($year === 'all' || ($year >= 2025 && $year <= date('Y') + 5)) {
            session(['current_year' => $year]);
        }
        return redirect()->back();
    }
}
