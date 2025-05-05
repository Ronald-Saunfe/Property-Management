<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Http\Requests\UnitRequest;
use Illuminate\Database\Eloquent\Builder;

class UnitController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of units.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Unit::with(['property']);

            // Filtering
            if ($request->has('property_id')) {
                $query->where('property_id', $request->property_id);
            }

            if ($request->has('unit_number')) {
                $query->where('unit_number', 'like', "%{$request->unit_number}%");
            }

            if ($request->has('floor_plan')) {
                $query->where('floor_plan', 'like', "%{$request->floor_plan}%");
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('bedrooms')) {
                $query->where('bedrooms', $request->bedrooms);
            }

            if ($request->has('bathrooms')) {
                $query->where('bathrooms', $request->bathrooms);
            }

            if ($request->has('square_feet_min')) {
                $query->where('square_feet', '>=', $request->square_feet_min);
            }

            if ($request->has('square_feet_max')) {
                $query->where('square_feet', '<=', $request->square_feet_max);
            }

            if ($request->has('monthly_rent_min')) {
                $query->where('monthly_rent', '>=', $request->monthly_rent_min);
            }

            if ($request->has('monthly_rent_max')) {
                $query->where('monthly_rent', '<=', $request->monthly_rent_max);
            }

            // Search by unit number, floor plan, or features
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function (Builder $query) use ($search) {
                    $query->where('unit_number', 'like', "%{$search}%")
                        ->orWhere('floor_plan', 'like', "%{$search}%")
                        ->orWhere('features', 'like', "%{$search}%")
                        ->orWhereHas('property', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = [
                'id', 'property_id', 'unit_number', 'floor_plan', 'square_feet', 
                'bedrooms', 'bathrooms', 'monthly_rent', 'status', 'created_at', 'updated_at'
            ];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $units = $query->paginate($perPage);

            return response()->json([
                'data' => $units->items(),
                'pagination' => [
                    'total' => $units->total(),
                    'per_page' => $units->perPage(),
                    'current_page' => $units->currentPage(),
                    'last_page' => $units->lastPage(),
                    'from' => $units->firstItem() ?? 0,
                    'to' => $units->lastItem() ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching units',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created unit in storage.
     *
     * @param  \App\Http\Requests\UnitRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UnitRequest $request)
    {
        $validated = $request->validated();

        // Check if property exists
        $property = Property::findOrFail($validated['property_id']);

        // Check if unit number is unique for this property
        $existingUnit = Unit::where('property_id', $validated['property_id'])
            ->where('unit_number', $validated['unit_number'])
            ->first();
            
        if ($existingUnit) {
            return response()->json([
                'message' => 'A unit with this unit number already exists for this property.'
            ], 422);
        }

        $unit = Unit::create($validated);

        // Load relationships for the response
        $unit->load(['property']);

        return response()->json($unit, 201);
    }

    /**
     * Display the specified unit.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $unit = Unit::with(['property', 'leases'])->findOrFail($id);
        return response()->json($unit);
    }

    /**
     * Update the specified unit in storage.
     *
     * @param  \App\Http\Requests\UnitRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UnitRequest $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $validated = $request->validated();

        // Check if unit number is unique for this property if it's being changed
        if (isset($validated['unit_number']) && 
            ($validated['unit_number'] !== $unit->unit_number || 
             (isset($validated['property_id']) && $validated['property_id'] !== $unit->property_id))) {
            
            $propertyId = $validated['property_id'] ?? $unit->property_id;
            
            $existingUnit = Unit::where('property_id', $propertyId)
                ->where('unit_number', $validated['unit_number'])
                ->where('id', '!=', $id)
                ->first();
                
            if ($existingUnit) {
                return response()->json([
                    'message' => 'A unit with this unit number already exists for this property.'
                ], 422);
            }
        }

        // Check if status is being changed to 'occupied' when there are no active leases
        if (isset($validated['status']) && 
            $validated['status'] === 'occupied' && 
            $unit->status !== 'occupied') {
            
            $hasActiveLeases = $unit->leases()->where('status', 'active')->exists();
            if (!$hasActiveLeases) {
                return response()->json([
                    'message' => 'Cannot mark unit as occupied without an active lease. Create a lease first.'
                ], 422);
            }
        }

        $unit->update($validated);

        // Load relationships for the response
        $unit->load(['property']);

        return response()->json($unit);
    }

    /**
     * Remove the specified unit from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        
        // Check if unit has active leases
        $hasActiveLeases = $unit->leases()->whereIn('status', ['active', 'pending'])->exists();
        if ($hasActiveLeases) {
            return response()->json([
                'message' => 'Cannot delete unit with active or pending leases. Terminate leases first or change unit status.'
            ], 422);
        }
        
        $unit->delete();

        return response()->json(null, 204);
    }

    /**
     * Get unit statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $stats = [
            'total_units' => Unit::count(),
            'available_units' => Unit::where('status', 'available')->count(),
            'occupied_units' => Unit::where('status', 'occupied')->count(),
            'maintenance_units' => Unit::where('status', 'maintenance')->count(),
            'reserved_units' => Unit::where('status', 'reserved')->count(),
            'average_rent' => Unit::avg('monthly_rent') ?? 0,
            'units_by_bedrooms' => [
                '0' => Unit::where('bedrooms', 0)->count(), // Studio
                '1' => Unit::where('bedrooms', 1)->count(),
                '2' => Unit::where('bedrooms', 2)->count(),
                '3' => Unit::where('bedrooms', 3)->count(),
                '4+' => Unit::where('bedrooms', '>=', 4)->count(),
            ],
            'units_by_property' => Property::withCount('units')
                ->orderByDesc('units_count')
                ->limit(5)
                ->get()
                ->map(function($property) {
                    return [
                        'id' => $property->id,
                        'name' => $property->name,
                        'address' => $property->address,
                        'units_count' => $property->units_count
                    ];
                }),
        ];

        return response()->json($stats);
    }
}