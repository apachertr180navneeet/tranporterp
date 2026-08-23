<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesLedgerController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view sales ledger') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = \App\Models\Invoice::with(['company', 'branch', 'consignor', 'billReceivings']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('consignor_id')) {
            $query->where('consignor_id', $request->consignor_id);
        }

        if ($request->filled('bill_number')) {
            $query->where(function ($q) use ($request) {
                $q->where('bill_number', 'LIKE', '%' . $request->bill_number . '%')
                  ->orWhere('invoice_no', 'LIKE', '%' . $request->bill_number . '%');
            });
        }

        if ($request->filled('bill_to')) {
            $query->where('consignor_name', 'LIKE', '%' . $request->bill_to . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('receiving_amount_status')) {
            if ($request->receiving_amount_status === 'paid') {
                $query->where('receiving_amount', '>', 0);
            } elseif ($request->receiving_amount_status === 'unpaid') {
                $query->where('receiving_amount', '<=', 0);
            }
        }

        if ($request->filled('receiving_gst_status')) {
            if ($request->receiving_gst_status === 'paid') {
                $query->where('receiving_gst', '>', 0);
            } elseif ($request->receiving_gst_status === 'unpaid') {
                $query->where('receiving_gst', '<=', 0);
            }
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(20);

        $companies = $user->isSuperAdmin() ? \App\Models\Company::where('status', 'active')->get() : collect();
        $branches = \App\Models\Branch::where('status', 'active')->when($companyId && $companyId !== 'all', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
        $consignors = \App\Models\Consignor::where('status', 'active')->get();

        // Get bills for dropdown
        $allBills = \App\Models\Invoice::when($companyId && $companyId !== 'all', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->orderBy('id', 'desc')->get(['id', 'bill_number', 'invoice_no', 'consignor_name', 'company_id', 'branch_id']);

        return view('admin.reports.sales_ledger', compact('invoices', 'companies', 'branches', 'consignors', 'allBills'));
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = \App\Models\Invoice::with(['company', 'branch', 'consignor']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('consignor_id')) {
            $query->where('consignor_id', $request->consignor_id);
        }

        if ($request->filled('bill_number')) {
            $query->where(function ($q) use ($request) {
                $q->where('bill_number', 'LIKE', '%' . $request->bill_number . '%')
                  ->orWhere('invoice_no', 'LIKE', '%' . $request->bill_number . '%');
            });
        }

        if ($request->filled('bill_to')) {
            $query->where('consignor_name', 'LIKE', '%' . $request->bill_to . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('receiving_amount_status')) {
            if ($request->receiving_amount_status === 'paid') {
                $query->where('receiving_amount', '>', 0);
            } elseif ($request->receiving_amount_status === 'unpaid') {
                $query->where('receiving_amount', '<=', 0);
            }
        }

        if ($request->filled('receiving_gst_status')) {
            if ($request->receiving_gst_status === 'paid') {
                $query->where('receiving_gst', '>', 0);
            } elseif ($request->receiving_gst_status === 'unpaid') {
                $query->where('receiving_gst', '<=', 0);
            }
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $headings = [
            'S.No',
            'Date',
            'Company',
            'Branch',
            'Bill No',
            'Bill To / Consigner',
            'Total Amt (w/o GST)',
            'GST',
            'TDS',
            'Deduction',
            'Net Payable',
            'Recv. Amt',
            'Recv. GST',
            'Total Recv.',
            'Outstanding',
            'Status'
        ];

        $data = [];
        foreach ($invoices as $index => $invoice) {
            $amountWithoutGst = $invoice->total_freight + $invoice->total_other;
            $netPayable = $invoice->net_payable_amount;
            $totalReceived = $invoice->total_received_amount;
            $outstanding = $invoice->outstanding_amount;

            $billNo = !empty($invoice->bill_number) ? $invoice->bill_number : ($invoice->invoice_no ?? ('INV-' . $invoice->id));

            $data[] = [
                $index + 1,
                $invoice->invoice_date?->format('d-m-Y') ?? '',
                $invoice->company?->name ?? 'N/A',
                $invoice->branch?->name ?? 'N/A',
                $billNo,
                $invoice->consignor_name,
                number_format($amountWithoutGst, 2, '.', ''),
                number_format($invoice->total_gst, 2, '.', ''),
                number_format($invoice->tds, 2, '.', ''),
                number_format($invoice->deduction, 2, '.', ''),
                number_format($netPayable, 2, '.', ''),
                number_format($invoice->receiving_amount, 2, '.', ''),
                number_format($invoice->receiving_gst, 2, '.', ''),
                number_format($totalReceived, 2, '.', ''),
                number_format($outstanding, 2, '.', ''),
                ucfirst($invoice->status)
            ];
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Reports\ReportExport($headings, $data, 'Sales Ledger Report'),
            'sales_ledger_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function tdsReport(Request $request)
    {
        if (!auth()->user()->can('view tds report') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = \App\Models\BillReceiving::with(['invoice.consignor', 'company', 'branch'])
            ->where('tds', '>', 0);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('consignor_id')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('consignor_id', $request->consignor_id);
            });
        }

        if ($request->filled('bill_number')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('bill_number', 'LIKE', '%' . $request->bill_number . '%')
                         ->orWhere('invoice_no', 'LIKE', '%' . $request->bill_number . '%');
                });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $receivings = $query->orderBy('date', 'desc')->paginate(20);

        $companies = $user->isSuperAdmin() ? \App\Models\Company::where('status', 'active')->get() : collect();
        $branches = \App\Models\Branch::where('status', 'active')->when($companyId && $companyId !== 'all', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
        $consignors = \App\Models\Consignor::where('status', 'active')->get();

        return view('admin.reports.tds_report', compact('receivings', 'companies', 'branches', 'consignors'));
    }

    public function history(Request $request)
    {
        if (!auth()->user()->can('view sales ledger') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->filled('company_id') ? $request->company_id : session('current_company_id'))
            : $user->company_id;

        $query = \App\Models\BillReceiving::with(['invoice.consignor', 'company', 'branch']);

        if ($companyId && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('consignor_id')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('consignor_id', $request->consignor_id);
            });
        }
        
        if ($request->filled('bill_number')) {
            $query->whereHas('invoice', function($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('bill_number', 'LIKE', '%' . $request->bill_number . '%')
                         ->orWhere('invoice_no', 'LIKE', '%' . $request->bill_number . '%');
                });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $receivings = $query->orderBy('date', 'desc')->paginate(20);

        $companies = $user->isSuperAdmin() ? \App\Models\Company::where('status', 'active')->get() : collect();
        $branches = \App\Models\Branch::where('status', 'active')->when($companyId && $companyId !== 'all', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
        $consignors = \App\Models\Consignor::where('status', 'active')->get();

        return view('admin.reports.bill_receiving_history', compact('receivings', 'companies', 'branches', 'consignors'));
    }

    public function getInvoiceDetails($id)
    {
        $invoice = \App\Models\Invoice::with(['company', 'branch'])->find($id);
        
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found']);
        }
        
        // Calculate base amount (before TDS) for auto TDS calculation
        $amountWithoutGst = $invoice->total_freight + $invoice->total_other;
        $grossBaseAmount = $amountWithoutGst + $invoice->total_gst;
        $baseAmount = $grossBaseAmount - $invoice->deduction;
        $autoTds = round($baseAmount * 1 / 100, 2); // 1% TDS
        
        return response()->json([
            'success' => true,
            'data' => [
                'bill_to' => $invoice->consignor_name,
                'company_name' => $invoice->company ? $invoice->company->name : '',
                'branch_name' => $invoice->branch ? $invoice->branch->name : '',
                'total_freight' => $invoice->total_freight,
                'total_other' => $invoice->total_other,
                'total_gst' => $invoice->total_gst,
                'existing_tds' => $invoice->tds,
                'existing_deduction' => $invoice->deduction,
                'gross_base_amount' => $grossBaseAmount,
                'base_amount' => $baseAmount,
                'auto_tds' => $autoTds,
                'net_payable_amount' => $invoice->net_payable_amount,
                'outstanding_amount' => $invoice->outstanding_amount,
            ]
        ]);
    }

    public function storeReceiving(Request $request)
    {
        if (!auth()->user()->can('edit sales ledger') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'date' => 'required|date',
            'receiving_amount' => 'nullable|numeric|min:0',
            'receiving_gst' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'tds' => 'nullable|numeric|min:0',
            'deduction_reason' => 'nullable|string|max:255',
        ]);

        $invoice = \App\Models\Invoice::findOrFail($request->invoice_id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $invoice) {
            $receivingAmount = $request->input('receiving_amount', 0);
            $receivingGst = $request->input('receiving_gst', 0);
            $deduction = $request->input('deduction', 0);
            $tds = $request->input('tds', 0);

            // Create history entry
            \App\Models\BillReceiving::create([
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'date' => $request->date,
                'receiving_amount' => $receivingAmount,
                'receiving_gst' => $receivingGst,
                'tds' => $tds,
                'deduction' => $deduction,
                'deduction_reason' => $request->deduction_reason,
            ]);

            // Synchronize and update invoice
            $invoice->receiving_amount = $invoice->billReceivings()->sum('receiving_amount');
            $invoice->receiving_gst = $invoice->billReceivings()->sum('receiving_gst');
            $invoice->tds = $invoice->billReceivings()->sum('tds');
            $invoice->deduction = $invoice->billReceivings()->sum('deduction');
            
            // Check if fully paid
            if ($invoice->outstanding_amount <= 0 && $invoice->net_payable_amount > 0) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'pending';
            }
            
            $invoice->save();
        });

        return back()->with('success', 'Amount received successfully.');
    }

    public function getReceivingDetails($id)
    {
        if (!auth()->user()->can('edit sales ledger') && !auth()->user()->can('view sales ledger') && !auth()->user()->can('view reports') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $receiving = \App\Models\BillReceiving::with(['invoice.company', 'invoice.branch'])->find($id);

        if (!$receiving) {
            return response()->json(['success' => false, 'message' => 'Receiving record not found']);
        }

        $invoice = $receiving->invoice;
        $amountWithoutGst = $invoice ? ($invoice->total_freight + $invoice->total_other) : 0;
        $grossBaseAmount = $amountWithoutGst + ($invoice ? $invoice->total_gst : 0);
        $billNo = $invoice ? (!empty($invoice->bill_number) ? $invoice->bill_number : ($invoice->invoice_no ?? ('INV-' . $invoice->id))) : '';

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $receiving->id,
                'invoice_id' => $receiving->invoice_id,
                'bill_number' => $billNo,
                'date' => $receiving->date?->format('Y-m-d'),
                'receiving_amount' => $receiving->receiving_amount,
                'receiving_gst' => $receiving->receiving_gst,
                'tds' => $receiving->tds,
                'deduction' => $receiving->deduction,
                'deduction_reason' => $receiving->deduction_reason ?? '',
                'bill_to' => $invoice ? $invoice->consignor_name : '',
                'company_name' => $receiving->company ? $receiving->company->name : ($invoice?->company?->name ?? ''),
                'branch_name' => $receiving->branch ? $receiving->branch->name : ($invoice?->branch?->name ?? ''),
                'gross_base_amount' => $grossBaseAmount,
                'net_payable_amount' => $invoice ? $invoice->net_payable_amount : 0,
                'outstanding_amount' => $invoice ? $invoice->outstanding_amount : 0,
            ]
        ]);
    }

    public function updateReceiving(Request $request, $id)
    {
        if (!auth()->user()->can('edit sales ledger') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'date' => 'required|date',
            'receiving_amount' => 'nullable|numeric|min:0',
            'receiving_gst' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'tds' => 'nullable|numeric|min:0',
            'deduction_reason' => 'nullable|string|max:255',
        ]);

        $receiving = \App\Models\BillReceiving::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $receiving) {
            $receiving->update([
                'date' => $request->date,
                'receiving_amount' => $request->input('receiving_amount', 0),
                'receiving_gst' => $request->input('receiving_gst', 0),
                'tds' => $request->input('tds', 0),
                'deduction' => $request->input('deduction', 0),
                'deduction_reason' => $request->deduction_reason,
            ]);

            $invoice = $receiving->invoice;
            if ($invoice) {
                $invoice->receiving_amount = $invoice->billReceivings()->sum('receiving_amount');
                $invoice->receiving_gst = $invoice->billReceivings()->sum('receiving_gst');
                $invoice->tds = $invoice->billReceivings()->sum('tds');
                $invoice->deduction = $invoice->billReceivings()->sum('deduction');

                if ($invoice->outstanding_amount <= 0 && $invoice->net_payable_amount > 0) {
                    $invoice->status = 'paid';
                } else {
                    $invoice->status = 'pending';
                }

                $invoice->save();
            }
        });

        return back()->with('success', 'Receiving entry updated successfully.');
    }

    public function deleteReceiving($id)
    {
        if (!auth()->user()->can('edit sales ledger') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $receiving = \App\Models\BillReceiving::findOrFail($id);
        $invoice = $receiving->invoice;

        \Illuminate\Support\Facades\DB::transaction(function () use ($receiving, $invoice) {
            $receiving->delete();

            if ($invoice) {
                $invoice->receiving_amount = $invoice->billReceivings()->sum('receiving_amount');
                $invoice->receiving_gst = $invoice->billReceivings()->sum('receiving_gst');
                $invoice->tds = $invoice->billReceivings()->sum('tds');
                $invoice->deduction = $invoice->billReceivings()->sum('deduction');

                if ($invoice->outstanding_amount <= 0 && $invoice->net_payable_amount > 0) {
                    $invoice->status = 'paid';
                } else {
                    $invoice->status = 'pending';
                }

                $invoice->save();
            }
        });

        return back()->with('success', 'Receiving entry deleted successfully.');
    }
}
