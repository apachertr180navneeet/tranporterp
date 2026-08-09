<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverSalary;
use App\Models\DriverAdvance;
use App\Models\SalarySlip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DriverManagementController extends Controller
{
    public function salaryManagement()
    {
        if (!auth()->user()->can('view driver salary') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        $salaries = DriverSalary::with('driver')->latest()->get();
        $editSalary = null;

        return view('admin.driver-management.salary', compact('drivers', 'salaries', 'editSalary'));
    }

    public function storeSalary(Request $request)
    {
        if (!auth()->user()->can('create driver salary') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'salary_amount' => 'required|numeric|min:0',
            'effective_from' => "required|date|before_or_equal:9999-12-31",
            'effective_to' => "nullable|date|after_or_equal:effective_from|before_or_equal:9999-12-31",
        ]);

        DriverSalary::create($validated);

        return redirect()->route('admin.driver-management.salary')
            ->with('success', 'Salary assigned successfully');
    }

    public function editSalary(DriverSalary $salary)
    {
        if (!auth()->user()->can('edit driver salary') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        $salaries = DriverSalary::with('driver')->latest()->get();
        $editSalary = $salary;

        return view('admin.driver-management.salary', compact('drivers', 'salaries', 'editSalary'));
    }

    public function updateSalary(Request $request, DriverSalary $salary)
    {
        if (!auth()->user()->can('edit driver salary') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'salary_amount' => 'required|numeric|min:0',
            'effective_from' => "required|date|before_or_equal:9999-12-31",
            'effective_to' => "nullable|date|after_or_equal:effective_from|before_or_equal:9999-12-31",
        ]);

        $salary->update($validated);

        return redirect()->route('admin.driver-management.salary')
            ->with('success', 'Salary updated successfully');
    }

    public function advanceManagement()
    {
        if (!auth()->user()->can('view driver advances') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        $advances = DriverAdvance::with('driver')->latest()->get();
        $editAdvance = null;

        $deductedSoFar = DB::table('salary_slips')
            ->whereNotNull('advances_data')
            ->where('advances_data', '!=', '[]')
            ->select('advances_data')
            ->get()
            ->reduce(function ($carry, $slip) {
                foreach (json_decode($slip->advances_data, true) ?? [] as $ad) {
                    $aid = $ad['id'] ?? null;
                    if ($aid) {
                        $carry[$aid] = ($carry[$aid] ?? 0) + ($ad['deduction_amount'] ?? 0);
                    }
                }
                return $carry;
            }, []);

        foreach ($advances as $advance) {
            $deducted = $deductedSoFar[$advance->id] ?? 0;
            $advance->is_cleared = $deducted >= $advance->amount;
            $advance->balance = max(0, $advance->amount - $deducted);
        }

        return view('admin.driver-management.advance', compact('drivers', 'advances', 'editAdvance'));
    }

    public function storeAdvance(Request $request)
    {
        if (!auth()->user()->can('create driver advances') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'amount' => 'required|numeric|min:0',
            'deduction_type' => 'required|in:full,monthly',
            'monthly_deduction' => 'nullable|required_if:deduction_type,monthly|numeric|min:0',
            'date' => "required|date|before_or_equal:9999-12-31",
            'remark' => 'nullable|string|max:500',
        ]);

        DriverAdvance::create($validated);

        return redirect()->route('admin.driver-management.advance')
            ->with('success', 'Advance recorded successfully');
    }

    public function editAdvance(DriverAdvance $advance)
    {
        if (!auth()->user()->can('edit driver advances') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        $advances = DriverAdvance::with('driver')->latest()->get();
        $editAdvance = $advance;

        $deductedSoFar = DB::table('salary_slips')
            ->whereNotNull('advances_data')
            ->where('advances_data', '!=', '[]')
            ->select('advances_data')
            ->get()
            ->reduce(function ($carry, $slip) {
                foreach (json_decode($slip->advances_data, true) ?? [] as $ad) {
                    $aid = $ad['id'] ?? null;
                    if ($aid) {
                        $carry[$aid] = ($carry[$aid] ?? 0) + ($ad['deduction_amount'] ?? 0);
                    }
                }
                return $carry;
            }, []);

        foreach ($advances as $adv) {
            $deducted = $deductedSoFar[$adv->id] ?? 0;
            $adv->is_cleared = $deducted >= $adv->amount;
            $adv->balance = max(0, $adv->amount - $deducted);
        }

        return view('admin.driver-management.advance', compact('drivers', 'advances', 'editAdvance'));
    }

    public function updateAdvance(Request $request, DriverAdvance $advance)
    {
        if (!auth()->user()->can('edit driver advances') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'amount' => 'required|numeric|min:0',
            'deduction_type' => 'required|in:full,monthly',
            'monthly_deduction' => 'nullable|required_if:deduction_type,monthly|numeric|min:0',
            'date' => "required|date|before_or_equal:9999-12-31",
            'remark' => 'nullable|string|max:500',
        ]);

        $advance->update($validated);

        return redirect()->route('admin.driver-management.advance')
            ->with('success', 'Advance updated successfully');
    }

    public function destroyAdvance(DriverAdvance $advance)
    {
        if (!auth()->user()->can('delete driver advances') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $advance->delete();

        return redirect()->route('admin.driver-management.advance')
            ->with('success', 'Advance deleted successfully');
    }

    public function salarySlip(Request $request)
    {
        if (!auth()->user()->can('view driver salary slips') && !auth()->user()->can('generate driver salary slips') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $driverId = $request->input('driver_id');

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $months = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create()->month($m)->format('F')]);

        $slip = null;
        $history = SalarySlip::with('driver')->latest('year')->latest('month')->get();

        if ($driverId) {
            $driver = Driver::findOrFail($driverId);

            $existing = SalarySlip::where('driver_id', $driverId)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($existing) {
                $slip = $existing;
                $slip->driver = $driver;
                $slip->monthName = $startOfMonth->format('F');
                $slip->advances = collect($existing->advances_data ?? []);
            } else {
                if (!auth()->user()->can('generate driver salary slips') && !auth()->user()->isSuperAdmin()) {
                    return redirect()->route('admin.driver-management.salary-slip.list')
                        ->with('error', 'Salary slip has not been generated for this month yet. You do not have permission to generate new salary slips.');
                }

                $salary = DriverSalary::where('driver_id', $driverId)
                    ->orderByDesc('effective_from')
                    ->first();

                $salaryAmount = $salary?->salary_amount ?? 0;

                $fullAdvances = DriverAdvance::where('driver_id', $driverId)
                    ->where('deduction_type', 'full')
                    ->whereBetween('date', [$startOfMonth, $startOfMonth->copy()->endOfMonth()])
                    ->get();

                $monthlyAdvances = DriverAdvance::where('driver_id', $driverId)
                    ->where('deduction_type', 'monthly')
                    ->where('date', '<=', $startOfMonth->copy()->endOfMonth())
                    ->get();

                $previousSlips = SalarySlip::where('driver_id', $driverId)
                    ->where(function ($q) use ($year, $month) {
                        $q->where('year', '<', $year)
                          ->orWhere(function ($q) use ($year, $month) {
                              $q->where('year', $year)->where('month', '<', $month);
                          });
                    })
                    ->get();

                $deductedSoFar = [];
                foreach ($previousSlips as $ps) {
                    foreach (collect($ps->advances_data ?? []) as $ad) {
                        $aid = $ad['id'] ?? null;
                        if ($aid) {
                            $deductedSoFar[$aid] = ($deductedSoFar[$aid] ?? 0) + ($ad['deduction_amount'] ?? 0);
                        }
                    }
                }

                $allAdvances = collect();
                $advancesData = collect();
                $totalDeductions = 0;

                foreach ($fullAdvances as $adv) {
                    $allAdvances->push($adv);
                    $advancesData->push([
                        'id' => $adv->id,
                        'date' => $adv->date->format('d-m-Y'),
                        'amount' => $adv->amount,
                        'deduction_type' => 'full',
                        'deduction_amount' => $adv->amount,
                        'balance' => 0,
                        'remark' => $adv->remark,
                    ]);
                    $totalDeductions += $adv->amount;
                }

                foreach ($monthlyAdvances as $adv) {
                    $alreadyDeducted = $deductedSoFar[$adv->id] ?? 0;
                    $remaining = $adv->amount - $alreadyDeducted;

                    if ($remaining <= 0) {
                        continue;
                    }

                    $thisMonthDed = min($adv->monthly_deduction ?? 0, $remaining);

                    $allAdvances->push($adv);
                    $advancesData->push([
                        'id' => $adv->id,
                        'date' => $adv->date->format('d-m-Y'),
                        'amount' => $adv->amount,
                        'deduction_type' => 'monthly',
                        'deduction_amount' => $thisMonthDed,
                        'balance' => $remaining - $thisMonthDed,
                        'remark' => $adv->remark,
                    ]);
                    $totalDeductions += $thisMonthDed;
                }

                $netPayable = $salaryAmount - $totalDeductions;

                $salarySlip = SalarySlip::create([
                    'driver_id' => $driverId,
                    'month' => $month,
                    'year' => $year,
                    'salary_amount' => $salaryAmount,
                    'total_deductions' => $totalDeductions,
                    'net_payable' => $netPayable,
                    'advances_data' => $advancesData,
                    'generated_at' => now(),
                ]);

                $slip = $salarySlip;
                $slip->driver = $driver;
                $slip->monthName = $startOfMonth->format('F');
                $slip->advances = $advancesData;
            }
        }

        return view('admin.driver-management.salary-slip', compact('drivers', 'month', 'year', 'slip', 'months', 'history'));
    }

    public function destroySalarySlip(SalarySlip $salarySlip)
    {
        if (!auth()->user()->can('delete driver salary slips') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $salarySlip->delete();

        return redirect()->route('admin.driver-management.salary-slip')
            ->with('success', 'Salary slip deleted');
    }

    public function salarySlipList()
    {
        if (!auth()->user()->can('view driver salary slips') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $slips = SalarySlip::with('driver')->latest('year')->latest('month')->get();

        return view('admin.driver-management.salary-slip-list', compact('slips'));
    }
}
