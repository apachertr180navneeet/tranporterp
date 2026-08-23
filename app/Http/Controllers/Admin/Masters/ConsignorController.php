<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Http\Controllers\Controller;
use App\Models\Consignor;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ActivityLog;
use App\Imports\ConsignorImport;
use App\Exports\ConsignorTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsignorController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = Consignor::with(['branch', 'company']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $consignors = $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        return view('admin.masters.consignors.index', compact('consignors'));
    }

    public function create()
    {
        if (!auth()->user()->can('create consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $branches = Branch::where('status', 'active')->get();
        return view('admin.masters.consignors.create', compact('branches'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'vendor_code' => ['nullable', 'string', 'max:50', Rule::unique('consignors', 'vendor_code')],
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:10', Rule::unique('consignors', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('consignors', 'email')],
            'gstin' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ]);

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $validated['company_id'] = $request->validate(['company_id' => 'required|exists:companies,id'])['company_id'];
        } else {
            abort_if(!$user->company_id, 403, 'Your account is not associated with any company.');
            $validated['company_id'] = $user->company_id;
        }

        if (!$user->isSuperAdmin()) {
            $validated['branch_id'] = $validated['branch_id'] ?? $user->branch_id;
        }

        $validated['status'] = 'active';

        $consignor = Consignor::create($validated);
        ActivityLog::log('consignor_created', "Created consignor: {$consignor->name}", $consignor);

        return redirect()->route('admin.masters.consignors.index')->with('success', 'Consignor created successfully.');
    }

    public function edit(Consignor $consignor)
    {
        if (!auth()->user()->can('edit consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $branches = Branch::where('status', 'active')->get();
        return view('admin.masters.consignors.edit', compact('consignor', 'branches'));
    }

    public function update(Request $request, Consignor $consignor)
    {
        if (!auth()->user()->can('edit consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'vendor_code' => ['nullable', 'string', 'max:50', Rule::unique('consignors', 'vendor_code')->ignore($consignor->id)],
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:10', Rule::unique('consignors', 'phone')->ignore($consignor->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('consignors', 'email')->ignore($consignor->id)],
            'gstin' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ]);

        if (auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = $request->validate(['company_id' => 'required|exists:companies,id'])['company_id'];
        }

        $consignor->update($validated);
        ActivityLog::log('consignor_updated', "Updated consignor: {$consignor->name}", $consignor);

        return redirect()->route('admin.masters.consignors.index')->with('success', 'Consignor updated successfully.');
    }

    public function import(Request $request)
    {
        if (!auth()->user()->can('create consignors') && !auth()->user()->can('import consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $user = auth()->user();
        $companyId = $request->company_id ?? ($user->isSuperAdmin() ? null : $user->company_id);
        $branchId = $request->branch_id ?? ($user->isSuperAdmin() ? null : $user->branch_id);

        $import = new ConsignorImport($companyId, $branchId);
        try {
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $failures = $import->getFailures();
            $headings = $import->getHeadings();

            $message = "{$imported} consignor(s) imported successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped (missing data or duplicate phone/vendor code).";
            }

            if (!empty($failures)) {
                $errorMessages = [];
                foreach ($failures as $failure) {
                    $errorMessages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
                }
                $message .= ' Validation errors: ' . implode(' | ', array_slice($errorMessages, 0, 5));
                if (count($errorMessages) > 5) {
                    $message .= ' ... and ' . (count($errorMessages) - 5) . ' more.';
                }
            }

            if ($imported === 0 && $skipped === 0 && empty($failures)) {
                $headingsStr = !empty($headings) ? ' Detected headers: ' . implode(', ', $headings) : ' No headers detected.';
                $message .= ' Ensure your Excel file has data rows below the header row.' . $headingsStr;
            }

            ActivityLog::log('consignors_imported', "Imported {$imported} consignors from Excel, {$skipped} skipped");

            return redirect()->route('admin.masters.consignors.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.masters.consignors.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        if (!auth()->user()->can('create consignors') && !auth()->user()->can('import consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        ActivityLog::log('consignor_template_downloaded', 'Downloaded consignor import template');
        return Excel::download(new ConsignorTemplateExport, 'consignor_import_template.xlsx');
    }

    public function transferForm(Consignor $consignor)
    {
        if (!auth()->user()->can('edit consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $companies = Company::where('status', 'active')->get();
        $branches = Branch::where('status', 'active')->get();
        return view('admin.masters.consignors.transfer', compact('consignor', 'companies', 'branches'));
    }

    public function transfer(Request $request, Consignor $consignor)
    {
        if (!auth()->user()->can('edit consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = $request->validate(['company_id' => 'required|exists:companies,id'])['company_id'];
        }

        $consignor->update($validated);
        ActivityLog::log('consignor_transferred', "Transferred consignor: {$consignor->name}", $consignor);

        return redirect()->route('admin.masters.consignors.index')->with('success', 'Consignor transferred successfully.');
    }

    public function trashed()
    {
        if (!auth()->user()->can('delete consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignors = Consignor::onlyTrashed()->with(['branch', 'company'])->paginate(15);
        return view('admin.masters.consignors.trashed', compact('consignors'));
    }

    public function restore($id)
    {
        if (!auth()->user()->can('delete consignors') && !auth()->user()->can('restore consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignor = Consignor::withTrashed()->findOrFail($id);
        $consignor->restore();
        ActivityLog::log('consignor_restored', "Restored consignor: {$consignor->name}");
        return redirect()->route('admin.masters.consignors.trashed')->with('success', 'Consignor restored successfully.');
    }

    public function forceDelete($id)
    {
        if (!auth()->user()->can('delete consignors') && !auth()->user()->can('force delete consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignor = Consignor::withTrashed()->findOrFail($id);
        ActivityLog::log('consignor_force_deleted', "Force deleted consignor: {$consignor->name}");
        $consignor->forceDelete();
        return redirect()->route('admin.masters.consignors.trashed')->with('success', 'Consignor permanently deleted.');
    }

    public function destroy(Consignor $consignor)
    {
        if (!auth()->user()->can('delete consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignor->delete();
        ActivityLog::log('consignor_deleted', "Deleted consignor: {$consignor->name}");
        return redirect()->route('admin.masters.consignors.index')->with('success', 'Consignor deleted successfully.');
    }

    public function toggleStatus(Consignor $consignor)
    {
        if (!auth()->user()->can('edit consignors') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignor->status = $consignor->status === 'active' ? 'inactive' : 'active';
        $consignor->save();
        ActivityLog::log('consignor_status_changed', "Changed status of consignor: {$consignor->name}", $consignor);
        return back()->with('success', 'Consignor status updated.');
    }

    public function search(Request $request)
    {
        if (!auth()->user()->can('view consignors') && !auth()->user()->isSuperAdmin()) {
            return response()->json([], 403);
        }

        $term = $request->term;
        $consignors = Consignor::where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('gstin', 'like', "%{$term}%")
            ->orWhere('vendor_code', 'like', "%{$term}%")
            ->limit(10)
            ->get();

        return response()->json($consignors);
    }

    public function quickStore(Request $request)
    {
        if (!auth()->user()->can('create consignors') && !auth()->user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

    $validated['company_id'] = $request->input('company_id', auth()->user()->company_id);
    $validated['branch_id'] = $request->input('branch_id', auth()->user()->branch_id);
    $validated['status'] = 'active';

        $consignor = Consignor::create($validated);

        ActivityLog::log('consignor_quick_created', "Quick created consignor: {$consignor->name}", $consignor);

        return response()->json([
            'id' => $consignor->id,
            'name' => $consignor->name,
            'phone' => $consignor->phone,
            'gstin' => $consignor->gstin,
            'address' => $consignor->address,
        ]);
    }
}
