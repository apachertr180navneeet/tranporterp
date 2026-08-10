<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ActivityLog;
use App\Imports\CityImport;
use App\Exports\CityTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || (!auth()->user()->can('view cities') && !auth()->user()->isSuperAdmin())) {
                abort(403);
            }
            return $next($request);
        })->except(['quickStore', 'search']);
    }

    public function search(Request $request)
    {
        $term = trim($request->input('q') ?? $request->input('term') ?? '');

        $query = City::where('status', 'active');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('state', 'like', "%{$term}%");
            });
        }

        $cities = $query->orderBy('name')->limit(50)->get();

        $results = $cities->map(function ($city) {
            return [
                'id' => $city->id,
                'text' => $city->name . ' (' . $city->state . ')',
            ];
        });

        return response()->json([
            'results' => $results
        ]);
    }

    public function index(Request $request)
    {
        $query = City::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%");
            });
        }

        $cities = $query->orderBy('name')->paginate(15);
        return view('admin.masters.city.index', compact('cities'));
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $import = new CityImport;
        try {
            Excel::import($import, $request->file('file'));
            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $failures = $import->getFailures();
            $headings = $import->getHeadings();

            $message = "{$imported} city/cities imported successfully.";
            if ($skipped > 0) $message .= " {$skipped} row(s) skipped (duplicate city name + state).";
            if (!empty($failures)) {
                $errs = [];
                foreach ($failures as $f) $errs[] = "Row {$f->row()}: " . implode(', ', $f->errors());
                $message .= ' Errors: ' . implode(' | ', array_slice($errs, 0, 5));
                if (count($errs) > 5) $message .= ' ... and ' . (count($errs) - 5) . ' more.';
            }
            if ($imported === 0 && $skipped === 0 && empty($failures))
                $message .= ' No data found. Detected headers: ' . (!empty($headings) ? implode(', ', $headings) : 'none');

            ActivityLog::log('cities_imported', "Imported {$imported} cities from Excel");
            return redirect()->route('admin.masters.city.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.masters.city.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        ActivityLog::log('city_template_downloaded', 'Downloaded city import template');
        return Excel::download(new CityTemplateExport, 'city_import_template.xlsx');
    }

    public function create()
    {
        return view('admin.masters.city.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cities,name',
            'state' => 'required|string|max:255',
        ]);

        City::create($validated);

        return redirect()->route('admin.masters.city.index')
                       ->with('success', 'City created successfully.');
    }

    public function edit(City $city)
    {
        return view('admin.masters.city.edit', compact('city'));
    }

    public function update(Request $request, City $city)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cities,name,' . $city->id,
            'state' => 'required|string|max:255',
        ]);

        $city->update($validated);

        return redirect()->route('admin.masters.city.index')
                       ->with('success', 'City updated successfully.');
    }

    public function destroy(City $city)
    {
        $city->delete();

        return redirect()->route('admin.masters.city.index')
                       ->with('success', 'City deleted successfully.');
    }

    public function trashed()
    {
        $cities = City::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(15);
        return view('admin.masters.city.trashed', compact('cities'));
    }

    public function restore($id)
    {
        $city = City::withTrashed()->findOrFail($id);
        $city->restore();
        ActivityLog::log('city_restored', "Restored city: {$city->name}");
        return redirect()->route('admin.masters.city.trashed')->with('success', 'City restored successfully.');
    }

    public function forceDelete($id)
    {
        $city = City::withTrashed()->findOrFail($id);
        ActivityLog::log('city_force_deleted', "Force deleted city: {$city->name}");
        $city->forceDelete();
        return redirect()->route('admin.masters.city.trashed')->with('success', 'City permanently deleted.');
    }

    public function toggleStatus(City $city)
    {
        $city->status = $city->status === 'active' ? 'inactive' : 'active';
        $city->save();
        ActivityLog::log('city_status_changed', "Changed status of city: {$city->name}", $city);
        return back()->with('success', 'City status updated.');
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cities,name',
            'state' => 'required|string|max:255',
        ]);

        $city = City::create($validated);

        return response()->json(['id' => $city->id, 'name' => $city->name, 'state' => $city->state]);
    }
}
