<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Http\Controllers\Controller;
use App\Models\Consignee;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ActivityLog;
use App\Imports\ConsigneeImport;
use App\Exports\ConsigneeTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsigneeController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = Consignee::with(['branch', 'company']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $consignees = $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        return view('admin.masters.consignees.index', compact('consignees'));
    }

    public function create()
    {
        if (!auth()->user()->can('create consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $branches = Branch::where('status', 'active')->get();
        return view('admin.masters.consignees.create', compact('branches'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:10', Rule::unique('consignees', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('consignees', 'email')],
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

        $consignee = Consignee::create($validated);
        ActivityLog::log('consignee_created', "Created consignee: {$consignee->name}", $consignee);

        return redirect()->route('admin.masters.consignees.index')->with('success', 'Consignee created successfully.');
    }

    public function edit(Consignee $consignee)
    {
        if (!auth()->user()->can('edit consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $branches = Branch::where('status', 'active')->get();
        return view('admin.masters.consignees.edit', compact('consignee', 'branches'));
    }

    public function update(Request $request, Consignee $consignee)
    {
        if (!auth()->user()->can('edit consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:10', Rule::unique('consignees', 'phone')->ignore($consignee->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('consignees', 'email')->ignore($consignee->id)],
            'gstin' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ]);

        if (auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = $request->validate(['company_id' => 'required|exists:companies,id'])['company_id'];
        }

        $consignee->update($validated);
        ActivityLog::log('consignee_updated', "Updated consignee: {$consignee->name}", $consignee);

        return redirect()->route('admin.masters.consignees.index')->with('success', 'Consignee updated successfully.');
    }

    public function import(Request $request)
    {
        if (!auth()->user()->can('create consignees') && !auth()->user()->can('import consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $user = auth()->user();
        $companyId = $request->company_id ?? ($user->isSuperAdmin() ? null : $user->company_id);
        $branchId = $request->branch_id ?? ($user->isSuperAdmin() ? null : $user->branch_id);

        $import = new ConsigneeImport($companyId, $branchId);
        try {
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $failures = $import->getFailures();
            $headings = $import->getHeadings();

            $message = "{$imported} consignee(s) imported successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped (missing data or duplicate phone/email).";
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

            ActivityLog::log('consignees_imported', "Imported {$imported} consignees from Excel, {$skipped} skipped");

            return redirect()->route('admin.masters.consignees.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.masters.consignees.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        if (!auth()->user()->can('create consignees') && !auth()->user()->can('import consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        ActivityLog::log('consignee_template_downloaded', 'Downloaded consignee import template');
        return Excel::download(new ConsigneeTemplateExport, 'consignee_import_template.xlsx');
    }

    public function transferForm(Consignee $consignee)
    {
        if (!auth()->user()->can('edit consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $companies = Company::where('status', 'active')->get();
        $branches = Branch::where('status', 'active')->get();
        return view('admin.masters.consignees.transfer', compact('consignee', 'companies', 'branches'));
    }

    public function transfer(Request $request, Consignee $consignee)
    {
        if (!auth()->user()->can('edit consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = $request->validate(['company_id' => 'required|exists:companies,id'])['company_id'];
        }

        $consignee->update($validated);
        ActivityLog::log('consignee_transferred', "Transferred consignee: {$consignee->name}", $consignee);

        return redirect()->route('admin.masters.consignees.index')->with('success', 'Consignee transferred successfully.');
    }

    public function trashed()
    {
        if (!auth()->user()->can('delete consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignees = Consignee::onlyTrashed()->with(['branch', 'company'])->paginate(15);
        return view('admin.masters.consignees.trashed', compact('consignees'));
    }

    public function restore($id)
    {
        if (!auth()->user()->can('delete consignees') && !auth()->user()->can('restore consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignee = Consignee::withTrashed()->findOrFail($id);
        $consignee->restore();
        ActivityLog::log('consignee_restored', "Restored consignee: {$consignee->name}");
        return redirect()->route('admin.masters.consignees.trashed')->with('success', 'Consignee restored successfully.');
    }

    public function forceDelete($id)
    {
        if (!auth()->user()->can('delete consignees') && !auth()->user()->can('force delete consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignee = Consignee::withTrashed()->findOrFail($id);
        ActivityLog::log('consignee_force_deleted', "Force deleted consignee: {$consignee->name}");
        $consignee->forceDelete();
        return redirect()->route('admin.masters.consignees.trashed')->with('success', 'Consignee permanently deleted.');
    }

    public function destroy(Consignee $consignee)
    {
        if (!auth()->user()->can('delete consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignee->delete();
        ActivityLog::log('consignee_deleted', "Deleted consignee: {$consignee->name}");
        return redirect()->route('admin.masters.consignees.index')->with('success', 'Consignee deleted successfully.');
    }

    public function toggleStatus(Consignee $consignee)
    {
        if (!auth()->user()->can('edit consignees') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $consignee->status = $consignee->status === 'active' ? 'inactive' : 'active';
        $consignee->save();
        ActivityLog::log('consignee_status_changed', "Changed status of consignee: {$consignee->name}", $consignee);
        return back()->with('success', 'Consignee status updated.');
    }

    public function search(Request $request)
    {
        if (!auth()->user()->can('view consignees') && !auth()->user()->isSuperAdmin()) {
            return response()->json([], 403);
        }

        $term = $request->term;
        $consignees = Consignee::where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('gstin', 'like', "%{$term}%")
            ->limit(10)
            ->get();

        return response()->json($consignees);
    }

    public function quickStore(Request $request)
    {
        if (!auth()->user()->can('create consignees') && !auth()->user()->isSuperAdmin()) {
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

        $consignee = Consignee::create($validated);

        ActivityLog::log('consignee_quick_created', "Quick created consignee: {$consignee->name}", $consignee);

        return response()->json([
            'id' => $consignee->id,
            'name' => $consignee->name,
            'phone' => $consignee->phone,
            'gstin' => $consignee->gstin,
            'address' => $consignee->address,
        ]);
    }
}
