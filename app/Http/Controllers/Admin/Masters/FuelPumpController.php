<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Http\Controllers\Controller;
use App\Models\FuelPump;
use App\Models\FuelCompany;
use App\Models\ActivityLog;
use App\Imports\FuelPumpImport;
use App\Exports\FuelPumpTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class FuelPumpController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || (!auth()->user()->can('view fuel pumps') && !auth()->user()->isSuperAdmin())) {
                abort(403);
            }
            return $next($request);
        })->except(['quickStore']);
    }

    public function index(Request $request)
    {
        $query = FuelPump::with('fuelCompany');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('owner_mobile', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('fuelCompany', function($b) use ($search) {
                      $b->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $fuelPumps = $query->orderBy('name')->paginate(15);
        $fuelCompanies = FuelCompany::where(function($q) {
            $q->where('status', 'active')->orWhereNull('status');
        })->orderBy('name')->get();
        return view('admin.masters.fuel-pumps.index', compact('fuelPumps', 'fuelCompanies'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'fuel_company_id' => 'nullable|exists:fuel_companies,id',
        ]);

        $import = new FuelPumpImport($request->fuel_company_id);
        try {
            Excel::import($import, $request->file('file'));
            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $failures = $import->getFailures();
            $headings = $import->getHeadings();

            $message = "{$imported} fuel pump(s) imported successfully.";
            if ($skipped > 0) $message .= " {$skipped} row(s) skipped (duplicate name).";
            if (!empty($failures)) {
                $errs = [];
                foreach ($failures as $f) $errs[] = "Row {$f->row()}: " . implode(', ', $f->errors());
                $message .= ' Errors: ' . implode(' | ', array_slice($errs, 0, 5));
                if (count($errs) > 5) $message .= ' ... and ' . (count($errs) - 5) . ' more.';
            }
            if ($imported === 0 && $skipped === 0 && empty($failures))
                $message .= ' No data found. Detected headers: ' . (!empty($headings) ? implode(', ', $headings) : 'none');

            ActivityLog::log('fuel_pumps_imported', "Imported {$imported} fuel pumps from Excel");
            return redirect()->route('admin.masters.fuel-pumps.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.masters.fuel-pumps.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        ActivityLog::log('fuel_pump_template_downloaded', 'Downloaded fuel pump import template');
        return Excel::download(new FuelPumpTemplateExport, 'fuel_pump_import_template.xlsx');
    }

    public function create()
    {
        $fuelCompanies = FuelCompany::where(function($q) {
            $q->where('status', 'active')->orWhereNull('status');
        })->orderBy('name')->get();
        return view('admin.masters.fuel-pumps.create', compact('fuelCompanies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255|unique:fuel_pumps,name',
            'fuel_company_id'  => 'nullable|exists:fuel_companies,id',
            'number'           => 'nullable|string|max:255',
            'address'          => 'nullable|string|max:500',
            'owner_name'       => 'nullable|string|max:255',
            'owner_mobile'     => 'nullable|string|digits:10',
        ]);

        FuelPump::create($validated);

        return redirect()->route('admin.masters.fuel-pumps.index')
                       ->with('success', 'Fuel pump created successfully.');
    }

    public function edit(FuelPump $fuelPump)
    {
        $fuelCompanies = FuelCompany::where(function($q) {
            $q->where('status', 'active')->orWhereNull('status');
        })->orderBy('name')->get();
        return view('admin.masters.fuel-pumps.edit', compact('fuelPump', 'fuelCompanies'));
    }

    public function update(Request $request, FuelPump $fuelPump)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255|unique:fuel_pumps,name,' . $fuelPump->id,
            'fuel_company_id'  => 'nullable|exists:fuel_companies,id',
            'number'           => 'nullable|string|max:255',
            'address'          => 'nullable|string|max:500',
            'owner_name'       => 'nullable|string|max:255',
            'owner_mobile'     => 'nullable|string|digits:10',
        ]);

        $fuelPump->update($validated);

        return redirect()->route('admin.masters.fuel-pumps.index')
                       ->with('success', 'Fuel pump updated successfully.');
    }

    public function destroy(FuelPump $fuelPump)
    {
        $fuelPump->delete();

        return redirect()->route('admin.masters.fuel-pumps.index')
                       ->with('success', 'Fuel pump deleted successfully.');
    }

    public function trashed()
    {
        $fuelPumps = FuelPump::onlyTrashed()->with('fuelCompany')->orderBy('deleted_at', 'desc')->paginate(15);
        return view('admin.masters.fuel-pumps.trashed', compact('fuelPumps'));
    }

    public function restore($id)
    {
        $fuelPump = FuelPump::withTrashed()->findOrFail($id);
        $fuelPump->restore();
        ActivityLog::log('fuel_pump_restored', "Restored fuel pump: {$fuelPump->name}");
        return redirect()->route('admin.masters.fuel-pumps.trashed')->with('success', 'Fuel pump restored successfully.');
    }

    public function forceDelete($id)
    {
        $fuelPump = FuelPump::withTrashed()->findOrFail($id);
        ActivityLog::log('fuel_pump_force_deleted', "Force deleted fuel pump: {$fuelPump->name}");
        $fuelPump->forceDelete();
        return redirect()->route('admin.masters.fuel-pumps.trashed')->with('success', 'Fuel pump permanently deleted.');
    }

    public function toggleStatus(FuelPump $fuelPump)
    {
        $fuelPump->status = $fuelPump->status === 'active' ? 'inactive' : 'active';
        $fuelPump->save();
        ActivityLog::log('fuel_pump_status_changed', "Changed status of fuel pump: {$fuelPump->name}", $fuelPump);
        return back()->with('success', 'Fuel pump status updated.');
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fuel_pumps,name',
        ]);

        $fuelPump = FuelPump::create($validated);

        return response()->json(['id' => $fuelPump->id, 'name' => $fuelPump->name]);
    }
}
