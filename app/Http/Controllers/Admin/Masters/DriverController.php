<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\ActivityLog;
use App\Imports\DriverImport;
use App\Exports\DriverTemplateExport;
use App\Exports\DriversExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = Driver::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhere('driver_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $drivers = $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        return view('admin.masters.drivers.index', compact('drivers'));
    }

    public function create()
    {
        if (!auth()->user()->can('create drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return view('admin.masters.drivers.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'driver_id' => 'nullable|string|max:50|unique:drivers,driver_id',
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:10', Rule::unique('drivers', 'phone')],
            'license_number' => 'required|string|max:50|unique:drivers,license_number',
            'license_expiry' => "nullable|date|before_or_equal:9999-12-31",
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:20',
            'license_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'license_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pan_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pan_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $documentFields = ['license_front', 'license_back', 'aadhar_front', 'aadhar_back', 'pan_front', 'pan_back'];
        $driverData = array_diff_key($validated, array_flip($documentFields));
        $driverData['status'] = 'active';

        $driver = Driver::create($driverData);
        $this->uploadDocuments($request, $driver);
        $driver->refresh();

        ActivityLog::log('driver_created', "Created driver: {$driver->name}", $driver);

        return redirect()->route('admin.masters.drivers.index')->with('success', 'Driver created successfully.');
    }

    public function edit(Driver $driver)
    {
        if (!auth()->user()->can('edit drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return view('admin.masters.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        if (!auth()->user()->can('edit drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'driver_id' => ['nullable', 'string', 'max:50', Rule::unique('drivers', 'driver_id')->ignore($driver->id)],
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:10', Rule::unique('drivers', 'phone')->ignore($driver->id)],
            'license_number' => ['required', 'string', 'max:50', Rule::unique('drivers', 'license_number')->ignore($driver->id)],
            'license_expiry' => "nullable|date|before_or_equal:9999-12-31",
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:20',
            'license_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'license_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pan_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pan_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $documentFields = ['license_front', 'license_back', 'aadhar_front', 'aadhar_back', 'pan_front', 'pan_back'];
        $driver->update(array_diff_key($validated, array_flip($documentFields)));
        $this->uploadDocuments($request, $driver);
        $driver->refresh();
        ActivityLog::log('driver_updated', "Updated driver: {$driver->name}", $driver);

        return redirect()->route('admin.masters.drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function import(Request $request)
    {
        if (!auth()->user()->can('import drivers') && !auth()->user()->can('create drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $import = new DriverImport;
        try {
            Excel::import($import, $request->file('file'));
            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $failures = $import->getFailures();
            $headings = $import->getHeadings();

            $message = "{$imported} driver(s) imported successfully.";
            if ($skipped > 0) $message .= " {$skipped} row(s) skipped (duplicate phone/license).";
            if (!empty($failures)) {
                $errs = [];
                foreach ($failures as $f) $errs[] = "Row {$f->row()}: " . implode(', ', $f->errors());
                $message .= ' Errors: ' . implode(' | ', array_slice($errs, 0, 5));
                if (count($errs) > 5) $message .= ' ... and ' . (count($errs) - 5) . ' more.';
            }
            if ($imported === 0 && $skipped === 0 && empty($failures))
                $message .= ' No data found. Detected headers: ' . (!empty($headings) ? implode(', ', $headings) : 'none');

            ActivityLog::log('drivers_imported', "Imported {$imported} drivers from Excel, {$skipped} skipped");
            return redirect()->route('admin.masters.drivers.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.masters.drivers.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        if (!auth()->user()->can('create drivers') && !auth()->user()->can('import drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        ActivityLog::log('driver_template_downloaded', 'Downloaded driver import template');
        return Excel::download(new DriverTemplateExport, 'driver_import_template.xlsx');
    }

    public function export()
    {
        if (!auth()->user()->can('export drivers') && !auth()->user()->can('view drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        ActivityLog::log('drivers_exported', 'Exported drivers to Excel');
        return Excel::download(new DriversExport, 'drivers_export.xlsx');
    }

    public function trashed()
    {
        if (!auth()->user()->can('delete drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $drivers = Driver::onlyTrashed()->paginate(15);
        return view('admin.masters.drivers.trashed', compact('drivers'));
    }

    public function restore($id)
    {
        if (!auth()->user()->can('delete drivers') && !auth()->user()->can('restore drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $driver = Driver::withTrashed()->findOrFail($id);
        $driver->restore();
        ActivityLog::log('driver_restored', "Restored driver: {$driver->name}");
        return redirect()->route('admin.masters.drivers.trashed')->with('success', 'Driver restored successfully.');
    }

    public function forceDelete($id)
    {
        if (!auth()->user()->can('delete drivers') && !auth()->user()->can('force delete drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $driver = Driver::withTrashed()->findOrFail($id);
        ActivityLog::log('driver_force_deleted', "Force deleted driver: {$driver->name}");
        $driver->forceDelete();
        return redirect()->route('admin.masters.drivers.trashed')->with('success', 'Driver permanently deleted.');
    }

    public function getDetailsByName(Request $request)
    {
        if (!auth()->user()->can('view drivers') && !auth()->user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $term = trim($request->driver_name ?? '');
        if (empty($term)) {
            return response()->json(['success' => false, 'message' => 'Driver identifier is required']);
        }

        $cleanTerm = preg_replace('/[^A-Za-z0-9]/', '', $term);

        // 1. Exact match
        $driver = Driver::where('name', $term)
            ->orWhere('phone', $term)
            ->orWhere('driver_id', $term)
            ->first();

        // 2. Normalized phone or partial match
        if (!$driver && !empty($cleanTerm)) {
            $driver = Driver::whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$cleanTerm}%"])
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('driver_id', 'like', "%{$term}%")
                ->first();
        }

        if ($driver) {
            return response()->json([
                'success' => true,
                'driver' => $driver
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Driver not found']);
    }

    public function search(Request $request)
    {
        if (!auth()->user()->can('view drivers') && !auth()->user()->isSuperAdmin()) {
            return response()->json([], 403);
        }

        $term = trim($request->term ?? '');
        $cleanTerm = preg_replace('/[^A-Za-z0-9]/', '', $term);

        $drivers = Driver::where(function ($q) use ($term, $cleanTerm) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('license_number', 'like', "%{$term}%")
              ->orWhere('driver_id', 'like', "%{$term}%");
            if (!empty($cleanTerm)) {
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$cleanTerm}%"]);
            }
        })
        ->limit(10)
        ->get();

        return response()->json($drivers);
    }

    public function quickStore(Request $request)
    {
        if (!auth()->user()->can('create drivers') && !auth()->user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'driver_id' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:10', Rule::unique('drivers', 'phone')],
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => "nullable|date|before_or_equal:9999-12-31",
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $validated['status'] = 'active';

        $driver = Driver::create($validated);
        ActivityLog::log('driver_created', "Quick created driver: {$driver->name}", $driver);

        return response()->json([
            'success' => true,
            'driver' => $driver
        ]);
    }

    public function destroy(Driver $driver)
    {
        if (!auth()->user()->can('delete drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $driver->delete();
        ActivityLog::log('driver_deleted', "Deleted driver: {$driver->name}");
        return redirect()->route('admin.masters.drivers.index')->with('success', 'Driver deleted successfully.');
    }

    public function toggleStatus(Driver $driver)
    {
        if (!auth()->user()->can('edit drivers') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $driver->status = $driver->status === 'active' ? 'inactive' : 'active';
        $driver->save();
        ActivityLog::log('driver_status_changed', "Changed status of driver: {$driver->name}", $driver);
        return back()->with('success', 'Driver status updated.');
    }

    private function uploadDocuments(Request $request, Driver $driver)
    {
        $documentFields = ['license_front', 'license_back', 'aadhar_front', 'aadhar_back', 'pan_front', 'pan_back'];
        $uploadPath = 'uploads/drivers/' . $driver->id;

        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                if ($driver->{$field}) {
                    $oldPath = str_replace(asset('uploads/'), '', $driver->{$field});
                    Storage::disk('uploads')->delete($oldPath);
                }
                $path = $request->file($field)->store($uploadPath, 'uploads');
                $fullUrl = asset('uploads/' . $path);
                $driver->update([$field => $fullUrl]);
            }
        }
    }
}
