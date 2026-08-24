<?php

namespace App\Http\Controllers\Admin\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\TyreManagement;
use App\Models\Vehicle;
use App\Models\TyreBrand;
use App\Models\TyreModel;
use App\Models\TyreSize;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TyreManagementController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('view tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $tyres = TyreManagement::with('vehicle', 'branch')
            ->latest()
            ->paginate(15);

        $vehicles = Vehicle::orderBy('vehicle_number')->get();

        return view('admin.maintenance.tyre-management.index', compact('tyres', 'vehicles'));
    }

    public function create(Request $request)
    {
        if (!auth()->user()->can('create tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $brands = TyreBrand::where('status', 'active')->orderBy('name')->get();
        $models = TyreModel::where('status', 'active')->orderBy('name')->get();
        $sizes = TyreSize::where('status', 'active')->orderBy('name')->get();

        $selectedVehicleId = $request->get('vehicle_id');
        $selectedPosition = $request->get('tyre_position') ?? $request->get('position');
        $returnTo = $request->get('return_to');

        $preselectedVehicle = $selectedVehicleId ? Vehicle::find($selectedVehicleId) : null;

        return view('admin.maintenance.tyre-management.create', compact('vehicles', 'brands', 'models', 'sizes', 'selectedVehicleId', 'selectedPosition', 'returnTo', 'preselectedVehicle'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'tyre_position' => 'required|string|max:255',
            'tyre_brand' => 'required|string|max:255',
            'tyre_size' => 'required|string|max:255',
            'tyre_model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:tyre_management,serial_number',
            'purchase_date' => 'nullable|date|before_or_equal:9999-12-31',
            'purchase_cost' => 'nullable|numeric|min:0',
            'installation_date' => 'nullable|date|before_or_equal:9999-12-31',
            'installation_km' => 'nullable|numeric|min:0',
            'removal_date' => 'nullable|date|before_or_equal:9999-12-31',
            'removal_km' => 'nullable|numeric|min:0',
            'removal_reason' => 'nullable|string|max:255',
            'tread_depth_new' => 'nullable|numeric|min:0',
            'tread_depth_current' => 'nullable|numeric|min:0',
            'pressure_psi' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,removed,scrap',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $validated['company_id'] = $user->company_id;
        $validated['branch_id'] = $user->branch_id;

        // If newly created tyre is active and assigned to a specific vehicle position,
        // mark any existing active tyre in that slot as removed to avoid duplication
        if ($validated['status'] === 'active' && !empty($validated['tyre_position']) && $validated['tyre_position'] !== 'Unassigned') {
            $occupyingTyre = TyreManagement::where('vehicle_id', $validated['vehicle_id'])
                ->where('tyre_position', $validated['tyre_position'])
                ->where('status', 'active')
                ->first();

            if ($occupyingTyre) {
                $occupyingTyre->status = 'removed';
                $occupyingTyre->save();
            }
        }

        $tyre = TyreManagement::create($validated);

        ActivityLog::log('tyre_created', "Tyre record created for vehicle", $tyre);

        if ($request->get('return_to') === 'layout') {
            return redirect()->route('admin.maintenance.tyre-management.layout', ['vehicle_id' => $tyre->vehicle_id])
                ->with('success', "Tyre {$tyre->tyre_brand} (" . ($tyre->serial_number ?: 'ID#'.$tyre->id) . ") added and assigned to position {$tyre->tyre_position} successfully!");
        }

        return redirect()->route('admin.maintenance.tyre-management.index')
            ->with('success', 'Tyre record created successfully');
    }

    public function show(TyreManagement $tyreManagement)
    {
        if (!auth()->user()->can('view tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $tyreManagement->load('vehicle', 'branch', 'company');

        return view('admin.maintenance.tyre-management.show', compact('tyreManagement'));
    }

    public function edit(TyreManagement $tyreManagement)
    {
        if (!auth()->user()->can('edit tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $brands = TyreBrand::where('status', 'active')->orderBy('name')->get();
        $models = TyreModel::where('status', 'active')->orderBy('name')->get();
        $sizes = TyreSize::where('status', 'active')->orderBy('name')->get();

        return view('admin.maintenance.tyre-management.edit', compact('tyreManagement', 'vehicles', 'brands', 'models', 'sizes'));
    }

    public function update(Request $request, TyreManagement $tyreManagement)
    {
        if (!auth()->user()->can('edit tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'tyre_position' => 'required|string|max:255',
            'tyre_brand' => 'required|string|max:255',
            'tyre_size' => 'required|string|max:255',
            'tyre_model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:tyre_management,serial_number,' . $tyreManagement->id,
            'purchase_date' => 'nullable|date|before_or_equal:9999-12-31',
            'purchase_cost' => 'nullable|numeric|min:0',
            'installation_date' => 'nullable|date|before_or_equal:9999-12-31',
            'installation_km' => 'nullable|numeric|min:0',
            'removal_date' => 'nullable|date|before_or_equal:9999-12-31',
            'removal_km' => 'nullable|numeric|min:0',
            'removal_reason' => 'nullable|string|max:255',
            'tread_depth_new' => 'nullable|numeric|min:0',
            'tread_depth_current' => 'nullable|numeric|min:0',
            'pressure_psi' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,removed,scrap',
            'notes' => 'nullable|string|max:1000',
        ]);

        $tyreManagement->update($validated);

        ActivityLog::log('tyre_updated', "Tyre record updated", $tyreManagement);

        return redirect()->route('admin.maintenance.tyre-management.index')
            ->with('success', 'Tyre record updated successfully');
    }

    public function destroy(TyreManagement $tyreManagement)
    {
        if (!auth()->user()->can('delete tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $tyreManagement->delete();

        ActivityLog::log('tyre_deleted', "Tyre record deleted", $tyreManagement);

        return redirect()->route('admin.maintenance.tyre-management.index')
            ->with('success', 'Tyre record deleted successfully');
    }

    public function trashed()
    {
        if (!auth()->user()->can('delete tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $tyres = TyreManagement::onlyTrashed()->with('vehicle')->latest()->paginate(15);

        return view('admin.maintenance.tyre-management.trashed', compact('tyres'));
    }

    public function restore($id)
    {
        if (!auth()->user()->can('delete tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $tyre = TyreManagement::onlyTrashed()->findOrFail($id);
        $tyre->restore();

        ActivityLog::log('tyre_restored', "Tyre record restored", $tyre);

        return redirect()->route('admin.maintenance.tyre-management.trashed')
            ->with('success', 'Tyre record restored successfully');
    }

    public function forceDelete($id)
    {
        if (!auth()->user()->can('delete tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $tyre = TyreManagement::onlyTrashed()->findOrFail($id);
        $tyre->forceDelete();

        ActivityLog::log('tyre_force_deleted', "Tyre record permanently deleted", $tyre);

        return redirect()->route('admin.maintenance.tyre-management.trashed')
            ->with('success', 'Tyre record permanently deleted');
    }

    public function graphicLayout(Request $request)
    {
        if (!auth()->user()->can('view tyre management') && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $selectedVehicleId = $request->get('vehicle_id') ?: ($vehicles->first()?->id);

        $selectedVehicle = null;
        $vehicleTyres = collect();
        $unassignedTyres = collect();

        if ($selectedVehicleId) {
            $selectedVehicle = Vehicle::find($selectedVehicleId);
            $vehicleTyres = TyreManagement::where('vehicle_id', $selectedVehicleId)
                ->where('status', 'active')
                ->get();

            $unassignedTyres = TyreManagement::where(function ($q) use ($selectedVehicleId) {
                $q->whereNull('vehicle_id')
                  ->orWhere(function ($q2) use ($selectedVehicleId) {
                      $q2->where('vehicle_id', $selectedVehicleId)->where('status', '!=', 'active');
                  });
            })
            ->where('status', '!=', 'scrap')
            ->get();
        }

        return view('admin.maintenance.tyre-management.graphic-layout', compact(
            'vehicles',
            'selectedVehicle',
            'selectedVehicleId',
            'vehicleTyres',
            'unassignedTyres'
        ));
    }

    public function getVehicleTyres(Vehicle $vehicle)
    {
        if (!auth()->user()->can('view tyre management') && !auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vehicleTyres = TyreManagement::where('vehicle_id', $vehicle->id)
            ->where('status', 'active')
            ->get();

        $unassignedTyres = TyreManagement::where(function ($q) use ($vehicle) {
            $q->whereNull('vehicle_id')
              ->orWhere(function ($q2) use ($vehicle) {
                  $q2->where('vehicle_id', $vehicle->id)->where('status', '!=', 'active');
              });
        })
        ->where('status', '!=', 'scrap')
        ->get();

        return response()->json([
            'success' => true,
            'vehicle' => $vehicle,
            'vehicle_tyres' => $vehicleTyres,
            'unassigned_tyres' => $unassignedTyres,
        ]);
    }

    public function updatePosition(Request $request)
    {
        if (!auth()->user()->can('edit tyre management') && !auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'tyre_id' => 'required|exists:tyre_management,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'new_position' => 'required|string',
            'target_tyre_id' => 'nullable|exists:tyre_management,id',
        ]);

        $tyre = TyreManagement::findOrFail($request->tyre_id);
        $oldPosition = $tyre->tyre_position;
        $newPosition = $request->new_position;
        $vehicleId = $request->vehicle_id;

        $occupyingTyre = null;
        if ($request->filled('target_tyre_id')) {
            $occupyingTyre = TyreManagement::find($request->target_tyre_id);
        } elseif ($newPosition !== 'Unassigned') {
            $occupyingTyre = TyreManagement::where('vehicle_id', $vehicleId)
                ->where('tyre_position', $newPosition)
                ->where('id', '!=', $tyre->id)
                ->where('status', 'active')
                ->first();
        }

        if ($occupyingTyre) {
            $occupyingTyre->tyre_position = $oldPosition;
            if ($oldPosition === 'Unassigned') {
                $occupyingTyre->status = 'removed';
            }
            $occupyingTyre->save();
        }

        $tyre->vehicle_id = $vehicleId;
        $tyre->tyre_position = $newPosition;
        if ($newPosition === 'Unassigned') {
            $tyre->status = 'removed';
        } else {
            $tyre->status = 'active';
            if (empty($tyre->installation_date)) {
                $tyre->installation_date = now()->format('Y-m-d');
            }
        }
        $tyre->save();

        ActivityLog::log('tyre_position_updated', "Updated position for Tyre #{$tyre->serial_number} to {$newPosition}", $tyre);

        // Fetch refreshed vehicle tyres and unassigned tyres
        $vehicleTyres = TyreManagement::where('vehicle_id', $vehicleId)
            ->where('status', 'active')
            ->get();

        $unassignedTyres = TyreManagement::where(function ($q) use ($vehicleId) {
            $q->whereNull('vehicle_id')
              ->orWhere(function ($q2) use ($vehicleId) {
                  $q2->where('vehicle_id', $vehicleId)->where('status', '!=', 'active');
              });
        })
        ->where('status', '!=', 'scrap')
        ->get();

        $allSlots = [
            'L1' => ['name' => 'Front Left (L1)', 'aliases' => ['L1', 'FL', 'Front Left']],
            'R1' => ['name' => 'Front Right (R1)', 'aliases' => ['R1', 'FR', 'Front Right']],
            'L2' => ['name' => 'Left Outer 1 (L2)', 'aliases' => ['L2', 'ROL1', 'Rear Left Outer']],
            'L3' => ['name' => 'Left Inner 1 (L3)', 'aliases' => ['L3', 'RIL1', 'Rear Left Inner']],
            'R3' => ['name' => 'Right Inner 1 (R3)', 'aliases' => ['R3', 'RIR1', 'Rear Right Inner']],
            'R2' => ['name' => 'Right Outer 1 (R2)', 'aliases' => ['R2', 'ROR1', 'Rear Right Outer']],
            'L4' => ['name' => 'Left Outer 2 (L4)', 'aliases' => ['L4', 'ROL2']],
            'L5' => ['name' => 'Left Inner 2 (L5)', 'aliases' => ['L5', 'RIL2']],
            'R5' => ['name' => 'Right Inner 2 (R5)', 'aliases' => ['R5', 'RIR2']],
            'R4' => ['name' => 'Right Outer 2 (R4)', 'aliases' => ['R4', 'ROR2']],
            'L6' => ['name' => 'Left Outer 3 (L6)', 'aliases' => ['L6', 'TLO1', 'ROL3']],
            'L7' => ['name' => 'Left Inner 3 (L7)', 'aliases' => ['L7', 'TLI1', 'RIL3']],
            'R7' => ['name' => 'Right Inner 3 (R7)', 'aliases' => ['R7', 'TRI1', 'RIR3']],
            'R6' => ['name' => 'Right Outer 3 (R6)', 'aliases' => ['R6', 'TRO1', 'ROR3']],
            'L8' => ['name' => 'Left Outer 4 (L8)', 'aliases' => ['L8', 'RL8']],
            'R8' => ['name' => 'Right Outer 4 (R8)', 'aliases' => ['R8', 'RR8']],
            'L9' => ['name' => 'Left Inner 4 (L9)', 'aliases' => ['L9', 'RL9']],
            'R9' => ['name' => 'Right Inner 4 (R9)', 'aliases' => ['R9', 'RR9']],
            'SP1' => ['name' => 'Spare 1 (Stepney)', 'aliases' => ['SP1', 'Spare 1', 'Spare']],
            'SP2' => ['name' => 'Spare 2 (Stepney)', 'aliases' => ['SP2', 'Spare 2']],
        ];

        $getSlotKey = function($pos) use ($allSlots) {
            $posUpper = strtoupper(trim($pos));
            foreach ($allSlots as $key => $data) {
                foreach ($data['aliases'] as $alias) {
                    if ($posUpper === strtoupper(trim($alias))) {
                        return $key;
                    }
                }
            }
            return $pos;
        };

        $findTyre = function($codes) use ($vehicleTyres) {
            $codes = (array)$codes;
            return $vehicleTyres->first(function($t) use ($codes) {
                $pos = strtoupper(trim($t->tyre_position));
                foreach ($codes as $c) {
                    if ($pos === strtoupper(trim($c))) return true;
                }
                return false;
            });
        };

        $oldKey = $getSlotKey($oldPosition);
        $newKey = $getSlotKey($newPosition);
        $affectedPositions = array_unique([$oldKey, $newKey]);

        $slotsHtml = [];
        foreach ($affectedPositions as $posCode) {
            if (isset($allSlots[$posCode])) {
                $slotData = $allSlots[$posCode];
                $matchedTyre = $findTyre($slotData['aliases']);
                $slotsHtml[$posCode] = view('admin.maintenance.tyre-management.partials.slot', [
                    'slotCode' => $posCode,
                    'slotName' => $slotData['name'],
                    'tyre' => $matchedTyre
                ])->render();
            }
        }

        $unassignedHtml = '';
        if ($unassignedTyres->count() > 0) {
            foreach ($unassignedTyres as $unTyre) {
                $unassignedHtml .= view('admin.maintenance.tyre-management.partials.tyre-card', ['tyre' => $unTyre])->render();
            }
        } else {
            $unassignedHtml = '<div class="text-center text-muted py-4 id-no-unassigned">
                <i class="bx bx-check-circle fs-2 text-success d-block mb-1"></i>
                No unassigned tyres in inventory pool
            </div>';
        }

        $mountedCount = $vehicleTyres->count() . ' / 18';
        $avgTread = $vehicleTyres->avg('tread_depth_current') ? number_format($vehicleTyres->avg('tread_depth_current'), 1) . ' mm' : 'N/A';
        $unassignedCount = $unassignedTyres->count();

        return response()->json([
            'success' => true,
            'message' => "Tyre {$tyre->tyre_brand} (" . ($tyre->serial_number ?: 'ID#'.$tyre->id) . ") moved to {$newPosition} successfully!",
            'tyre' => $tyre,
            'swapped_tyre' => $occupyingTyre ?? null,
            'slots_html' => $slotsHtml,
            'unassigned_html' => $unassignedHtml,
            'stats' => [
                'mounted_count' => $mountedCount,
                'avg_tread' => $avgTread,
                'unassigned_count' => $unassignedCount,
            ]
        ]);
    }
}
