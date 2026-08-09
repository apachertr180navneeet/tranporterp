<?php

namespace App\Http\Controllers\Admin\Transport;

use App\Exports\AdBlueDetailTemplateExport;
use App\Exports\FastTagTemplateExport;
use App\Exports\FuelDetailTemplateExport;
use App\Exports\OtherAmountDetailTemplateExport;
use App\Imports\AdBlueDetailImport;
use App\Imports\FastTagImport;
use App\Imports\FuelDetailImport;
use App\Imports\OtherAmountDetailImport;
use App\Exports\AdvanceDetailTemplateExport;
use App\Imports\AdvanceDetailImport;
use App\Http\Controllers\Controller;
use App\Models\AdBlueCompany;
use App\Models\Bulty;
use App\Models\FuelCompany;
use App\Models\FuelPump;
use App\Models\Trip;
use App\Models\FuelPumpPayment;
use App\Models\TripFuelDetail;
use App\Models\TripAdvanceDetail;
use App\Models\Vehicle;
use App\Models\AdBlueCompanyPayment;
use App\Models\TripAdBlueDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TripController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view trips')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Bulty::with(['consignor', 'consignee', 'originCity', 'destinationCity', 'bultyItems', 'trip'])
            ->whereNotNull('material_document');

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
                    });
            });
        }

        $trips = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.transport.trips.index', compact('trips'));
    }

    public function create($builtyId)
    {
        if (!auth()->user()->can('create trips')) {
            abort(403, 'Unauthorized action.');
        }

        $builty = Bulty::with(['consignor', 'consignee', 'originCity', 'destinationCity', 'bultyItems'])->findOrFail($builtyId);
        $fuelPumps = FuelPump::with('fuelCompany')->orderBy('name')->get();
        $fuelCompanies = FuelCompany::orderBy('name')->get();
        $adblueCompanies = AdBlueCompany::orderBy('name')->get();
        $cities = \App\Models\City::orderBy('name')->get();

        return view('admin.transport.trips.create', compact('builty', 'fuelPumps', 'fuelCompanies', 'adblueCompanies', 'cities'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create trips')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'builty_id' => 'required|exists:bulties,id|unique:trips,builty_id',
            'status' => 'nullable|in:pending,complete,reject',
            'fasttag_total_amount' => 'nullable|numeric|min:0',
            'fuel_amount' => 'nullable|numeric|min:0',
            'other_amount' => 'nullable|numeric|min:0',
            'adblue_total_amount' => 'nullable|numeric|min:0',
            'advance_total_amount' => 'nullable|numeric|min:0',

            'fast_tag_details' => 'nullable|array',
            'fast_tag_details.*.transaction_time' => 'nullable|string|max:50',
            'fast_tag_details.*.amount' => 'nullable|numeric|min:0',
            'fast_tag_details.*.description' => 'nullable|string|max:255',
            'fast_tag_details.*.transaction_id' => 'nullable|string|max:255',
            'fast_tag_details.*.location' => 'nullable|string|max:255',
            'fast_tag_details.*.one_way' => 'nullable|numeric|min:0',
            'fast_tag_details.*.return' => 'nullable|numeric|min:0',

            'fuel_details' => 'nullable|array',
            'fuel_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'fuel_details.*.fuel_company_id' => 'nullable|exists:fuel_companies,id',
            'fuel_details.*.fuel_pump_id' => 'nullable|exists:fuel_pumps,id',
            'fuel_details.*.quantity' => 'nullable|numeric|min:0',
            'fuel_details.*.rate' => 'nullable|numeric|min:0',
            'fuel_details.*.amount' => 'nullable|numeric|min:0',
            'fuel_details.*.km' => 'nullable|numeric|min:0',
            'fuel_details.*.payment_type' => 'nullable|string|in:credit,debit,cash',
            'fuel_details.*.remark' => 'nullable|string|max:500',

            'adblue_details' => 'nullable|array',
            'adblue_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'adblue_details.*.adblue_company_id' => 'nullable|exists:adblue_companies,id',
            'adblue_details.*.quantity' => 'nullable|numeric|min:0',
            'adblue_details.*.rate' => 'nullable|numeric|min:0',
            'adblue_details.*.amount' => 'nullable|numeric|min:0',
            'adblue_details.*.km' => 'nullable|numeric|min:0',

            'other_details' => 'nullable|array',
            'other_details.*.title' => 'nullable|string|max:255',
            'other_details.*.amount' => 'nullable|numeric|min:0',
            'other_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'other_details.*.remark' => 'nullable|string|max:500',

            'advance_details' => 'nullable|array',
            'advance_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'advance_details.*.fuel_company_id' => 'nullable|exists:fuel_companies,id',
            'advance_details.*.fuel_pump_id' => 'nullable|exists:fuel_pumps,id',
            'advance_details.*.payment_type' => 'nullable|string|in:credit,debit,cash',
            'advance_details.*.advance_amount' => 'nullable|numeric|min:0',
            'advance_details.*.remark' => 'nullable|string|max:500',
        ]);

        $trip = DB::transaction(function () use ($validated, $request) {
            $trip = Trip::create([
                'builty_id' => $validated['builty_id'],
                'fasttag_total_amount' => $validated['fasttag_total_amount'] ?? 0,
                'fuel_amount' => $validated['fuel_amount'] ?? 0,
                'other_amount' => $validated['other_amount'] ?? 0,
                'adblue_total_amount' => $validated['adblue_total_amount'] ?? 0,
                'advance_total_amount' => $validated['advance_total_amount'] ?? 0,
                'status' => $validated['status'] ?? 'pending',
            ]);

            if ($request->has('fast_tag_details')) {
                foreach ($request->fast_tag_details as $detail) {
                    if (!empty($detail['amount'])) {
                        $trip->fastTagDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'transaction_time' => $this->parseDatetime($detail['transaction_time'] ?? null),
                            'amount' => $detail['amount'] ?? 0,
                            'description' => $detail['description'] ?? null,
                            'transaction_id' => $detail['transaction_id'] ?? null,
                            'location' => $detail['location'] ?? null,
                            'one_way' => $detail['one_way'] ?? 0,
                            'return' => $detail['return'] ?? 0,
                        ]);
                    }
                }
            }

            if ($request->has('fuel_details')) {
                foreach ($request->fuel_details as $detail) {
                    if (!empty($detail['fuel_pump_id'])) {
                        $trip->fuelDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'date' => $detail['date'] ?? null,
                            'fuel_company_id' => $detail['fuel_company_id'] ?? null,
                            'fuel_pump_id' => $detail['fuel_pump_id'],
                            'quantity' => $detail['quantity'] ?? 0,
                            'rate' => $detail['rate'] ?? 0,
                            'amount' => $detail['amount'] ?? 0,
                            'km' => $detail['km'] ?? 0,
                            'payment_type' => $detail['payment_type'] ?? null,
                            'remark' => $detail['remark'] ?? null,
                        ]);
                    }
                }
            }

            if ($request->has('adblue_details')) {
                foreach ($request->adblue_details as $detail) {
                    if (!empty($detail['adblue_company_id'])) {
                        $trip->adblueDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'date' => $detail['date'] ?? null,
                            'adblue_company_id' => $detail['adblue_company_id'],
                            'quantity' => $detail['quantity'] ?? 0,
                            'rate' => $detail['rate'] ?? 0,
                            'amount' => $detail['amount'] ?? 0,
                            'km' => $detail['km'] ?? 0,
                            'payment_type' => $detail['payment_type'] ?? null,
                        ]);
                    }
                }
            }

            if ($request->has('other_details')) {
                foreach ($request->other_details as $detail) {
                    if (!empty($detail['title'])) {
                        $trip->otherAmountDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'title' => $detail['title'],
                            'amount' => $detail['amount'] ?? 0,
                            'date' => $detail['date'] ?? null,
                            'remark' => $detail['remark'] ?? null,
                        ]);
                    }
                }
            }

            if ($request->has('advance_details')) {
                foreach ($request->advance_details as $detail) {
                    if (!empty($detail['fuel_pump_id'])) {
                        $trip->advanceDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'date' => $detail['date'] ?? null,
                            'fuel_company_id' => $detail['fuel_company_id'] ?? null,
                            'fuel_pump_id' => $detail['fuel_pump_id'],
                            'advance_amount' => $detail['advance_amount'] ?? 0,
                            'payment_type' => $detail['payment_type'] ?? null,
                            'remark' => $detail['remark'] ?? null,
                        ]);
                    }
                }
            }

            return $trip;
        });

        return redirect()->route('admin.transport.trips.index')
            ->with('success', 'Trip created successfully.');
    }

    public function edit(Trip $trip)
    {
        if (!auth()->user()->can('edit trips')) {
            abort(403, 'Unauthorized action.');
        }

        $trip->load(['builty.consignor', 'builty.consignee', 'builty.originCity', 'builty.destinationCity', 'builty.bultyItems', 'fastTagDetails', 'fuelDetails.fuelPump', 'otherAmountDetails', 'adblueDetails', 'advanceDetails']);
        $fuelPumps = FuelPump::with('fuelCompany')->orderBy('name')->get();
        $fuelCompanies = FuelCompany::orderBy('name')->get();
        $adblueCompanies = AdBlueCompany::orderBy('name')->get();
        $cities = \App\Models\City::orderBy('name')->get();

        return view('admin.transport.trips.edit', compact('trip', 'fuelPumps', 'fuelCompanies', 'adblueCompanies', 'cities'));
    }

    public function update(Request $request, Trip $trip)
    {
        if (!auth()->user()->can('edit trips')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'status' => 'nullable|in:pending,complete,reject',
            'fasttag_total_amount' => 'nullable|numeric|min:0',
            'fuel_amount' => 'nullable|numeric|min:0',
            'other_amount' => 'nullable|numeric|min:0',
            'adblue_total_amount' => 'nullable|numeric|min:0',
            'advance_total_amount' => 'nullable|numeric|min:0',

            'fast_tag_details' => 'nullable|array',
            'fast_tag_details.*.transaction_time' => 'nullable|string|max:50',
            'fast_tag_details.*.amount' => 'nullable|numeric|min:0',
            'fast_tag_details.*.description' => 'nullable|string|max:255',
            'fast_tag_details.*.transaction_id' => 'nullable|string|max:255',
            'fast_tag_details.*.location' => 'nullable|string|max:255',
            'fast_tag_details.*.one_way' => 'nullable|numeric|min:0',
            'fast_tag_details.*.return' => 'nullable|numeric|min:0',

            'fuel_details' => 'nullable|array',
            'fuel_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'fuel_details.*.fuel_company_id' => 'nullable|exists:fuel_companies,id',
            'fuel_details.*.fuel_pump_id' => 'nullable|exists:fuel_pumps,id',
            'fuel_details.*.quantity' => 'nullable|numeric|min:0',
            'fuel_details.*.rate' => 'nullable|numeric|min:0',
            'fuel_details.*.amount' => 'nullable|numeric|min:0',
            'fuel_details.*.km' => 'nullable|numeric|min:0',
            'fuel_details.*.payment_type' => 'nullable|string|in:credit,debit,cash',
            'fuel_details.*.remark' => 'nullable|string|max:500',

            'adblue_details' => 'nullable|array',
            'adblue_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'adblue_details.*.adblue_company_id' => 'nullable|exists:adblue_companies,id',
            'adblue_details.*.quantity' => 'nullable|numeric|min:0',
            'adblue_details.*.rate' => 'nullable|numeric|min:0',
            'adblue_details.*.amount' => 'nullable|numeric|min:0',
            'adblue_details.*.km' => 'nullable|numeric|min:0',

            'other_details' => 'nullable|array',
            'other_details.*.title' => 'nullable|string|max:255',
            'other_details.*.amount' => 'nullable|numeric|min:0',
            'other_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'other_details.*.remark' => 'nullable|string|max:500',

            'advance_details' => 'nullable|array',
            'advance_details.*.date' => "nullable|date|before_or_equal:9999-12-31",
            'advance_details.*.fuel_company_id' => 'nullable|exists:fuel_companies,id',
            'advance_details.*.fuel_pump_id' => 'nullable|exists:fuel_pumps,id',
            'advance_details.*.payment_type' => 'nullable|string|in:credit,debit,cash',
            'advance_details.*.advance_amount' => 'nullable|numeric|min:0',
            'advance_details.*.remark' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($trip, $validated, $request) {
            $trip->update([
                'fasttag_total_amount' => $validated['fasttag_total_amount'] ?? 0,
                'fuel_amount' => $validated['fuel_amount'] ?? 0,
                'other_amount' => $validated['other_amount'] ?? 0,
                'adblue_total_amount' => $validated['adblue_total_amount'] ?? 0,
                'advance_total_amount' => $validated['advance_total_amount'] ?? 0,
                'status' => $validated['status'] ?? $trip->status,
            ]);

            $trip->fastTagDetails()->delete();
            if ($request->has('fast_tag_details')) {
                foreach ($request->fast_tag_details as $detail) {
                    if (!empty($detail['amount'])) {
                        $trip->fastTagDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'transaction_time' => $this->parseDatetime($detail['transaction_time'] ?? null),
                            'amount' => $detail['amount'] ?? 0,
                            'description' => $detail['description'] ?? null,
                            'transaction_id' => $detail['transaction_id'] ?? null,
                            'location' => $detail['location'] ?? null,
                            'one_way' => $detail['one_way'] ?? 0,
                            'return' => $detail['return'] ?? 0,
                        ]);
                    }
                }
            }

            $trip->fuelDetails()->delete();
            if ($request->has('fuel_details')) {
                foreach ($request->fuel_details as $detail) {
                    if (!empty($detail['fuel_pump_id'])) {
                        $trip->fuelDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'date' => $detail['date'] ?? null,
                            'fuel_company_id' => $detail['fuel_company_id'] ?? null,
                            'fuel_pump_id' => $detail['fuel_pump_id'],
                            'quantity' => $detail['quantity'] ?? 0,
                            'rate' => $detail['rate'] ?? 0,
                            'amount' => $detail['amount'] ?? 0,
                            'km' => $detail['km'] ?? 0,
                            'payment_type' => $detail['payment_type'] ?? null,
                            'remark' => $detail['remark'] ?? null,
                        ]);
                    }
                }
            }

            $trip->adblueDetails()->delete();
            if ($request->has('adblue_details')) {
                foreach ($request->adblue_details as $detail) {
                    if (!empty($detail['adblue_company_id'])) {
                        $trip->adblueDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'date' => $detail['date'] ?? null,
                            'adblue_company_id' => $detail['adblue_company_id'],
                            'quantity' => $detail['quantity'] ?? 0,
                            'rate' => $detail['rate'] ?? 0,
                            'amount' => $detail['amount'] ?? 0,
                            'km' => $detail['km'] ?? 0,
                            'payment_type' => $detail['payment_type'] ?? null,
                        ]);
                    }
                }
            }

            $trip->otherAmountDetails()->delete();
            if ($request->has('other_details')) {
                foreach ($request->other_details as $detail) {
                    if (!empty($detail['title'])) {
                        $trip->otherAmountDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'title' => $detail['title'],
                            'amount' => $detail['amount'] ?? 0,
                            'date' => $detail['date'] ?? null,
                            'remark' => $detail['remark'] ?? null,
                        ]);
                    }
                }
            }

            $trip->advanceDetails()->delete();
            if ($request->has('advance_details')) {
                foreach ($request->advance_details as $detail) {
                    if (!empty($detail['fuel_pump_id'])) {
                        $trip->advanceDetails()->create([
                            'builty_id' => $trip->builty_id,
                            'date' => $detail['date'] ?? null,
                            'fuel_company_id' => $detail['fuel_company_id'] ?? null,
                            'fuel_pump_id' => $detail['fuel_pump_id'],
                            'advance_amount' => $detail['advance_amount'] ?? 0,
                            'payment_type' => $detail['payment_type'] ?? null,
                            'remark' => $detail['remark'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.transport.trips.index')
            ->with('success', 'Trip updated successfully.');
    }

    public function toggleStatus(Request $request, Trip $trip)
    {
        if (!auth()->user()->can('edit trips')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $newStatus = $request->input('status', 'pending');
        if (!in_array($newStatus, ['pending', 'complete', 'reject'])) {
            $newStatus = 'pending';
        }
        $trip->update(['status' => $newStatus]);

        return response()->json(['status' => $newStatus]);
    }

    public function downloadFastTagTemplate()
    {
        return Excel::download(new FastTagTemplateExport, 'fast_tag_import_template.xlsx');
    }

    public function importFastTag(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        try {
            $import = new FastTagImport;
            $rows = Excel::toArray($import, $request->file('file'));

            $data = [];
            if (!empty($rows[0])) {
                foreach ($rows[0] as $row) {
                    $time = $this->parseDatetime($row['transaction_time'] ?? null);
                    $oneWay = floatval($row['one_way'] ?? 0);
                    $return = floatval($row['return'] ?? 0);
                    $amount = $oneWay + $return;

                    $data[] = [
                        'transaction_time' => $time,
                        'amount' => $amount,
                        'description' => $row['description'] ?? null,
                        'transaction_id' => $row['transaction_id'] ?? null,
                        'location' => $row['location'] ?? null,
                        'one_way' => $oneWay,
                        'return' => $return,
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadFuelDetailTemplate()
    {
        return Excel::download(new FuelDetailTemplateExport, 'fuel_detail_import_template.xlsx');
    }

    public function importFuelDetail(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $defaultCompanyId = $request->input('fuel_company_id');
        $defaultPumpId = $request->input('fuel_pump_id');

        try {
            $import = new FuelDetailImport;
            $rows = Excel::toArray($import, $request->file('file'));

            $data = [];
            if (!empty($rows[0])) {
                foreach ($rows[0] as $row) {
                    $date = null;
                    if (!empty($row['date'])) {
                        $date = is_numeric($row['date'])
                            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d')
                            : Carbon::parse($row['date'])->format('Y-m-d');
                    }
                    $data[] = [
                        'date' => $date,
                        'fuel_company_id' => $defaultCompanyId ?: null,
                        'fuel_pump_id' => $defaultPumpId ?: null,
                        'quantity' => $row['quantity'] ?? 0,
                        'rate' => $row['rate'] ?? 0,
                        'amount' => $row['amount'] ?? 0,
                        'km' => $row['km'] ?? 0,
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadAdBlueDetailTemplate()
    {
        return Excel::download(new AdBlueDetailTemplateExport, 'adblue_detail_import_template.xlsx');
    }

    public function importAdBlueDetail(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $defaultCompanyId = $request->input('adblue_company_id');

        try {
            $import = new AdBlueDetailImport;
            $rows = Excel::toArray($import, $request->file('file'));

            $data = [];
            if (!empty($rows[0])) {
                foreach ($rows[0] as $row) {
                    $date = null;
                    if (!empty($row['date'])) {
                        $date = is_numeric($row['date'])
                            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d')
                            : Carbon::parse($row['date'])->format('Y-m-d');
                    }
                    $data[] = [
                        'date' => $date,
                        'adblue_company_id' => $defaultCompanyId ?: null,
                        'quantity' => $row['quantity'] ?? 0,
                        'rate' => $row['rate'] ?? 0,
                        'amount' => $row['amount'] ?? 0,
                        'km' => $row['km'] ?? 0,
                        'payment_type' => $row['payment_type'] ?? null,
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadOtherAmountDetailTemplate()
    {
        return Excel::download(new OtherAmountDetailTemplateExport, 'other_amount_detail_import_template.xlsx');
    }

    public function importOtherAmountDetail(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        try {
            $import = new OtherAmountDetailImport;
            $rows = Excel::toArray($import, $request->file('file'));

            $data = [];
            if (!empty($rows[0])) {
                foreach ($rows[0] as $row) {
                    $date = null;
                    if (!empty($row['date'])) {
                        $date = is_numeric($row['date'])
                            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d')
                            : Carbon::parse($row['date'])->format('Y-m-d');
                    }
                    $data[] = [
                        'title' => $row['title'] ?? '',
                        'amount' => $row['amount'] ?? 0,
                        'date' => $date,
                        'remark' => $row['remark'] ?? '',
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadAdvanceDetailTemplate()
    {
        return Excel::download(new AdvanceDetailTemplateExport, 'advance_detail_import_template.xlsx');
    }

    public function importAdvanceDetail(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $defaultCompanyId = $request->input('fuel_company_id');
        $defaultPumpId = $request->input('fuel_pump_id');

        try {
            $import = new AdvanceDetailImport;
            $rows = Excel::toArray($import, $request->file('file'));

            $data = [];
            if (!empty($rows[0])) {
                foreach ($rows[0] as $row) {
                    $date = null;
                    if (!empty($row['date'])) {
                        $date = is_numeric($row['date'])
                            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d')
                            : Carbon::parse($row['date'])->format('Y-m-d');
                    }
                    $data[] = [
                        'date' => $date,
                        'fuel_company_id' => $defaultCompanyId ?: null,
                        'fuel_pump_id' => $defaultPumpId ?: null,
                        'advance_amount' => $row['advance_amount'] ?? 0,
                        'payment_type' => $row['payment_type'] ?? null,
                        'remark' => $row['remark'] ?? '',
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getPumpsByCompany($companyId)
    {
        if (!FuelCompany::where('id', $companyId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $pumps = FuelPump::where('fuel_company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        return response()->json($pumps);
    }

    private function parseDatetime($value)
    {
        if (!$value) return null;
        try {
            return Carbon::createFromFormat('d-M-y h:i A', $value)->format('Y-m-d H:i');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i');
            } catch (\Exception $e) {
                return $value;
            }
        }
    }

    public function fuelOutstanding(Request $request)
    {
        if (!auth()->user()->can('view fuel outstanding')) {
            abort(403, 'Unauthorized action.');
        }

        $vehicleList = Vehicle::orderBy('vehicle_number')->get();
        $fuelCompanies = FuelCompany::orderBy('name')->get();
        $fuelPumps = FuelPump::with('fuelCompany')->orderBy('name')->get();
        
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            $companies = \App\Models\Company::orderBy('name')->get();
        } elseif ($user && $user->company_id) {
            $companies = \App\Models\Company::where('id', $user->company_id)->get();
        } else {
            $companies = \App\Models\Company::orderBy('name')->get();
        }

        $targetCompanyId = $request->filled('company_id') ? $request->company_id : (session('current_company_id') && session('current_company_id') !== 'all' ? session('current_company_id') : null);

        // 1. Calculate Opening Balances if date_from is set
        $openingBalancesMap = [];
        $openingBalanceAll = 0.0;

        if ($request->filled('date_from')) {
            $opFuelQuery = TripFuelDetail::query()->where('payment_type', 'credit')->where('date', '<', $request->date_from);
            $opAdvanceQuery = TripAdvanceDetail::query()->where(function ($q) { $q->whereNull('payment_type')->orWhere('payment_type', 'credit'); })->where('date', '<', $request->date_from);
            $opPaymentQuery = FuelPumpPayment::query()->withoutGlobalScope('company')->where('date', '<', $request->date_from);

            if ($targetCompanyId) {
                $opFuelQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                    $q->where('company_id', $targetCompanyId);
                });
                $opAdvanceQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                    $q->where('company_id', $targetCompanyId);
                });
                $opPaymentQuery->where('company_id', $targetCompanyId);
            }

            if ($request->filled('fuel_company_id')) {
                $opFuelQuery->where('fuel_company_id', $request->fuel_company_id);
                $opAdvanceQuery->where('fuel_company_id', $request->fuel_company_id);
                $opPaymentQuery->where('fuel_company_id', $request->fuel_company_id);
            }
            if ($request->filled('fuel_pump_id')) {
                $opFuelQuery->where('fuel_pump_id', $request->fuel_pump_id);
                $opAdvanceQuery->where('fuel_pump_id', $request->fuel_pump_id);
                $opPaymentQuery->where('fuel_pump_id', $request->fuel_pump_id);
            }
            if ($request->filled('vehicle_id')) {
                $opFuelQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('vehicle_id', $request->vehicle_id);
                });
                $opAdvanceQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('vehicle_id', $request->vehicle_id);
                });
            }
            if ($request->filled('lr_no')) {
                $opFuelQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('lr_no', 'like', "%{$request->lr_no}%");
                });
                $opAdvanceQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('lr_no', 'like', "%{$request->lr_no}%");
                });
            }

            $opFuels = $opFuelQuery->select('fuel_company_id', 'fuel_pump_id')->selectRaw('SUM(amount) as total')->groupBy('fuel_company_id', 'fuel_pump_id')->get();
            $opAdvances = $opAdvanceQuery->select('fuel_company_id', 'fuel_pump_id')->selectRaw('SUM(advance_amount) as total')->groupBy('fuel_company_id', 'fuel_pump_id')->get();
            $opPayments = $opPaymentQuery->select('fuel_company_id', 'fuel_pump_id')->selectRaw('SUM(amount) as total')->groupBy('fuel_company_id', 'fuel_pump_id')->get();

            foreach ($opFuels as $o) {
                $k = ($o->fuel_company_id ?? 0) . '-' . ($o->fuel_pump_id ?? 0);
                $openingBalancesMap[$k] = ($openingBalancesMap[$k] ?? 0.0) + (float)$o->total;
            }
            foreach ($opAdvances as $o) {
                $k = ($o->fuel_company_id ?? 0) . '-' . ($o->fuel_pump_id ?? 0);
                $openingBalancesMap[$k] = ($openingBalancesMap[$k] ?? 0.0) + (float)$o->total;
            }
            foreach ($opPayments as $o) {
                $k = ($o->fuel_company_id ?? 0) . '-' . ($o->fuel_pump_id ?? 0);
                $openingBalancesMap[$k] = ($openingBalancesMap[$k] ?? 0.0) - (float)$o->total;
            }
        }

        // 2. Calculate Summary Table (Overview) of all pumps/companies for the selected period
        $fuelQuery = TripFuelDetail::query()->where('payment_type', 'credit');
        $advanceQuery = TripAdvanceDetail::query()->where(function ($q) { $q->whereNull('payment_type')->orWhere('payment_type', 'credit'); });
        $paymentQuery = FuelPumpPayment::query()->withoutGlobalScope('company');

        if ($targetCompanyId) {
            $fuelQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                $q->where('company_id', $targetCompanyId);
            });
            $advanceQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                $q->where('company_id', $targetCompanyId);
            });
            $paymentQuery->where('company_id', $targetCompanyId);
        }

        // Apply filters to summary query if filtered
        if ($request->filled('fuel_company_id')) {
            $fuelQuery->where('fuel_company_id', $request->fuel_company_id);
            $advanceQuery->where('fuel_company_id', $request->fuel_company_id);
            $paymentQuery->where('fuel_company_id', $request->fuel_company_id);
        }
        if ($request->filled('fuel_pump_id')) {
            $fuelQuery->where('fuel_pump_id', $request->fuel_pump_id);
            $advanceQuery->where('fuel_pump_id', $request->fuel_pump_id);
            $paymentQuery->where('fuel_pump_id', $request->fuel_pump_id);
        }
        if ($request->filled('date_from')) {
            $fuelQuery->where('date', '>=', $request->date_from);
            $advanceQuery->where('date', '>=', $request->date_from);
            $paymentQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $fuelQuery->where('date', '<=', $request->date_to);
            $advanceQuery->where('date', '<=', $request->date_to);
            $paymentQuery->where('date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $fuelQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
            $advanceQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }
        if ($request->filled('lr_no')) {
            $fuelQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('lr_no', 'like', "%{$request->lr_no}%");
            });
            $advanceQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('lr_no', 'like', "%{$request->lr_no}%");
            });
        }

        $fuelPurchases = $fuelQuery->select('fuel_company_id', 'fuel_pump_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(quantity) as total_qty')
            ->groupBy('fuel_company_id', 'fuel_pump_id')
            ->get();

        $driverAdvances = $advanceQuery->select('fuel_company_id', 'fuel_pump_id')
            ->selectRaw('SUM(advance_amount) as total_amount')
            ->groupBy('fuel_company_id', 'fuel_pump_id')
            ->get();

        $pumpPayments = $paymentQuery->select('fuel_company_id', 'fuel_pump_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('fuel_company_id', 'fuel_pump_id')
            ->get();

        $overviewData = [];

        foreach ($fuelPurchases as $fp) {
            $key = ($fp->fuel_company_id ?? 0) . '-' . ($fp->fuel_pump_id ?? 0);
            $overviewData[$key] = [
                'fuel_company_id' => $fp->fuel_company_id,
                'fuel_pump_id' => $fp->fuel_pump_id,
                'total_qty' => (float)$fp->total_qty,
                'fuel_amount' => (float)$fp->total_amount,
                'advance_amount' => 0.0,
                'payment_amount' => 0.0,
            ];
        }

        foreach ($driverAdvances as $da) {
            $key = ($da->fuel_company_id ?? 0) . '-' . ($da->fuel_pump_id ?? 0);
            if (!isset($overviewData[$key])) {
                $overviewData[$key] = [
                    'fuel_company_id' => $da->fuel_company_id,
                    'fuel_pump_id' => $da->fuel_pump_id,
                    'total_qty' => 0.0,
                    'fuel_amount' => 0.0,
                    'advance_amount' => 0.0,
                    'payment_amount' => 0.0,
                ];
            }
            $overviewData[$key]['advance_amount'] = (float)$da->total_amount;
        }

        foreach ($pumpPayments as $pp) {
            $key = ($pp->fuel_company_id ?? 0) . '-' . ($pp->fuel_pump_id ?? 0);
            if (!isset($overviewData[$key])) {
                $overviewData[$key] = [
                    'fuel_company_id' => $pp->fuel_company_id,
                    'fuel_pump_id' => $pp->fuel_pump_id,
                    'total_qty' => 0.0,
                    'fuel_amount' => 0.0,
                    'advance_amount' => 0.0,
                    'payment_amount' => 0.0,
                ];
            }
            $overviewData[$key]['payment_amount'] = (float)$pp->total_amount;
        }

        $companiesMap = $fuelCompanies->keyBy('id');
        $pumpsMap = $fuelPumps->keyBy('id');

        $totalFuelAmountAll = 0;
        $totalAdvanceAmountAll = 0;
        $totalPaymentAmountAll = 0;

        foreach ($overviewData as $key => &$item) {
            $item['company_name'] = isset($companiesMap[$item['fuel_company_id']]) ? $companiesMap[$item['fuel_company_id']]->name : 'Direct / Unknown';
            $item['pump_name'] = isset($pumpsMap[$item['fuel_pump_id']]) ? $pumpsMap[$item['fuel_pump_id']]->name : 'Unknown Pump';
            
            $opBal = $openingBalancesMap[$key] ?? 0.0;
            $item['opening_balance'] = $opBal;
            $item['net_outstanding'] = $opBal + ($item['fuel_amount'] + $item['advance_amount']) - $item['payment_amount'];

            $totalFuelAmountAll += $item['fuel_amount'];
            $totalAdvanceAmountAll += $item['advance_amount'];
            $totalPaymentAmountAll += $item['payment_amount'];
            $openingBalanceAll += $opBal;
        }
        unset($item);

        usort($overviewData, function ($a, $b) {
            return strcmp($a['company_name'] . $a['pump_name'], $b['company_name'] . $b['pump_name']);
        });

        // 3. Detailed Ledger
        $ledgerItems = [];
        $fuelLedgerQuery = TripFuelDetail::with(['trip.builty.vehicle', 'fuelCompany', 'fuelPump'])
            ->where('payment_type', 'credit');

        $advanceLedgerQuery = TripAdvanceDetail::with(['trip.builty.vehicle', 'fuelCompany', 'fuelPump'])
            ->where(function ($q) { $q->whereNull('payment_type')->orWhere('payment_type', 'credit'); });
        $paymentLedgerQuery = FuelPumpPayment::with(['fuelCompany', 'fuelPump', 'company'])
            ->withoutGlobalScope('company');

        if ($targetCompanyId) {
            $fuelLedgerQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                $q->where('company_id', $targetCompanyId);
            });
            $advanceLedgerQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                $q->where('company_id', $targetCompanyId);
            });
            $paymentLedgerQuery->where('company_id', $targetCompanyId);
        }

        if ($request->filled('fuel_company_id')) {
            $fuelLedgerQuery->where('fuel_company_id', $request->fuel_company_id);
            $advanceLedgerQuery->where('fuel_company_id', $request->fuel_company_id);
            $paymentLedgerQuery->where('fuel_company_id', $request->fuel_company_id);
        }
        if ($request->filled('fuel_pump_id')) {
            $fuelLedgerQuery->where('fuel_pump_id', $request->fuel_pump_id);
            $advanceLedgerQuery->where('fuel_pump_id', $request->fuel_pump_id);
            $paymentLedgerQuery->where('fuel_pump_id', $request->fuel_pump_id);
        }
        if ($request->filled('date_from')) {
            $fuelLedgerQuery->where('date', '>=', $request->date_from);
            $advanceLedgerQuery->where('date', '>=', $request->date_from);
            $paymentLedgerQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $fuelLedgerQuery->where('date', '<=', $request->date_to);
            $advanceLedgerQuery->where('date', '<=', $request->date_to);
            $paymentLedgerQuery->where('date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $fuelLedgerQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
            $advanceLedgerQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }
        if ($request->filled('lr_no')) {
            $fuelLedgerQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('lr_no', 'like', "%{$request->lr_no}%");
            });
            $advanceLedgerQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('lr_no', 'like', "%{$request->lr_no}%");
            });
        }

        // Calculate single overall opening balance for the ledger view
        $singleOpeningBalance = 0.0;
        if ($request->filled('date_from')) {
            $singleOpFuelQuery = TripFuelDetail::query()->where('payment_type', 'credit')->where('date', '<', $request->date_from);
            $singleOpAdvanceQuery = TripAdvanceDetail::query()->where(function ($q) { $q->whereNull('payment_type')->orWhere('payment_type', 'credit'); })->where('date', '<', $request->date_from);
            $singleOpPaymentQuery = FuelPumpPayment::query()->withoutGlobalScope('company')->where('date', '<', $request->date_from);

            if ($targetCompanyId) {
                $singleOpFuelQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                    $q->where('company_id', $targetCompanyId);
                });
                $singleOpAdvanceQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                    $q->where('company_id', $targetCompanyId);
                });
                $singleOpPaymentQuery->where('company_id', $targetCompanyId);
            }

            if ($request->filled('fuel_company_id')) {
                $singleOpFuelQuery->where('fuel_company_id', $request->fuel_company_id);
                $singleOpAdvanceQuery->where('fuel_company_id', $request->fuel_company_id);
                $singleOpPaymentQuery->where('fuel_company_id', $request->fuel_company_id);
            }
            if ($request->filled('fuel_pump_id')) {
                $singleOpFuelQuery->where('fuel_pump_id', $request->fuel_pump_id);
                $singleOpAdvanceQuery->where('fuel_pump_id', $request->fuel_pump_id);
                $singleOpPaymentQuery->where('fuel_pump_id', $request->fuel_pump_id);
            }
            if ($request->filled('vehicle_id')) {
                $singleOpFuelQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('vehicle_id', $request->vehicle_id);
                });
                $singleOpAdvanceQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('vehicle_id', $request->vehicle_id);
                });
            }
            if ($request->filled('lr_no')) {
                $singleOpFuelQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('lr_no', 'like', "%{$request->lr_no}%");
                });
                $singleOpAdvanceQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('lr_no', 'like', "%{$request->lr_no}%");
                });
            }

            $singleOpDebit = (float)$singleOpFuelQuery->sum('amount') + (float)$singleOpAdvanceQuery->sum('advance_amount');
            $singleOpCredit = (float)$singleOpPaymentQuery->sum('amount');
            $singleOpeningBalance = $singleOpDebit - $singleOpCredit;
        }

        // Add opening balance to ledger items if date_from is set
        if ($request->filled('date_from')) {
            $ledgerItems[] = [
                'date' => $request->date_from,
                'date_raw' => Carbon::parse($request->date_from)->startOfDay(),
                'type' => 'Opening Balance',
                'ref_no' => '-',
                'vehicle' => '-',
                'company' => '-',
                'pump' => '-',
                'qty' => 0.00,
                'rate' => 0.00,
                'debit' => $singleOpeningBalance >= 0 ? $singleOpeningBalance : 0.00,
                'credit' => $singleOpeningBalance < 0 ? abs($singleOpeningBalance) : 0.00,
                'remark' => 'Outstanding balance before ' . date('d-m-Y', strtotime($request->date_from)),
            ];
        }

        $fuels = $fuelLedgerQuery->get();
        foreach ($fuels as $f) {
            $ledgerItems[] = [
                'date' => $f->date ? $f->date->format('Y-m-d') : null,
                'date_raw' => $f->date,
                'type' => 'Fuel Purchase',
                'ref_no' => $f->trip?->builty?->lr_no ?? '-',
                'vehicle' => $f->trip?->builty?->vehicle?->vehicle_number ?? '-',
                'company' => $f->fuelCompany?->name ?? '-',
                'pump' => $f->fuelPump?->name ?? '-',
                'qty' => (float)$f->quantity,
                'rate' => (float)$f->rate,
                'debit' => (float)$f->amount,
                'credit' => 0.00,
                'remark' => $f->remark ?? '-',
            ];
        }

        $advances = $advanceLedgerQuery->get();
        foreach ($advances as $a) {
            $ledgerItems[] = [
                'date' => $a->date ? $a->date->format('Y-m-d') : null,
                'date_raw' => $a->date,
                'type' => 'Driver Advance',
                'ref_no' => $a->trip?->builty?->lr_no ?? '-',
                'vehicle' => $a->trip?->builty?->vehicle?->vehicle_number ?? '-',
                'company' => $a->fuelCompany?->name ?? '-',
                'pump' => $a->fuelPump?->name ?? '-',
                'qty' => 0.00,
                'rate' => 0.00,
                'debit' => (float)$a->advance_amount,
                'credit' => 0.00,
                'remark' => $a->remark ?? '-',
            ];
        }

        $paymentsList = $paymentLedgerQuery->get();
        foreach ($paymentsList as $p) {
            $ledgerItems[] = [
                'date' => $p->date ? $p->date->format('Y-m-d') : null,
                'date_raw' => $p->date,
                'type' => 'Credit Payment',
                'ref_no' => $p->payment_method ?? 'Payment',
                'vehicle' => '-',
                'company' => $p->fuelCompany?->name ?? '-',
                'pump' => $p->fuelPump?->name ?? '-',
                'qty' => 0.00,
                'rate' => 0.00,
                'debit' => 0.00,
                'credit' => (float)$p->amount,
                'remark' => $p->remark ?? '-',
                'payment_id' => $p->id,
            ];
        }

        usort($ledgerItems, function ($a, $b) {
            $dateA = $a['date_raw'] ? $a['date_raw']->timestamp : 0;
            $dateB = $b['date_raw'] ? $b['date_raw']->timestamp : 0;
            if ($dateA == $dateB) {
                // Keep Opening Balance first, then purchases/advances, then payments
                if ($a['type'] === 'Opening Balance') return -1;
                if ($b['type'] === 'Opening Balance') return 1;
                $valA = $a['type'] === 'Credit Payment' ? 1 : 0;
                $valB = $b['type'] === 'Credit Payment' ? 1 : 0;
                return $valA - $valB;
            }
            return $dateA - $dateB;
        });

        $balance = 0.0;
        foreach ($ledgerItems as &$item) {
            if ($item['type'] === 'Opening Balance') {
                $balance = $item['debit'] - $item['credit'];
            } else {
                $balance += ($item['debit'] - $item['credit']);
            }
            $item['balance'] = $balance;
        }
        unset($item);

        // 4. Paginated Credit Payments tab
        $paymentsQuery = FuelPumpPayment::with(['fuelCompany', 'fuelPump', 'company'])
            ->withoutGlobalScope('company')
            ->orderBy('date', 'desc');

        if ($targetCompanyId) {
            $paymentsQuery->where('company_id', $targetCompanyId);
        }
        if ($request->filled('fuel_company_id')) {
            $paymentsQuery->where('fuel_company_id', $request->fuel_company_id);
        }
        if ($request->filled('fuel_pump_id')) {
            $paymentsQuery->where('fuel_pump_id', $request->fuel_pump_id);
        }
        if ($request->filled('date_from')) {
            $paymentsQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $paymentsQuery->where('date', '<=', $request->date_to);
        }
        $payments = $paymentsQuery->paginate(15);
        if ($request->export === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FuelLedgerExport($ledgerItems), 'fuel_ledger_'.date('YmdHis').'.xlsx');
        } elseif ($request->export === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.transport.trips.exports.fuel-ledger', compact('ledgerItems'));
            return $pdf->download('fuel_ledger_'.date('YmdHis').'.pdf');
        }

        return view('admin.transport.trips.fuel-outstanding', compact(
            'vehicleList',
            'fuelCompanies',
            'fuelPumps',
            'overviewData',
            'ledgerItems',
            'payments',
            'totalFuelAmountAll',
            'totalAdvanceAmountAll',
            'totalPaymentAmountAll',
            'openingBalanceAll',
            'companies'
        ));
    }

    public function storeFuelPayment(Request $request)
    {
        if (!auth()->user()->can('create fuel outstanding')) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'date' => 'required|date|before_or_equal:9999-12-31',
            'fuel_company_id' => 'required|exists:fuel_companies,id',
            'fuel_pump_id' => 'required|exists:fuel_pumps,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:500',
        ]);

        $sessionCompanyId = $request->input('company_id') ?: session('current_company_id');
        $sessionBranchId = session('current_branch_id');

        if (!$sessionCompanyId || $sessionCompanyId === 'all') {
            $sessionCompanyId = auth()->user()->company_id;
        }
        if (!$sessionBranchId || $sessionBranchId === 'all') {
            $sessionBranchId = auth()->user()->branch_id;
        }

        if (!$sessionCompanyId) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please select a specific company from the top bar to record payment.']);
            }
            return redirect()->back()->with('error', 'Please select a specific company to record payment.');
        }

        $payment = FuelPumpPayment::create([
            'company_id' => $sessionCompanyId,
            'date' => $validated['date'],
            'fuel_company_id' => $validated['fuel_company_id'],
            'fuel_pump_id' => $validated['fuel_pump_id'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? 'Bank Transfer',
            'remark' => $validated['remark'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Payment recorded successfully.', 'payment' => $payment]);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function editFuelPayment($id)
    {
        if (!auth()->user()->can('edit fuel outstanding')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $payment = FuelPumpPayment::with('company')->findOrFail($id);
        return response()->json($payment);
    }

    public function updateFuelPayment(Request $request, $id)
    {
        if (!auth()->user()->can('edit fuel outstanding')) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $payment = FuelPumpPayment::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'date' => 'required|date|before_or_equal:9999-12-31',
            'fuel_company_id' => 'required|exists:fuel_companies,id',
            'fuel_pump_id' => 'required|exists:fuel_pumps,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:500',
        ]);

        if ($request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        }

        $payment->update($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Payment updated successfully.', 'payment' => $payment]);
        }

        return redirect()->back()->with('success', 'Payment updated successfully.');
    }

    public function destroyFuelPayment($id)
    {
        if (!auth()->user()->can('delete fuel outstanding')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $payment = FuelPumpPayment::findOrFail($id);
        $payment->delete();

        return response()->json(['success' => true, 'message' => 'Payment deleted successfully.']);
    }

    public function adblueOutstanding(Request $request)
    {
        if (!auth()->user()->can('view adblue outstanding')) {
            abort(403, 'Unauthorized action.');
        }

        $vehicleList = Vehicle::orderBy('vehicle_number')->get();
        $adblueCompanies = AdBlueCompany::orderBy('name')->get();
        
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            $companies = \App\Models\Company::orderBy('name')->get();
        } elseif ($user && $user->company_id) {
            $companies = \App\Models\Company::where('id', $user->company_id)->get();
        } else {
            $companies = \App\Models\Company::orderBy('name')->get();
        }

        $targetCompanyId = $request->filled('company_id') ? $request->company_id : (session('current_company_id') && session('current_company_id') !== 'all' ? session('current_company_id') : null);

        // 1. Calculate Opening Balances if date_from is set
        $openingBalancesMap = [];
        $openingBalanceAll = 0.0;

        if ($request->filled('date_from')) {
            $opAdBlueQuery = TripAdBlueDetail::query()->where('date', '<', $request->date_from);
            $opPaymentQuery = AdBlueCompanyPayment::query()->withoutGlobalScope('company')->where('date', '<', $request->date_from);

            if ($targetCompanyId) {
                $opAdBlueQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                    $q->where('company_id', $targetCompanyId);
                });
                $opPaymentQuery->where('company_id', $targetCompanyId);
            }

            if ($request->filled('adblue_company_id')) {
                $opAdBlueQuery->where('adblue_company_id', $request->adblue_company_id);
                $opPaymentQuery->where('adblue_company_id', $request->adblue_company_id);
            }
            if ($request->filled('vehicle_id')) {
                $opAdBlueQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('vehicle_id', $request->vehicle_id);
                });
            }
            if ($request->filled('lr_no')) {
                $opAdBlueQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('lr_no', 'like', "%{$request->lr_no}%");
                });
            }

            $opAdBlues = $opAdBlueQuery->select('adblue_company_id')->selectRaw('SUM(amount) as total')->groupBy('adblue_company_id')->get();
            $opPayments = $opPaymentQuery->select('adblue_company_id')->selectRaw('SUM(amount) as total')->groupBy('adblue_company_id')->get();

            foreach ($opAdBlues as $o) {
                $k = $o->adblue_company_id;
                $openingBalancesMap[$k] = ($openingBalancesMap[$k] ?? 0.0) + (float)$o->total;
            }
            foreach ($opPayments as $o) {
                $k = $o->adblue_company_id;
                $openingBalancesMap[$k] = ($openingBalancesMap[$k] ?? 0.0) - (float)$o->total;
            }
        }

        // 2. Calculate Summary Table (Overview) of all companies for the selected period
        $adblueQuery = TripAdBlueDetail::query();
        $paymentQuery = AdBlueCompanyPayment::query()->withoutGlobalScope('company');

        if ($targetCompanyId) {
            $adblueQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                $q->where('company_id', $targetCompanyId);
            });
            $paymentQuery->where('company_id', $targetCompanyId);
        }

        // Apply filters to summary query if filtered
        if ($request->filled('adblue_company_id')) {
            $adblueQuery->where('adblue_company_id', $request->adblue_company_id);
            $paymentQuery->where('adblue_company_id', $request->adblue_company_id);
        }
        if ($request->filled('date_from')) {
            $adblueQuery->where('date', '>=', $request->date_from);
            $paymentQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $adblueQuery->where('date', '<=', $request->date_to);
            $paymentQuery->where('date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $adblueQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }
        if ($request->filled('lr_no')) {
            $adblueQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('lr_no', 'like', "%{$request->lr_no}%");
            });
        }

        $adbluePurchases = $adblueQuery->select('adblue_company_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(quantity) as total_qty')
            ->groupBy('adblue_company_id')
            ->get();

        $companyPayments = $paymentQuery->select('adblue_company_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('adblue_company_id')
            ->get();

        $overviewData = [];

        foreach ($adbluePurchases as $ab) {
            $key = $ab->adblue_company_id;
            $overviewData[$key] = [
                'adblue_company_id' => $ab->adblue_company_id,
                'total_qty' => (float)$ab->total_qty,
                'adblue_amount' => (float)$ab->total_amount,
                'payment_amount' => 0.0,
            ];
        }

        foreach ($companyPayments as $cp) {
            $key = $cp->adblue_company_id;
            if (!isset($overviewData[$key])) {
                $overviewData[$key] = [
                    'adblue_company_id' => $cp->adblue_company_id,
                    'total_qty' => 0.0,
                    'adblue_amount' => 0.0,
                    'payment_amount' => 0.0,
                ];
            }
            $overviewData[$key]['payment_amount'] = (float)$cp->total_amount;
        }

        $companiesMap = $adblueCompanies->keyBy('id');

        $totalAdBlueAmountAll = 0;
        $totalPaymentAmountAll = 0;

        foreach ($overviewData as $key => &$item) {
            $item['company_name'] = isset($companiesMap[$item['adblue_company_id']]) ? $companiesMap[$item['adblue_company_id']]->name : 'Unknown Company';
            
            $opBal = $openingBalancesMap[$key] ?? 0.0;
            $item['opening_balance'] = $opBal;
            $item['net_outstanding'] = $opBal + $item['adblue_amount'] - $item['payment_amount'];

            $totalAdBlueAmountAll += $item['adblue_amount'];
            $totalPaymentAmountAll += $item['payment_amount'];
            $openingBalanceAll += $opBal;
        }
        unset($item);

        usort($overviewData, function ($a, $b) {
            return strcmp($a['company_name'], $b['company_name']);
        });

        // 3. Detailed Ledger
        $ledgerItems = [];
        $adblueLedgerQuery = TripAdBlueDetail::with(['trip.builty.vehicle', 'adblueCompany']);
        $paymentLedgerQuery = AdBlueCompanyPayment::with(['adblueCompany', 'company'])->withoutGlobalScope('company');

        if ($targetCompanyId) {
            $adblueLedgerQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                $q->where('company_id', $targetCompanyId);
            });
            $paymentLedgerQuery->where('company_id', $targetCompanyId);
        }

        if ($request->filled('adblue_company_id')) {
            $adblueLedgerQuery->where('adblue_company_id', $request->adblue_company_id);
            $paymentLedgerQuery->where('adblue_company_id', $request->adblue_company_id);
        }
        if ($request->filled('date_from')) {
            $adblueLedgerQuery->where('date', '>=', $request->date_from);
            $paymentLedgerQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $adblueLedgerQuery->where('date', '<=', $request->date_to);
            $paymentLedgerQuery->where('date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $adblueLedgerQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('vehicle_id', $request->vehicle_id);
            });
        }
        if ($request->filled('lr_no')) {
            $adblueLedgerQuery->whereHas('builty', function ($q) use ($request) {
                $q->where('lr_no', 'like', "%{$request->lr_no}%");
            });
        }

        // Calculate single overall opening balance for the ledger view
        $singleOpeningBalance = 0.0;
        if ($request->filled('date_from')) {
            $singleOpAdBlueQuery = TripAdBlueDetail::query()->where('date', '<', $request->date_from);
            $singleOpPaymentQuery = AdBlueCompanyPayment::query()->withoutGlobalScope('company')->where('date', '<', $request->date_from);

            if ($targetCompanyId) {
                $singleOpAdBlueQuery->whereHas('builty', function ($q) use ($targetCompanyId) {
                    $q->where('company_id', $targetCompanyId);
                });
                $singleOpPaymentQuery->where('company_id', $targetCompanyId);
            }

            if ($request->filled('adblue_company_id')) {
                $singleOpAdBlueQuery->where('adblue_company_id', $request->adblue_company_id);
                $singleOpPaymentQuery->where('adblue_company_id', $request->adblue_company_id);
            }
            if ($request->filled('vehicle_id')) {
                $singleOpAdBlueQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('vehicle_id', $request->vehicle_id);
                });
            }
            if ($request->filled('lr_no')) {
                $singleOpAdBlueQuery->whereHas('builty', function ($q) use ($request) {
                    $q->where('lr_no', 'like', "%{$request->lr_no}%");
                });
            }

            $singleOpDebit = (float)$singleOpAdBlueQuery->sum('amount');
            $singleOpCredit = (float)$singleOpPaymentQuery->sum('amount');
            $singleOpeningBalance = $singleOpDebit - $singleOpCredit;
        }

        // Add opening balance to ledger items if date_from is set
        if ($request->filled('date_from')) {
            $ledgerItems[] = [
                'date' => $request->date_from,
                'date_raw' => Carbon::parse($request->date_from)->startOfDay(),
                'type' => 'Opening Balance',
                'ref_no' => '-',
                'vehicle' => '-',
                'company' => '-',
                'qty' => 0.00,
                'rate' => 0.00,
                'debit' => $singleOpeningBalance >= 0 ? $singleOpeningBalance : 0.00,
                'credit' => $singleOpeningBalance < 0 ? abs($singleOpeningBalance) : 0.00,
                'remark' => 'Outstanding balance before ' . date('d-m-Y', strtotime($request->date_from)),
            ];
        }

        $adblues = $adblueLedgerQuery->get();
        foreach ($adblues as $ab) {
            $ledgerItems[] = [
                'date' => $ab->date ? $ab->date->format('Y-m-d') : null,
                'date_raw' => $ab->date,
                'type' => 'AdBlue Purchase',
                'ref_no' => $ab->trip?->builty?->lr_no ?? '-',
                'vehicle' => $ab->trip?->builty?->vehicle?->vehicle_number ?? '-',
                'company' => $ab->adblueCompany?->name ?? '-',
                'qty' => (float)$ab->quantity,
                'rate' => (float)$ab->rate,
                'debit' => (float)$ab->amount,
                'credit' => 0.00,
                'remark' => '-',
            ];
        }

        $paymentsList = $paymentLedgerQuery->get();
        foreach ($paymentsList as $p) {
            $ledgerItems[] = [
                'date' => $p->date ? $p->date->format('Y-m-d') : null,
                'date_raw' => $p->date,
                'type' => 'Payment',
                'ref_no' => $p->payment_method ?? 'Payment',
                'vehicle' => '-',
                'company' => $p->adblueCompany?->name ?? '-',
                'qty' => 0.00,
                'rate' => 0.00,
                'debit' => 0.00,
                'credit' => (float)$p->amount,
                'remark' => $p->remark ?? '-',
                'payment_id' => $p->id,
            ];
        }

        usort($ledgerItems, function ($a, $b) {
            $dateA = $a['date_raw'] ? $a['date_raw']->timestamp : 0;
            $dateB = $b['date_raw'] ? $b['date_raw']->timestamp : 0;
            if ($dateA == $dateB) {
                if ($a['type'] === 'Opening Balance') return -1;
                if ($b['type'] === 'Opening Balance') return 1;
                $valA = $a['type'] === 'Payment' ? 1 : 0;
                $valB = $b['type'] === 'Payment' ? 1 : 0;
                return $valA - $valB;
            }
            return $dateA - $dateB;
        });

        $balance = 0.0;
        foreach ($ledgerItems as &$item) {
            if ($item['type'] === 'Opening Balance') {
                $balance = $item['debit'] - $item['credit'];
            } else {
                $balance += ($item['debit'] - $item['credit']);
            }
            $item['balance'] = $balance;
        }
        unset($item);

        // 4. Paginated Payments tab
        $paymentsQuery = AdBlueCompanyPayment::with(['adblueCompany', 'company'])
            ->withoutGlobalScope('company')
            ->orderBy('date', 'desc');

        if ($targetCompanyId) {
            $paymentsQuery->where('company_id', $targetCompanyId);
        }
        if ($request->filled('adblue_company_id')) {
            $paymentsQuery->where('adblue_company_id', $request->adblue_company_id);
        }
        if ($request->filled('date_from')) {
            $paymentsQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $paymentsQuery->where('date', '<=', $request->date_to);
        }
        $payments = $paymentsQuery->paginate(15);

        if ($request->export === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AdBlueLedgerExport($ledgerItems), 'adblue_ledger_'.date('YmdHis').'.xlsx');
        } elseif ($request->export === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.transport.trips.exports.adblue-ledger', compact('ledgerItems'));
            return $pdf->download('adblue_ledger_'.date('YmdHis').'.pdf');
        }

        return view('admin.transport.trips.adblue-outstanding', compact(
            'vehicleList',
            'adblueCompanies',
            'overviewData',
            'ledgerItems',
            'payments',
            'totalAdBlueAmountAll',
            'totalPaymentAmountAll',
            'openingBalanceAll',
            'companies'
        ));
    }

    public function storeAdBluePayment(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'date' => 'required|date|before_or_equal:9999-12-31',
            'adblue_company_id' => 'required|exists:adblue_companies,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:500',
        ]);

        $sessionCompanyId = $request->input('company_id') ?: session('current_company_id');
        $sessionBranchId = session('current_branch_id');

        if (!$sessionCompanyId || $sessionCompanyId === 'all') {
            $sessionCompanyId = auth()->user()->company_id;
        }
        if (!$sessionBranchId || $sessionBranchId === 'all') {
            $sessionBranchId = auth()->user()->branch_id;
        }

        if (!$sessionCompanyId) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please select a specific company from the top bar to record payment.']);
            }
            return redirect()->back()->with('error', 'Please select a specific company to record payment.');
        }

        $payment = AdBlueCompanyPayment::create([
            'company_id' => $sessionCompanyId,
            'date' => $validated['date'],
            'adblue_company_id' => $validated['adblue_company_id'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? 'Bank Transfer',
            'remark' => $validated['remark'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Payment recorded successfully.', 'payment' => $payment]);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function editAdBluePayment($id)
    {
        $payment = AdBlueCompanyPayment::with('company')->findOrFail($id);
        return response()->json($payment);
    }

    public function updateAdBluePayment(Request $request, $id)
    {
        $payment = AdBlueCompanyPayment::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'date' => 'required|date|before_or_equal:9999-12-31',
            'adblue_company_id' => 'required|exists:adblue_companies,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:500',
        ]);

        if ($request->filled('company_id')) {
            $validated['company_id'] = $request->company_id;
        }

        $payment->update($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Payment updated successfully.', 'payment' => $payment]);
        }

        return redirect()->back()->with('success', 'Payment updated successfully.');
    }

    public function destroyAdBluePayment($id)
    {
        $payment = AdBlueCompanyPayment::findOrFail($id);
        $payment->delete();

        return response()->json(['success' => true, 'message' => 'Payment deleted successfully.']);
    }
}
