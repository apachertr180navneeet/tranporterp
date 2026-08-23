<?php

namespace App\Http\Controllers\Admin\Transport;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Bulty;
use App\Models\Consignee;
use App\Models\Consignor;
use App\Models\City;
use App\Models\Company;
use App\Models\GstMaster;
use App\Models\Item;
use App\Models\Packaging;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BultyMail;

class BultyController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view bulties')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Bulty::with(['company', 'branch', 'consignor', 'consignee', 'originCity', 'destinationCity', 'bultyItems', 'vehicle', 'driver']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lr_no', 'like', "%{$search}%")
                    ->orWhereHas('consignor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('consignee', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($q) use ($search) {
                        $q->where('vehicle_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('lr_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('lr_date', '<=', $request->to_date);
        }

        $yearFilter = session('current_year');
        if ($yearFilter && $yearFilter !== 'all') {
            $query->whereYear('lr_date', $yearFilter);
        }

        $statusCounts = Bulty::selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $bulties = $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.transport.bulties.index', compact('bulties', 'statusCounts'));
    }

    public function nextLRNumber($branchId)
    {
        $year = date('Y');
        $branch = Branch::find($branchId);
        $prefix = ($branch && $branch->name) ? strtoupper(substr($branch->name, 0, 1)) : 'LR';

        $existing = Bulty::withTrashed()
            ->where('lr_no', 'like', "{$prefix}-{$year}-%")
            ->pluck('lr_no')
            ->map(function ($lr) {
                return (int) substr($lr, -4);
            })
            ->sort()
            ->values()
            ->toArray();

        $missing = [];
        $lastNumber = 0;

        if (!empty($existing)) {
            $maxExisting = max($existing);
            for ($i = 1; $i <= $maxExisting; $i++) {
                if (!in_array($i, $existing)) {
                    $missing[] = $prefix . '-' . $year . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
                }
            }
            $lastNumber = max($existing);
        }

        $nextNumber = $prefix . '-' . $year . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'missing' => $missing,
            'next' => $nextNumber,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->can('create bulties')) {
            abort(403, 'Unauthorized action.');
        }

        $companies = [];
        if (auth()->user()->isSuperAdmin()) {
            $companies = Company::where('status', 'active')->get();
        }

        $branches = Branch::where('status', 'active')->get();
        $consignors = Consignor::where('status', 'active')->get();
        $consignees = Consignee::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $cities = City::where('status', 'active')->orderBy('name')->get();
        $packagings = Packaging::where('status', 'active')->orderBy('name')->get();
        $units = Unit::where('status', 'active')->orderBy('name')->get();
        $branchId = auth()->user()->branch_id ?: ($branches->first()?->id);
        $nextBultyNo = $branchId ? Bulty::generateLRNumber($branchId) : '';

        return view('admin.transport.bulties.create', compact('branches', 'companies', 'consignors', 'consignees', 'suppliers', 'cities', 'packagings', 'units', 'nextBultyNo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'consignor_pod' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'consignee_pod' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $validated = $this->validatedData($request, [
            'company_id' => auth()->user()->isSuperAdmin() ? 'required|exists:companies,id' : 'nullable|exists:companies,id',
            'lr_no' => 'required|string|unique:bulties,lr_no',
        ]);

        if (!auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = auth()->user()->company_id;
            $validated['branch_id'] = auth()->user()->branch_id;
        }
        $validated['status'] = 'pending';

        if ($request->hasFile('consignor_pod')) {
            $path = $request->file('consignor_pod')->store('pods', 'uploads');
            $validated['consignor_pod'] = asset('uploads/' . $path);
        }
        if ($request->hasFile('consignee_pod')) {
            $path = $request->file('consignee_pod')->store('pods', 'uploads');
            $validated['consignee_pod'] = asset('uploads/' . $path);
        }
        if (!empty($validated['vehicle_id'])) {
            $vehicleInUse = Bulty::where('vehicle_id', $validated['vehicle_id'])
                ->whereHas('trip', fn($q) => $q->where('status', 'pending'))
                ->exists();
            if ($vehicleInUse) {
                return back()->withErrors(['vehicle_id' => 'This vehicle already has an open trip on another bilty. Close the trip first.'])->withInput();
            }
        }

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $bulty = DB::transaction(function () use ($validated, $items, $request) {
            $bulty = Bulty::create($validated);
            $this->syncItems($bulty, $items);
            $this->syncBultyDetail($bulty, $request);

            return $bulty;
        });

        ActivityLog::log('bulty_created', "Created bulty: {$bulty->lr_no}", $bulty);

        return redirect()->route('admin.transport.bulties.index')->with('success', 'Bulty created successfully. LR No: ' . $bulty->lr_no);
    }

    public function show(Bulty $bulty)
    {
        if (!auth()->user()->can('view bulties')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$bulty->share_token) {
            $bulty->share_token = (string) \Illuminate\Support\Str::uuid();
            $bulty->save();
        }

        $bulty->load([
            'branch',
            'consignor',
            'consignee',
            'vehicle',
            'driver',
            'originCity',
            'destinationCity',
            'bultyItems.item',
        ]);

        return view('admin.transport.bulties.show', compact('bulty'));
    }

    public function edit(Bulty $bulty)
    {
        if (!auth()->user()->can('edit bulties')) {
            abort(403, 'Unauthorized action.');
        }

        $companies = [];
        if (auth()->user()->isSuperAdmin()) {
            $companies = Company::where('status', 'active')->get();
        }

        $branches = Branch::where('status', 'active')->get();
        $consignors = Consignor::where('status', 'active')->get();
        $consignees = Consignee::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $cities = City::where('status', 'active')->orderBy('name')->get();
        $packagings = Packaging::where('status', 'active')->orderBy('name')->get();
        $units = Unit::where('status', 'active')->orderBy('name')->get();

        $bulty->load(['consignor', 'consignee', 'vehicle', 'driver', 'bultyItems.item', 'bultyDetail']);

        return view('admin.transport.bulties.edit', compact('bulty', 'companies', 'branches', 'consignors', 'consignees', 'suppliers', 'cities', 'packagings', 'units'));
    }

    public function update(Request $request, Bulty $bulty)
    {
        if (!auth()->user()->can('edit bulties')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'material_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'pod_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'consignor_pod' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'consignee_pod' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $validated = $this->validatedData($request, [
            'company_id' => auth()->user()->isSuperAdmin() ? 'required|exists:companies,id' : 'nullable|exists:companies,id',
            'lr_no' => 'required|string|unique:bulties,lr_no,' . $bulty->id,
        ]);

        if (!auth()->user()->isSuperAdmin()) {
            $validated['company_id'] = auth()->user()->company_id;
            $validated['branch_id'] = auth()->user()->branch_id;
        }

        if (!empty($validated['vehicle_id'])) {
            $vehicleInUse = Bulty::where('vehicle_id', $validated['vehicle_id'])
                ->where('id', '!=', $bulty->id)
                ->whereHas('trip', fn($q) => $q->where('status', 'pending'))
                ->exists();
            if ($vehicleInUse) {
                return back()->withErrors(['vehicle_id' => 'This vehicle already has an open trip on another bilty. Close the trip first.'])->withInput();
            }
        }

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        if ($request->hasFile('material_document')) {
            if ($bulty->material_document) {
                $relativePath = str_replace(asset('uploads/'), '', $bulty->material_document);
                Storage::disk('uploads')->delete($relativePath);
            }
            $path = $request->file('material_document')->store('material-documents', 'uploads');
            $validated['material_document'] = asset('uploads/' . $path);
            $validated['material_document_status'] = false;
        }

        if ($request->hasFile('pod_document')) {
            if ($bulty->pod_document) {
                $relativePath = str_replace(asset('uploads/'), '', $bulty->pod_document);
                Storage::disk('uploads')->delete($relativePath);
            }
            $path = $request->file('pod_document')->store('pods', 'uploads');
            $validated['pod_document'] = asset('uploads/' . $path);
            $validated['pod_document_status'] = false;
        }

        DB::transaction(function () use ($bulty, $validated, $items, $request) {
            $bulty->update($validated);

            if ($this->hasSubmittedItems($items)) {
                $this->syncItems($bulty, $items);
            }

            $this->syncBultyDetail($bulty, $request);
        });

        ActivityLog::log('bulty_updated', "Updated bulty: {$bulty->lr_no}", $bulty);

        return redirect()->route('admin.transport.bulties.show', $bulty)->with('success', 'Bulty updated successfully.');
    }

    private function authorizeBultyAction(Bulty $bulty, ?string $permission = null): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }
        if ($permission && !$user->can($permission)) {
            abort(403, 'Unauthorized action. You do not have the required permission.');
        }
    }

    public function approveDocument(Bulty $bulty)
    {
        $this->authorizeBultyAction($bulty, 'approve bulty documents');

        $bulty->material_document_status = true;
        $bulty->status = 'dispatched';
        $bulty->save();

        ActivityLog::log('document_approved', "Approved material document for bulty: {$bulty->lr_no}", $bulty);

        return back()->with('success', 'Material document approved successfully. Status updated to Dispatched.');
    }

    public function rejectDocument(Bulty $bulty)
    {
        $this->authorizeBultyAction($bulty, 'approve bulty documents');

        if ($bulty->material_document) {
            $relativePath = str_replace(asset('uploads/'), '', $bulty->material_document);
            Storage::disk('uploads')->delete($relativePath);
        }

        $bulty->material_document = null;
        $bulty->material_document_status = false;
        $bulty->save();

        ActivityLog::log('document_rejected', "Rejected material document for bulty: {$bulty->lr_no}", $bulty);

        return back()->with('success', 'Material document rejected. Driver can re-upload.');
    }

    public function approvePodDocument(Bulty $bulty)
    {
        $this->authorizeBultyAction($bulty, 'approve bulty pod');

        if (!$bulty->pod_document) {
            return back()->with('error', 'Cannot approve POD: no POD document uploaded.');
        }

        $bulty->pod_document_status = true;
        $bulty->status = 'delivered';
        $bulty->save();

        ActivityLog::log('pod_approved', "Approved POD for bulty: {$bulty->lr_no}", $bulty);

        return back()->with('success', 'POD approved successfully. Status updated to Delivered.');
    }

    public function rejectPodDocument(Bulty $bulty)
    {
        $this->authorizeBultyAction($bulty, 'approve bulty pod');

        if ($bulty->pod_document) {
            $relativePath = str_replace(asset('uploads/'), '', $bulty->pod_document);
            Storage::disk('uploads')->delete($relativePath);
        }

        $bulty->pod_document = null;
        $bulty->pod_document_status = false;
        $bulty->save();

        ActivityLog::log('pod_rejected', "Rejected POD for bulty: {$bulty->lr_no}", $bulty);

        return back()->with('success', 'POD rejected. Driver can re-upload.');
    }

    public function generatePdf(Bulty $bulty)
    {
        $bulty->load([
            'branch', 'consignor', 'consignee', 'vehicle', 'driver',
            'originCity', 'destinationCity', 'bultyItems', 'company', 'bultyDetail',
        ]);

        $pdf = Pdf::loadView('admin.transport.bulties.pdf', compact('bulty'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Bulty-{$bulty->lr_no}.pdf");
    }

    public function printBill(Bulty $bulty)
    {
        $bulty->load([
            'branch', 'consignor', 'consignee', 'vehicle', 'driver',
            'originCity', 'destinationCity', 'bultyItems', 'company', 'bultyDetail',
        ]);

        $freightTotal = floatval($bulty->freight_charges) - floatval($bulty->advance_amount);
        $gstTotal = floatval($bulty->gst_amount);
        $otherTotal = floatval($bulty->other_charges);
        $grandTotal = $freightTotal + $gstTotal + $otherTotal;
        $amountInWords = \App\Http\Controllers\Admin\Transport\BillingController::convertNumberToWords($grandTotal);

        $invoiceCompany = $bulty->company;
        $invoiceConsignor = (object) [
            'name' => $bulty->consignor->name ?? '-',
            'address' => $bulty->consignor->address ?? '-',
            'city' => $bulty->consignor->city ?? '',
            'state' => $bulty->consignor->state ?? '',
            'pincode' => $bulty->consignor->pincode ?? '',
            'vendor_code' => $bulty->consignor->vendor_code ?? ''
        ];
        $billNumber = $bulty->invoice_number ?? $bulty->lr_no;
        $vendorCode = $bulty->consignor->vendor_code ?? '';
        $bulties = collect([$bulty]);
        $gstPercentage = 0;

        $bankAccountNo = $invoiceCompany && $invoiceCompany->bank_account_no ? $invoiceCompany->bank_account_no : '';
        $bankIfsc = $invoiceCompany && $invoiceCompany->bank_ifsc ? $invoiceCompany->bank_ifsc : '';
        $bankHolder = $invoiceCompany && $invoiceCompany->bank_holder_name ? strtoupper($invoiceCompany->bank_holder_name) : '';
        $grnNewPage = false;
        
        $existingInvoice = null;

        return view('admin.transport.bulties.print_bill', compact(
            'invoiceCompany', 'existingInvoice', 'invoiceConsignor', 'billNumber', 'vendorCode',
            'bulties', 'gstPercentage', 'freightTotal', 'gstTotal', 'otherTotal', 'grandTotal',
            'amountInWords', 'bankAccountNo', 'bankIfsc', 'bankHolder', 'grnNewPage'
        ));
    }

    public function sendMail(Request $request, Bulty $bulty)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Log::info('Attempting to send Bilty PDF email', [
                'bulty_id' => $bulty->id,
                'email' => $request->email
            ]);
            
            Mail::to($request->email)->send(new BultyMail($bulty));

            Log::info('Bilty PDF emailed successfully', [
                'bulty_id' => $bulty->id,
                'lr_no' => $bulty->lr_no,
                'to' => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bilty PDF sent successfully to ' . $request->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send Bilty PDF email', [
                'bulty_id' => $bulty->id,
                'lr_no' => $bulty->lr_no,
                'to' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function reject(Bulty $bulty)
    {
        $this->authorizeBultyAction($bulty, 'cancel bulties');

        if (!in_array($bulty->status, ['pending', 'planned'])) {
            return back()->with('error', 'Only bilties with Pending or Planned status can be rejected.');
        }

        $lrNo = $bulty->lr_no;
        DB::transaction(function () use ($bulty) {
            $bulty->update(['status' => 'rejected']);
            $bulty->delete();
        });

        ActivityLog::log('bulty_rejected', "Rejected bulty: {$lrNo}");

        return redirect()->route('admin.transport.bulties.index')->with('success', 'Bulty rejected successfully.');
    }

    public function trashed()
    {
        if (!auth()->user()->can('restore bulties') && !auth()->user()->can('force delete bulties')) {
            abort(403, 'Unauthorized action.');
        }

        $bulties = Bulty::onlyTrashed()->with(['branch', 'consignor', 'consignee', 'originCity', 'destinationCity', 'bultyItems'])
            ->orderBy('deleted_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.transport.bulties.trashed', compact('bulties'));
    }

    public function restore($id)
    {
        $bulty = Bulty::withTrashed()->findOrFail($id);
        $this->authorizeBultyAction($bulty, 'restore bulties');

        $bulty = Bulty::withTrashed()->findOrFail($id);
        $bulty->restore();

        ActivityLog::log('bulty_restored', "Restored bulty: {$bulty->lr_no}");

        return redirect()->route('admin.transport.bulties.trashed')->with('success', 'Bulty restored successfully.');
    }

    public function forceDelete($id)
    {
        $bulty = Bulty::withTrashed()->findOrFail($id);
        $this->authorizeBultyAction($bulty, 'force delete bulties');

        $lrNo = $bulty->lr_no;

        $fileFields = ['material_document', 'consignor_pod', 'consignee_pod', 'pod_document'];
        foreach ($fileFields as $field) {
            if ($bulty->$field) {
                $relativePath = str_replace(asset('uploads/'), '', $bulty->$field);
                $fullPath = public_path('uploads/' . $relativePath);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }

        $bulty->forceDelete();

        ActivityLog::log('bulty_force_deleted', "Permanently deleted bulty: {$lrNo}");

        return redirect()->route('admin.transport.bulties.trashed')->with('success', 'Bulty permanently deleted.');
    }

    public function destroy(Bulty $bulty)
    {
        $this->authorizeBultyAction($bulty, 'delete bulties');

        if (!in_array($bulty->status, ['pending', 'planned'])) {
            return back()->with('error', 'Only bilties with Pending or Planned status can be deleted.');
        }

        $lrNo = $bulty->lr_no;
        $bulty->delete();

        ActivityLog::log('bulty_deleted', "Deleted bulty: {$lrNo}");

        return redirect()->route('admin.transport.bulties.index')->with('success', 'Bulty deleted successfully.');
    }

    private function validatedData(Request $request, array $extraRules = []): array
    {
        $request->merge([
            'consignor_id' => $request->filled('consignor_id') ? $request->consignor_id : null,
            'consignee_id' => $request->filled('consignee_id') ? $request->consignee_id : null,
        ]);

        return $request->validate(array_merge([
            'branch_id' => 'required|exists:branches,id',
            'lr_date' => "required|date|before_or_equal:9999-12-31",
            'from_city' => 'required|exists:cities,id',
            'to_city' => 'required|exists:cities,id',
            'consignor_id' => 'nullable|exists:consignors,id',
            'consignee_id' => 'nullable|exists:consignees,id',
            'declared_value' => 'nullable|numeric|min:0',
            'freight_charges' => 'nullable|numeric|min:0',
            'gst_type' => 'nullable|in:cgst_sgst,igst,none',
            'gst_master_id' => 'nullable|exists:gst_masters,id',
            'gst_amount' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'damage_amount' => 'nullable|numeric|min:0',
            'shortage_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:paid,topay,tobill',
            'mode' => 'nullable|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'remark' => 'nullable|string|max:1000',
            'bilty_commission' => 'nullable|numeric|min:0',
            'order_number' => 'nullable|string|max:255',
            'delivery_number' => 'nullable|string|max:255',
            'from_no' => 'nullable|string|max:255',
            'e_lr_no' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => "nullable|date|before_or_equal:9999-12-31",
            'eway_bill_no' => 'nullable|string|max:255',
            'generation_date' => "nullable|date|before_or_equal:9999-12-31",
            'expiry_date' => "nullable|date|before_or_equal:9999-12-31",
            'advance_amount' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.item_name' => 'nullable|string|max:255',
            'items.*.packaging_type' => 'nullable|string|max:255',
            'items.*.articles' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.freight_per_mt' => 'nullable|numeric|min:0',
            'items.*.amount' => 'nullable|numeric|min:0',
            // bulty_details fields
            'posting_date' => "nullable|date|before_or_equal:9999-12-31",
            'po_no' => 'nullable|string|max:255',
            'po_item' => 'nullable|string|max:255',
            'mat_doc' => 'nullable|string|max:255',
            'gate_entry_no' => 'nullable|string|max:255',
            'challan_no' => 'nullable|string|max:255',
            'challan_date' => "nullable|date|before_or_equal:9999-12-31",
            'transporter_code' => 'nullable|string|max:255',
            'transporter_name' => 'nullable|string|max:255',
            'gate_out_date' => "nullable|date|before_or_equal:9999-12-31",
            'invoice_doc' => 'nullable|string|max:255',
            'inv_date' => "nullable|date|before_or_equal:9999-12-31",
            'invoice_time' => 'nullable',
            'grn_no' => 'nullable|string|max:255',
            'grn_date' => "nullable|date|before_or_equal:9999-12-31",
            'grn_time' => 'nullable',
            'recd_qty' => 'nullable|numeric|min:0',
            'arrival_time' => 'nullable',
            'shortage_grn_no' => 'nullable|string|max:255',
            'shortage_grn_date' => "nullable|date|before_or_equal:9999-12-31",
            'short_qty' => 'nullable|numeric|min:0',
            'ul_date' => "nullable|date|before_or_equal:9999-12-31",
            'ul_rate' => 'nullable|numeric|min:0',
            'bag_ld' => 'nullable|integer|min:0',
            'bag_ul' => 'nullable|integer|min:0',
            'bag_short' => 'nullable|integer|min:0',
            'rate_mt' => 'nullable|numeric|min:0',
            'challan_qty' => 'nullable|numeric|min:0',
            'final_wgt' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description_services' => 'nullable|string|max:2000',
            'mn_no' => 'nullable|string|max:255',
            'bill_no' => 'nullable|string|max:255',
            'supplier_no' => 'nullable|string|max:255',
            'material_name' => 'nullable|string|max:255',
            'material_no' => 'nullable|string|max:255',
            'depot_name' => 'nullable|string|max:255',
            'billed_qty' => 'nullable|numeric|min:0',
        ], $extraRules));
    }

    private function syncItems(Bulty $bulty, array $items): void
    {
        $rows = collect($items)
            ->filter(fn ($item) => !empty($item['item_id']) || !empty($item['item_name']))
            ->map(function ($item) {
                $masterItem = null;

                if (!empty($item['item_id'])) {
                    $masterItem = Item::find($item['item_id']);
                } elseif (!empty($item['item_name'])) {
                    $masterItem = Item::where('name', $item['item_name'])->first();
                }

                $weight = (float) ($item['weight'] ?? 0);
                $freight = (float) ($item['freight_per_mt'] ?? 0);
                $amount = array_key_exists('amount', $item) ? (float) $item['amount'] : $weight * $freight;

                return [
                    'item_id' => $masterItem?->id,
                    'item_name' => $masterItem?->name ?? ($item['item_name'] ?? null),
                    'packaging_type' => $item['packaging_type'] ?? null,
                    'articles' => (int) ($item['articles'] ?? 0),
                    'weight' => $weight,
                    'unit' => $item['unit'] ?? null,
                    'freight_per_mt' => $freight,
                    'amount' => $amount,
                ];
            })
            ->values()
            ->all();

        $bulty->bultyItems()->delete();

        if (!empty($rows)) {
            $bulty->bultyItems()->createMany($rows);
        }
    }

    private function hasSubmittedItems(array $items): bool
    {
        return collect($items)->contains(fn ($item) => !empty($item['item_id']) || !empty($item['item_name']));
    }

    private function syncBultyDetail(Bulty $bulty, Request $request): void
    {
        $detailFields = [
            'posting_date', 'po_no', 'po_item', 'mat_doc', 'gate_entry_no', 'challan_no',
            'challan_date', 'transporter_code', 'transporter_name',
            'gate_out_date', 'invoice_doc', 'inv_date', 'invoice_time',
            'grn_no', 'grn_date', 'grn_time', 'recd_qty', 'arrival_time',
            'shortage_grn_no', 'shortage_grn_date', 'short_qty',
            'ul_date', 'ul_rate', 'bag_ld', 'bag_ul', 'bag_short', 'rate_mt', 'qty_mt',
            'challan_qty', 'final_wgt', 'supplier_id',
            'description_services',
            'mn_no', 'bill_no', 'supplier_no', 'material_name', 'material_no',
            'depot_name', 'billed_qty',
        ];

        $data = [];
        foreach ($detailFields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        if (!empty($data)) {
            // Map form field name to database column where they differ
            if (isset($data['inv_date'])) {
                $data['invoice_date'] = $data['inv_date'];
                unset($data['inv_date']);
            }

            // Auto-set qty_mt from sum of bultyItem weights only if user didn't provide one
            if (empty($data['qty_mt']) || floatval($data['qty_mt']) <= 0) {
                $bulty->load('bultyItems');
                $itemWeight = $bulty->bultyItems->pluck('weight')->map(fn($w) => (float)$w)->sum();
                if ($itemWeight > 0) {
                    $data['qty_mt'] = $itemWeight;
                }
            }

            $bulty->bultyDetail()->updateOrCreate(['bulty_id' => $bulty->id], $data);
        }
    }
}
