<?php

namespace App\Http\Controllers\Admin\Transport;

use App\Http\Controllers\Controller;
use App\Models\Bulty;
use App\Models\BillFormat;
use App\Models\Invoice;
use App\Models\Consignor;
use App\Models\Consignee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvoiceExport;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $query = Bulty::with([
            'consignor', 'consignee', 'originCity', 'destinationCity', 'bultyDetail', 'branch', 'company'
        ])->where('bill_status', 'unbilled')
          ->whereNotIn('status', ['pending', 'planned']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lr_no', 'LIKE', "%{$search}%")
                  ->orWhereHas('bultyDetail', function ($qd) use ($search) {
                      $qd->where('po_no', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('consignor', function ($qc) use ($search) {
                      $qc->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('consignee', function ($qc) use ($search) {
                      $qc->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('consignor_id')) {
            $query->where('consignor_id', $request->consignor_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('lr_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('lr_date', '<=', $request->to_date);
        }

        $bulties = $query->orderBy('lr_date', 'desc')->paginate(15)->withQueryString();

        $consignors = \App\Models\Consignor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.transport.billing.index', compact('bulties', 'consignors'));
    }

    public function create(Request $request)
    {
        $ids = $request->query('ids');
        if (!$ids) {
            return redirect()->route('admin.transport.billing')->with('error', 'No LRs selected');
        }

        $ids = explode(',', $ids);
        $bulties = Bulty::with([
            'consignor', 'consignee', 'originCity', 'destinationCity', 'bultyDetail.supplier', 'branch', 'company',
            'vehicle', 'driver', 'bultyItems.item'
        ])->whereIn('id', $ids)->where('bill_status', 'unbilled')->get();

        if ($bulties->isEmpty()) {
            return redirect()->route('admin.transport.billing')->with('error', 'Selected LRs not found or already billed');
        }

        $totals = [
            'freight' => $bulties->sum('total_amount'),
            'gst' => $bulties->sum('gst_amount'),
            'other' => $bulties->sum('other_charges'),
            'grand' => $bulties->sum(function($b) {
                return floatval($b->total_amount) + floatval($b->gst_amount) + floatval($b->other_charges);
            }),
        ];

        $billFormats = BillFormat::with('gstMaster')->orderBy('format_name')->get(['id', 'format_name', 'template_type', 'visible_fields', 'field_order', 'grn_fields', 'grn_field_order', 'grn_new_page', 'gst_master_id']);

        $latestFreight = Invoice::where('invoice_type', 'freight')->orderBy('id', 'desc')->first();
        $nextFreightNum = $latestFreight ? (intval(last(explode('-', $latestFreight->invoice_no))) + 1) : 1;
        $nextFreightInvoiceNo = "INV-" . str_pad($nextFreightNum, 4, '0', STR_PAD_LEFT);

        $latestToll = Invoice::where('invoice_type', 'toll')->orderBy('id', 'desc')->first();
        $nextTollNum = $latestToll ? (intval(last(explode('-', $latestToll->invoice_no))) + 1) : 1;
        $nextTollInvoiceNo = "INV-TOLL-" . str_pad($nextTollNum, 4, '0', STR_PAD_LEFT);

        return view('admin.transport.billing.create', compact('bulties', 'totals', 'billFormats', 'nextFreightInvoiceNo', 'nextTollInvoiceNo'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'template_type' => 'nullable|string',
            'bill_format_id' => 'required_if:template_type,dynamic|nullable|exists:bill_formats,id',
            'invoice_type' => 'nullable|string|in:freight,toll',
        ]);

        $invoiceType = $request->input('invoice_type', 'freight');
        $ids = explode(',', $request->ids);

        $bulties = Bulty::with([
            'consignor', 'consignee', 'originCity', 'destinationCity', 'bultyDetail.supplier', 'company', 'trip.fastTagDetails', 'bultyItems'
        ])->whereIn('id', $ids);

        if ($invoiceType === 'toll') {
            $bulties = $bulties->whereNull('toll_invoice_id')->get();
        } else {
            $bulties = $bulties->where('bill_status', 'unbilled')->get();
        }

        if ($bulties->isEmpty()) {
            return redirect()->route('admin.transport.billing')->with('error', 'No unbilled LRs found to bill');
        }

        if ($request->filled('bill_number')) {
            $companyId = $bulties->first()->company_id;
            $request->validate([
                'bill_number' => [
                    Rule::unique('invoices', 'invoice_no')->where(function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    })
                ],
            ], [
                'bill_number.unique' => 'This bill number is already taken. Please use a different one.',
            ]);
        }

        $format = null;
        if (!in_array($request->template_type, ['nathdwara', 'gypsum']) && $request->filled('bill_format_id')) {
            $format = BillFormat::with('gstMaster')->findOrFail($request->bill_format_id);
        }

        $isMaiharUnloading = false;
        if ($format && $format->format_name && str_contains(strtoupper($format->format_name), 'MAIHAR') && str_contains(strtoupper($format->format_name), 'UNLOADING')) {
            $isMaiharUnloading = true;
        }

        $gstPercentage = ($format && $format->gstMaster && $format->gstMaster->percentage) ? floatval($format->gstMaster->percentage) : null;
        if ($invoiceType === 'toll' && $gstPercentage === null) {
            $gstPercentage = 18.00;
        }

        // For toll invoices, group LRs by route for separate bills
        if ($invoiceType === 'toll') {
            $routeGroups = $bulties->groupBy(function ($b) {
                return $b->from_city . '-' . $b->to_city;
            });
        } else {
            $routeGroups = collect([$bulties]);
        }

        $customBillNo = $request->input('bill_number');
        $invoices = collect();
        $isFirstGroup = true;

        foreach ($routeGroups as $routeKey => $groupBulties) {
            $totalFreight = 0;
            $totalGst = 0;
            $totalOther = 0;
            $totalDamage = 0;
            $totalShortage = 0;
            $totalAmount = 0;

            $templateType = $request->input('template_type');
            foreach ($groupBulties as $bulty) {
                $damage = floatval($bulty->damage_amount ?? 0);
                $shortage = floatval($bulty->shortage_amount ?? 0);

                if ($invoiceType === 'toll') {
                    $freight = $bulty->trip ? floatval($bulty->trip->fastTagDetails->sum('amount')) : 0;
                    $other = 0;
                    $damage = 0;
                    $shortage = 0;
                } elseif ($templateType === 'gypsum') {
                    $finalWgt = $bulty->bultyDetail ? floatval($bulty->bultyDetail->final_wgt) : 0;
                    $rateMt = $bulty->bultyDetail ? floatval($bulty->bultyDetail->rate_mt) : 0;
                    $freight = $finalWgt * $rateMt;
                    $other = floatval($bulty->other_charges);

                    if ($request->filled('nathdwara_rate') && floatval($request->nathdwara_rate) > 0) {
                        $freight = $finalWgt * floatval($request->nathdwara_rate);
                    }
                } elseif ($isMaiharUnloading) {
                    $weight = $bulty->bultyItems ? floatval($bulty->bultyItems->sum('weight')) : 0;
                    $ulRate = $bulty->bultyDetail ? floatval($bulty->bultyDetail->ul_rate) : 0;
                    $freight = $weight * $ulRate;
                    $other = floatval($bulty->other_charges);
                } else {
                    $freight = floatval($bulty->freight_charges) - floatval($bulty->advance_amount);
                    $other = floatval($bulty->other_charges);
                }

                $gst = floatval($bulty->gst_amount);

                if ($gstPercentage !== null) {
                    $gst = $freight * ($gstPercentage / 100);
                }

                $total = $freight + $other - $damage - $shortage + $gst;

                $totalFreight += $freight;
                $totalGst += $gst;
                $totalOther += $other;
                $totalDamage += $damage;
                $totalShortage += $shortage;
                $totalAmount += $total;
            }

            // Generate Invoice Number
            if ($customBillNo && $isFirstGroup) {
                $invoiceNo = $customBillNo;
            } else {
                $prefix = $invoiceType === 'toll' ? 'INV-TOLL' : 'INV';
                $invoiceNo = DB::transaction(function () use ($prefix) {
                    $latest = Invoice::where('invoice_no', 'like', "{$prefix}-%")
                        ->orderBy('id', 'desc')
                        ->lockForUpdate()
                        ->first();
                    $nextNum = $latest ? (intval(explode('-', $latest->invoice_no)[count(explode('-', $latest->invoice_no)) - 1]) + 1) : 1;
                    return "{$prefix}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                });
            }

            $firstBulty = $groupBulties->first();
            $consignorId = $firstBulty->consignor_id;
            $consignorName = $firstBulty->consignor ? $firstBulty->consignor->name : null;
            $fromCityName = $firstBulty->originCity ? $firstBulty->originCity->name : null;
            $toCityName = $firstBulty->destinationCity ? $firstBulty->destinationCity->name : null;
            $companyName = $request->company_name ?? ($firstBulty->company ? $firstBulty->company->name : null);

            $companyState = $firstBulty->company ? ($firstBulty->company->state ?? null) : null;
            if (!$companyState && $firstBulty->originCity) {
                $companyState = $firstBulty->originCity->state ?? 'RAJASTHAN';
            }
            $placeOfSupply = $request->input('custom_place_of_supply') ?: ($firstBulty->destinationCity ? ($firstBulty->destinationCity->state ?? 'RAJASTHAN') : 'RAJASTHAN');
            $isSameState = self::isSameGstState($companyState, $placeOfSupply);

            $defaultGstType = $isSameState ? 'CGST_SGST' : 'IGST';
            $gstType = $request->input('gst_type') ?: $defaultGstType;
            if ($request->filled('total_gst') && floatval($request->total_gst) > 0) {
                $totalGst = floatval($request->total_gst);
            } elseif ($gstPercentage !== null && $gstPercentage > 0 && $totalFreight > 0) {
                $totalGst = round($totalFreight * ($gstPercentage / 100), 2);
            }

            if ($gstType === 'IGST') {
                $igstAmount = ($request->filled('igst_amount') && floatval($request->igst_amount) > 0) ? floatval($request->igst_amount) : $totalGst;
                $cgstAmount = 0.00;
                $sgstAmount = 0.00;
            } else {
                $cgstAmount = ($request->filled('cgst_amount') && floatval($request->cgst_amount) > 0) ? floatval($request->cgst_amount) : round($totalGst / 2, 2);
                $sgstAmount = ($request->filled('sgst_amount') && floatval($request->sgst_amount) > 0) ? floatval($request->sgst_amount) : round($totalGst / 2, 2);
                $igstAmount = 0.00;
            }

            if ($request->filled('total_amount') && floatval($request->total_amount) > 0) {
                $totalAmount = floatval($request->total_amount);
            } else {
                $totalAmount = $totalFreight + $totalOther - $totalDamage - $totalShortage + $totalGst;
            }
            $amountInWords = self::convertNumberToWords($totalAmount);

            $invoice = DB::transaction(function () use ($firstBulty, $consignorId, $consignorName, $fromCityName, $toCityName, $totalFreight, $totalGst, $totalOther, $invoiceNo, $invoiceType, $totalAmount, $amountInWords, $format, $groupBulties, $request, $isMaiharUnloading, $companyName, $gstType, $cgstAmount, $sgstAmount, $igstAmount) {
                $invoice = Invoice::create([
                    'company_id' => $firstBulty->company_id,
                    'branch_id' => $firstBulty->branch_id,
                    'consignor_id' => $consignorId,
                    'consignor_name' => $consignorName,
                    'company_name' => $companyName,
                    'billing_address' => $request->billing_address,
                    'custom_hsn_code' => $request->custom_hsn_code,
                    'custom_place_of_supply' => $request->custom_place_of_supply,
                    'from_city_name' => $fromCityName,
                    'to_city_name' => $toCityName,
                    'total_freight' => $totalFreight,
                    'total_gst' => $totalGst,
                    'total_other' => $totalOther,
                    'invoice_no' => $invoiceNo,
                    'bill_number' => $invoiceNo,
                    'template_type' => in_array($request->template_type, ['nathdwara', 'gypsum']) ? $request->template_type : ($isMaiharUnloading ? 'maihar_unloading' : ($format->template_type ?? 'standard')),
                    'invoice_type' => $invoiceType,
                    'invoice_date' => now(),
                    'total_amount' => $totalAmount,
                    'amount_in_words' => $amountInWords,
                    'visible_fields' => $format ? (!empty($format->field_order) ? $format->field_order : $format->visible_fields) : null,
                    'grn_fields' => $format ? (!empty($format->grn_field_order) ? $format->grn_field_order : $format->grn_fields) : null,
                    'grn_new_page' => $format ? $format->grn_new_page : false,
                    'status' => 'pending',
                    'user_id' => auth()->id(),
                    'gst_master_id' => $format ? $format->gst_master_id : null,
                    'mn_number' => $request->mn_number,
                    'no_of_lrs' => $request->no_of_lrs,
                    'state_vendor_code' => $request->state_vendor_code,
                    'vendor_code' => $request->vendor_code,
                    'vendor_name' => $request->vendor_name,
                    'epod_status' => $request->epod_status ?? 'N',
                    'description' => $request->nathdwara_description,
                    'custom_rate' => $request->nathdwara_rate,
                    'gst_type' => $gstType,
                    'cgst_amount' => $cgstAmount,
                    'sgst_amount' => $sgstAmount,
                    'igst_amount' => $igstAmount,
                    'rcm_payable' => $request->has('rcm_payable') ? (int)$request->rcm_payable : 1,
                ]);

                foreach ($groupBulties as $bulty) {
                    // Auto-set qty_mt from sum of bultyItem weights
                    $itemWeight = $bulty->bultyItems ? $bulty->bultyItems->pluck('weight')->map(fn($w) => (float)$w)->sum() : 0;
                    if ($itemWeight > 0 && $bulty->bultyDetail) {
                        $bulty->bultyDetail->update(['qty_mt' => $itemWeight]);
                    } elseif ($itemWeight > 0) {
                        $bulty->bultyDetail()->create(['bulty_id' => $bulty->id, 'qty_mt' => $itemWeight]);
                    }

                    if ($invoiceType === 'toll') {
                        if ($bulty->toll_invoice_id !== null) continue;
                        $bulty->update([
                            'toll_invoice_id' => $invoice->id,
                        ]);
                    } else {
                        if ($bulty->bill_status !== 'unbilled') continue;
                        $bulty->update([
                            'bill_status' => 'billed',
                            'invoice_id' => $invoice->id,
                        ]);
                    }
                }

                return $invoice;
            });

            if ($invoiceType === 'toll') {
                $this->saveTollDetails($invoice);
            }

            $invoices->push($invoice);
            $isFirstGroup = false;
        }

        $action = $request->input('action', 'generate_print');
        $totalLrs = $bulties->count();
        $invoiceCount = $invoices->count();

        if ($invoiceCount === 1) {
            $singleInvoice = $invoices->first();
            if ($action === 'save') {
                return redirect()->route('admin.transport.toll-bills.index')
                    ->with('success', 'Invoice ' . $singleInvoice->invoice_no . ' saved successfully for ' . $totalLrs . ' LR(s)');
            }
            return redirect()->route('admin.transport.invoices.show', $singleInvoice->id)
                ->with('success', 'Invoice ' . $singleInvoice->invoice_no . ' generated successfully for ' . $totalLrs . ' LR(s)');
        }

        return redirect()->route('admin.transport.toll-bills.index')
            ->with('success', $invoiceCount . ' invoice(s) generated successfully for ' . $totalLrs . ' LR(s)');
    }

    public function invoiceHistory(Request $request)
    {
        $query = Invoice::with(['consignor', 'company', 'user', 'freightBulties.consignee'])->where('invoice_type', 'freight');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('consignor_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('consignor', function ($qc) use ($search) {
                      $qc->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('freightBulties.consignee', function ($qc) use ($search) {
                      $qc->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('consignor_id')) {
            $query->where('consignor_id', $request->consignor_id);
        }

        if ($request->filled('consignee_id')) {
            $query->whereHas('freightBulties', function ($qb) use ($request) {
                $qb->where('consignee_id', $request->consignee_id);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(15)->withQueryString();
        $consignors = Consignor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);
        $consignees = Consignee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.transport.billing.invoices.index', compact('invoices', 'consignors', 'consignees'));
    }

    public function tollBills(Request $request)
    {
        $query = Invoice::with(['consignor', 'company', 'user', 'tollBulties.consignee'])
            ->where('invoice_type', 'toll');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('consignor_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('consignor', function ($qc) use ($search) {
                      $qc->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('tollBulties.consignee', function ($qc) use ($search) {
                      $qc->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('consignor_id')) {
            $query->where('consignor_id', $request->consignor_id);
        }

        if ($request->filled('consignee_id')) {
            $query->whereHas('tollBulties', function ($qb) use ($request) {
                $qb->where('consignee_id', $request->consignee_id);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(15)->withQueryString();
        $consignors = Consignor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);
        $consignees = Consignee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.transport.billing.invoices.toll-bills', compact('invoices', 'consignors', 'consignees'));
    }

    public function billGenerate(Invoice $invoice)
    {
        $bultyRelation = $invoice->invoice_type === 'toll' ? 'tollBulties' : 'freightBulties';
        $invoice->load([$bultyRelation => function($q) {
            $q->with([
                'consignor', 'consignee', 'originCity', 'destinationCity',
                'trip.fastTagDetails'
            ]);
        }]);
        $invoice->setRelation('bulties', $invoice->{$bultyRelation});

        return view('admin.transport.billing.invoices.bill-generate', compact('invoice'));
    }

    public function showInvoice(Invoice $invoice)
    {
        $bultyRelation = $invoice->invoice_type === 'toll' ? 'tollBulties' : 'freightBulties';
        if (!method_exists($invoice, $bultyRelation)) {
            abort(404, "Invalid invoice type: {$invoice->invoice_type}");
        }
        $invoice->load([
            'consignor', 
            'company', 
            'gstMaster',
            'tollDetails',
            $bultyRelation => function($q) {
                $q->with([
                    'consignor', 'consignee', 'originCity', 'destinationCity', 
                    'bultyDetail.supplier', 'branch', 'company', 'vehicle', 'driver', 'bultyItems.item',
                    'trip.fastTagDetails', 'invoice'
                ]);
            }
        ]);
        $invoice->setRelation('bulties', $invoice->{$bultyRelation});

        if ($invoice->invoice_type === 'toll') {
            return view('admin.transport.billing.invoices.toll-print', compact('invoice'));
        }

        if ($invoice->template_type === 'nathdwara') {
            return view('admin.transport.billing.invoices.show_nathdwara', compact('invoice'));
        } elseif ($invoice->template_type === 'gypsum') {
            return view('admin.transport.billing.invoices.show_gypsum', compact('invoice'));
        }

        return view('admin.transport.billing.invoices.show', compact('invoice'));
    }

    public function tollPrint(Invoice $invoice)
    {
        $bultyRelation = $invoice->invoice_type === 'toll' ? 'tollBulties' : 'freightBulties';
        $invoice->load([
            'consignor', 
            'company', 
            'gstMaster',
            'tollDetails',
            $bultyRelation => function($q) {
                $q->with([
                    'consignor', 'consignee', 'originCity', 'destinationCity', 
                    'bultyDetail', 'branch', 'company', 'vehicle', 'driver', 
                    'trip.fastTagDetails', 'invoice'
                ]);
            }
        ]);
        $invoice->setRelation('bulties', $invoice->{$bultyRelation});

        return view('admin.transport.billing.invoices.toll-print', compact('invoice'));
    }

    public function exportInvoiceExcel(Invoice $invoice)
    {
        $bultyRelation = $invoice->invoice_type === 'toll' ? 'tollBulties' : 'freightBulties';
        $invoice->load([
            'consignor', 
            'company', 
            'gstMaster',
            'tollDetails',
            $bultyRelation => function($q) {
                $q->with([
                    'consignor', 'consignee', 'originCity', 'destinationCity', 
                    'bultyDetail.supplier', 'branch', 'company', 'vehicle', 'driver', 'bultyItems.item',
                    'trip.fastTagDetails', 'invoice'
                ]);
            }
        ]);
        $invoice->setRelation('bulties', $invoice->{$bultyRelation});

        $fileName = 'invoice_' . $invoice->invoice_no . '.xlsx';
        return Excel::download(new InvoiceExport($invoice), $fileName);
    }

    private function saveTollDetails(Invoice $invoice)
    {
        $invoice->load([
            'bulties' => function($q) {
                $q->with(['trip.fastTagDetails']);
            }
        ]);

        $invoice->tollDetails()->delete();

        $tollLocations = collect();
        foreach ($invoice->bulties as $bulty) {
            if ($bulty->trip) {
                foreach ($bulty->trip->fastTagDetails as $ft) {
                    if ($ft->location) {
                        $tollLocations->push(strtoupper(trim($ft->location)));
                    }
                }
            }
        }
        $tollLocations = $tollLocations->unique()->values();

        foreach ($invoice->bulties as $bulty) {
            $ftDetails = $bulty->trip ? $bulty->trip->fastTagDetails : collect();

            foreach ($tollLocations as $location) {
                $match = $ftDetails->first(function($ft) use ($location) {
                    return strtoupper(trim($ft->location)) === $location;
                });

                if ($match && (floatval($match->one_way) > 0 || floatval($match->return) > 0)) {
                    $invoice->tollDetails()->create([
                        'builty_id' => $bulty->id,
                        'location' => $location,
                        'one_way' => floatval($match->one_way),
                        'return_amount' => floatval($match->return),
                    ]);
                }
            }
        }
    }

    private function resolveTollInvoiceMetaData(Invoice $sourceInvoice, Request $request): array
    {
        $resolveValue = function (string $field) use ($sourceInvoice, $request) {
            if ($request->exists($field)) {
                $value = $request->input($field);

                if (is_string($value)) {
                    $value = trim($value);
                }

                if ($value !== null && $value !== '' && $value !== []) {
                    return $value;
                }
            }

            return data_get($sourceInvoice, $field);
        };

        return [
            'custom_hsn_code' => $resolveValue('custom_hsn_code'),
            'company_name' => $resolveValue('company_name'),
            'billing_address' => $resolveValue('billing_address'),
            'custom_place_of_supply' => $resolveValue('custom_place_of_supply'),
            'custom_district' => $resolveValue('custom_district'),
            'custom_state' => $resolveValue('custom_state'),
            'custom_state_code' => $resolveValue('custom_state_code'),
            'custom_gstn' => $resolveValue('custom_gstn'),
            'custom_pan_no' => $resolveValue('custom_pan_no'),
            'state_vendor_code' => $resolveValue('state_vendor_code'),
            'vendor_code' => $resolveValue('vendor_code'),
            'vendor_name' => $resolveValue('vendor_name'),
            'consignor_name' => $resolveValue('consignor_name'),
            'epod_status' => $resolveValue('epod_status'),
            'mn_number' => $resolveValue('mn_number'),
            'no_of_lrs' => $resolveValue('no_of_lrs'),
        ];
    }

    public function saveTollInvoice(Request $request, Invoice $invoice)
    {
        if ($request->filled('bill_number') && $invoice->invoice_type !== 'toll') {
            $request->validate([
                'bill_number' => [
                    Rule::unique('invoices', 'invoice_no')->where(function ($query) use ($invoice) {
                        return $query->where('company_id', $invoice->company_id);
                    })
                ],
            ], [
                'bill_number.unique' => 'This bill number is already taken. Please use a different one.',
            ]);
        }

        $targetInvoice = $invoice;
        $metaData = $this->resolveTollInvoiceMetaData($invoice, $request);
        
        if ($invoice->invoice_type !== 'toll') {
            $grandTollSum = 0;
            foreach ($invoice->bulties as $bulty) {
                if ($bulty->trip) {
                    $grandTollSum += floatval($bulty->trip->fastTagDetails->sum('amount'));
                }
            }
            
            $gstRate = $invoice->gstMaster ? floatval($invoice->gstMaster->percentage) : 18.00;
            $calculatedGst = $grandTollSum * ($gstRate / 100);
            $grandTotal = $grandTollSum + $calculatedGst;
            $amountInWords = self::convertNumberToWords($grandTotal);
            
            $customBillNo = $request->bill_number;
            $prefix = 'INV-TOLL';
            $invoiceNo = $customBillNo ?: DB::transaction(function () use ($prefix) {
                $latest = Invoice::where('invoice_no', 'like', "{$prefix}-%")
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();
                $nextNum = $latest ? (intval(explode('-', $latest->invoice_no)[count(explode('-', $latest->invoice_no)) - 1]) + 1) : 1;
                return "{$prefix}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            });

            $targetInvoice = Invoice::create([
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'consignor_id' => $invoice->consignor_id,
                'consignor_name' => $metaData['consignor_name'],
                'company_name' => $metaData['company_name'],
                'billing_address' => $metaData['billing_address'],
                'custom_hsn_code' => $metaData['custom_hsn_code'],
                'custom_place_of_supply' => $metaData['custom_place_of_supply'],
                'custom_district' => $metaData['custom_district'],
                'custom_state' => $metaData['custom_state'],
                'custom_state_code' => $metaData['custom_state_code'],
                'custom_gstn' => $metaData['custom_gstn'],
                'custom_pan_no' => $metaData['custom_pan_no'],
                'from_city_name' => $invoice->from_city_name,
                'to_city_name' => $invoice->to_city_name,
                'total_freight' => $grandTollSum,
                'total_gst' => $calculatedGst,
                'total_other' => 0,
                'invoice_no' => $invoiceNo,
                'bill_number' => $request->bill_number ?? $invoiceNo,
                'invoice_type' => 'toll',
                'invoice_date' => now(),
                'total_amount' => $grandTotal,
                'amount_in_words' => $amountInWords,
                'visible_fields' => $invoice->visible_fields,
                'grn_fields' => $invoice->grn_fields,
                'grn_new_page' => $invoice->grn_new_page,
                'status' => 'pending',
                'user_id' => auth()->id(),
                'gst_master_id' => $invoice->gst_master_id,
                'mn_number' => $metaData['mn_number'],
                'no_of_lrs' => $metaData['no_of_lrs'],
                'state_vendor_code' => $metaData['state_vendor_code'],
                'vendor_code' => $metaData['vendor_code'],
                'vendor_name' => $metaData['vendor_name'],
                'epod_status' => $metaData['epod_status'],
            ]);
            
            foreach ($invoice->bulties as $bulty) {
                $bulty->update([
                    'toll_invoice_id' => $targetInvoice->id,
                ]);
            }
        } else {
            $updateData = ['status' => 'pending'];
            if ($request->has('bill_number')) {
                $updateData['bill_number'] = $request->bill_number;
            }
            $updateData['mn_number'] = $metaData['mn_number'];
            $updateData['no_of_lrs'] = $metaData['no_of_lrs'];
            $updateData['company_name'] = $metaData['company_name'];
            $updateData['billing_address'] = $metaData['billing_address'];
            $updateData['custom_hsn_code'] = $metaData['custom_hsn_code'];
            $updateData['custom_place_of_supply'] = $metaData['custom_place_of_supply'];
            $updateData['custom_district'] = $metaData['custom_district'];
            $updateData['custom_state'] = $metaData['custom_state'];
            $updateData['custom_state_code'] = $metaData['custom_state_code'];
            $updateData['custom_gstn'] = $metaData['custom_gstn'];
            $updateData['custom_pan_no'] = $metaData['custom_pan_no'];
            $updateData['state_vendor_code'] = $metaData['state_vendor_code'];
            $updateData['vendor_code'] = $metaData['vendor_code'];
            $updateData['vendor_name'] = $metaData['vendor_name'];
            $updateData['consignor_name'] = $metaData['consignor_name'];
            $updateData['epod_status'] = $metaData['epod_status'];
            $targetInvoice->update($updateData);
        }

        $this->saveTollDetails($targetInvoice);

        if ($request->has('bulty_modes') && is_array($request->bulty_modes)) {
            foreach ($request->bulty_modes as $bultyId => $mode) {
                Bulty::where('id', $bultyId)->where('toll_invoice_id', $targetInvoice->id)->update(['mode' => $mode]);
            }
        }

        $targetInvoice->load(['tollDetails', 'bulties.trip.fastTagDetails', 'gstMaster', 'company']);
        $grandTollSum = 0;
        if ($targetInvoice->tollDetails->isNotEmpty()) {
            $grandTollSum = floatval($targetInvoice->tollDetails->sum(function($d) {
                return floatval($d->one_way) + floatval($d->return_amount);
            }));
        } else {
            foreach ($targetInvoice->bulties as $bulty) {
                if ($bulty->trip) {
                    $grandTollSum += floatval($bulty->trip->fastTagDetails->sum('amount'));
                }
            }
        }

        $gstRate = $targetInvoice->gstMaster ? floatval($targetInvoice->gstMaster->percentage) : 18.00;
        $calculatedGst = $grandTollSum * ($gstRate / 100);
        $grandTotal = $grandTollSum + $calculatedGst;
        $amountInWords = self::convertNumberToWords($grandTotal);

        $firstBulty = $targetInvoice->bulties->first();
        $originState = $targetInvoice->company && $targetInvoice->company->state ? $targetInvoice->company->state : ($firstBulty && $firstBulty->originCity ? ($firstBulty->originCity->state ?? 'RAJASTHAN') : 'RAJASTHAN');
        $placeOfSupply = $targetInvoice->custom_place_of_supply ?: ($firstBulty && $firstBulty->destinationCity ? ($firstBulty->destinationCity->state ?? 'RAJASTHAN') : 'RAJASTHAN');
        $isSameState = self::isSameGstState($originState, $placeOfSupply);

        $cgstVal = $isSameState ? ($calculatedGst / 2) : 0;
        $sgstVal = $isSameState ? ($calculatedGst / 2) : 0;
        $igstVal = !$isSameState ? $calculatedGst : 0;

        $targetInvoice->update([
            'total_freight' => $grandTollSum,
            'total_gst' => $calculatedGst,
            'cgst_amount' => $cgstVal,
            'sgst_amount' => $sgstVal,
            'igst_amount' => $igstVal,
            'total_amount' => $grandTotal,
            'amount_in_words' => $amountInWords,
        ]);

        return redirect()->route('admin.transport.toll-bills.index')
            ->with('success', 'Toll Bill ' . $targetInvoice->invoice_no . ' saved successfully.');
    }

    /**
     * Determine whether origin state and place of supply match (same GST state)
     *
     * @param string|null $originState
     * @param string|null $placeOfSupply
     * @return bool
     */
    public static function isSameGstState($originState, $placeOfSupply)
    {
        if (empty($originState) || empty($placeOfSupply)) {
            return true;
        }

        $cleanOrigin = strtoupper(trim(preg_replace('/[^A-Z]/i', '', preg_replace('/^\d+[-_\s]*/', '', (string)$originState))));
        $cleanSupply = strtoupper(trim(preg_replace('/[^A-Z]/i', '', preg_replace('/^\d+[-_\s]*/', '', (string)$placeOfSupply))));

        if (empty($cleanOrigin) || empty($cleanSupply)) {
            return true;
        }

        return $cleanOrigin === $cleanSupply;
    }

    public static function convertNumberToWords($amount)
    {
        $amount = round($amount, 2);
        $rupees = floor($amount);
        $paise = round(($amount - $rupees) * 100);

        $rupeesStr = self::numberToWordsHelper($rupees);
        $result = $rupeesStr ? $rupeesStr . ' Rupees' : 'Zero Rupees';

        if ($paise > 0) {
            $paiseStr = self::numberToWordsHelper($paise);
            $result .= ' and ' . $paiseStr . ' Paise';
        }

        return strtoupper($result . ' Only.');
    }

    private static function numberToWordsHelper($num)
    {
        $ones = array(
            0 => "", 1 => "one", 2 => "two", 3 => "three", 4 => "four", 5 => "five", 6 => "six", 7 => "seven", 8 => "eight", 9 => "nine",
            10 => "ten", 11 => "eleven", 12 => "twelve", 13 => "thirteen", 14 => "fourteen", 15 => "fifteen", 16 => "sixteen", 17 => "seventeen", 18 => "eighteen", 19 => "nineteen"
        );
        $tens = array(
            0 => "", 1 => "", 2 => "twenty", 3 => "thirty", 4 => "forty", 5 => "fifty", 6 => "sixty", 7 => "seventy", 8 => "eighty", 9 => "ninety"
        );

        if ($num == 0) {
            return "";
        }

        $str = "";
        if ($num >= 10000000) {
            $str .= self::numberToWordsHelper(floor($num / 10000000)) . " crore ";
            $num %= 10000000;
        }
        if ($num >= 100000) {
            $str .= self::numberToWordsHelper(floor($num / 100000)) . " lakh ";
            $num %= 100000;
        }
        if ($num >= 1000) {
            $str .= self::numberToWordsHelper(floor($num / 1000)) . " thousand ";
            $num %= 1000;
        }
        if ($num >= 100) {
            $str .= self::numberToWordsHelper(floor($num / 100)) . " hundred ";
            $num %= 100;
        }
        if ($num > 0) {
            if ($str != "") {
                $str .= "and ";
            }
            if ($num < 20) {
                $str .= $ones[$num] . " ";
            } else {
                $str .= $tens[floor($num / 10)] . " ";
                if ($num % 10 > 0) {
                    $str .= $ones[$num % 10] . " ";
                }
            }
        }
        return trim($str);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled'
        ]);

        $invoice->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Invoice status updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            if ($invoice->invoice_type === 'toll') {
                Bulty::withoutGlobalScopes()->where('toll_invoice_id', $invoice->id)->update([
                    'toll_invoice_id' => null
                ]);
            } else {
                Bulty::withoutGlobalScopes()->where('invoice_id', $invoice->id)->update([
                    'bill_status' => 'unbilled',
                    'invoice_id' => null
                ]);
            }

            $invoice->delete();
        });

        return redirect()->back()->with('success', 'Invoice deleted successfully and LRs reverted to unbilled.');
    }
}
