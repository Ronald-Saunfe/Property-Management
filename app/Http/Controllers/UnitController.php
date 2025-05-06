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
     * @OA\Get(
     *     path="/units",
     *     operationId="getUnitsList",
     *     tags={"Units"},
     *     summary="Get list of units",
     *     description="Returns paginated list of units with filtering and sorting options",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="property_id",
     *         in="query",
     *         description="Filter by property ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="unit_number",
     *         in="query",
     *         description="Filter by unit number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="floor_plan",
     *         in="query",
     *         description="Filter by floor plan",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"available", "occupied", "maintenance", "reserved"})
     *     ),
     *     @OA\Parameter(
     *         name="bedrooms",
     *         in="query",
     *         description="Filter by number of bedrooms",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="bathrooms",
     *         in="query",
     *         description="Filter by number of bathrooms",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="square_feet_min",
     *         in="query",
     *         description="Filter by minimum square feet",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="square_feet_max",
     *         in="query",
     *         description="Filter by maximum square feet",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="monthly_rent_min",
     *         in="query",
     *         description="Filter by minimum monthly rent",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="monthly_rent_max",
     *         in="query",
     *         description="Filter by maximum monthly rent",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by unit number, floor plan, features, property name, or property address",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id", "property_id", "unit_number", "floor_plan", "square_feet", "bedrooms", "bathrooms", "monthly_rent", "status", "created_at", "updated_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Direction to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="property_id", type="integer"),
     *                     @OA\Property(property="unit_number", type="string"),
     *                     @OA\Property(property="floor_plan", type="string"),
     *                     @OA\Property(property="square_feet", type="integer"),
     *                     @OA\Property(property="bedrooms", type="integer"),
     *                     @OA\Property(property="bathrooms", type="number", format="float"),
     *                     @OA\Property(property="monthly_rent", type="number", format="float"),
     *                     @OA\Property(property="features", type="string"),
     *                     @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance", "reserved"}),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="property",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="address", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="to", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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
     * @OA\Post(
     *     path="/units",
     *     operationId="storeUnit",
     *     tags={"Units"},
     *     summary="Store new unit",
     *     description="Creates a new unit and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"property_id", "unit_number", "status"},
     *             @OA\Property(property="property_id", type="integer", example=1),
     *             @OA\Property(property="unit_number", type="string", example="A101"),
     *             @OA\Property(property="floor_plan", type="string", example="One Bedroom Deluxe"),
     *             @OA\Property(property="square_feet", type="integer", example=750),
     *             @OA\Property(property="bedrooms", type="integer", example=1),
     *             @OA\Property(property="bathrooms", type="number", format="float", example=1.5),
     *             @OA\Property(property="monthly_rent", type="number", format="float", example=1200),
     *             @OA\Property(property="features", type="string", example="Granite countertops, stainless steel appliances, hardwood floors"),
     *             @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance", "reserved"}, example="available")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Unit created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="property_id", type="integer"),
     *             @OA\Property(property="unit_number", type="string"),
     *             @OA\Property(property="floor_plan", type="string"),
     *             @OA\Property(property="square_feet", type="integer"),
     *             @OA\Property(property="bedrooms", type="integer"),
     *             @OA\Property(property="bathrooms", type="number", format="float"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="features", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="property",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="address", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or unit number already exists for this property"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Get(
     *     path="/units/{id}",
     *     operationId="getUnitById",
     *     tags={"Units"},
     *     summary="Get unit information",
     *     description="Returns unit details with associated property and leases",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Unit ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="property_id", type="integer"),
     *             @OA\Property(property="unit_number", type="string"),
     *             @OA\Property(property="floor_plan", type="string"),
     *             @OA\Property(property="square_feet", type="integer"),
     *             @OA\Property(property="bedrooms", type="integer"),
     *             @OA\Property(property="bathrooms", type="number", format="float"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="features", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="property",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="address", type="string")
     *             ),
     *             @OA\Property(
     *                 property="leases",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="property_id", type="integer"),
     *                     @OA\Property(property="unit_id", type="integer"),
     *                     @OA\Property(property="start_date", type="string", format="date"),
     *                     @OA\Property(property="end_date", type="string", format="date"),
     *                     @OA\Property(property="rent_amount", type="number", format="float"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Put(
     *     path="/units/{id}",
     *     operationId="updateUnit",
     *     tags={"Units"},
     *     summary="Update unit",
     *     description="Updates an existing unit and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Unit ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="property_id", type="integer"),
     *             @OA\Property(property="unit_number", type="string"),
     *             @OA\Property(property="floor_plan", type="string"),
     *             @OA\Property(property="square_feet", type="integer"),
     *             @OA\Property(property="bedrooms", type="integer"),
     *             @OA\Property(property="bathrooms", type="number", format="float"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="features", type="string"),
     *             @OA\Property(property="status", type="string", enum={"available", "occupied", "maintenance", "reserved"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unit updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="property_id", type="integer"),
     *             @OA\Property(property="unit_number", type="string"),
     *             @OA\Property(property="floor_plan", type="string"),
     *             @OA\Property(property="square_feet", type="integer"),
     *             @OA\Property(property="bedrooms", type="integer"),
     *             @OA\Property(property="bathrooms", type="number", format="float"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="features", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="property",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="address", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error, unit number already exists, or cannot mark as occupied without active lease"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/units/{id}",
     *     operationId="deleteUnit",
     *     tags={"Units"},
     *     summary="Delete unit",
     *     description="Deletes a unit if it has no active or pending leases",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Unit ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Unit deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unit not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete unit with active or pending leases"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Get(
     *     path="/units/statistics",
     *     operationId="getUnitStatistics",
     *     tags={"Units"},
     *     summary="Get unit statistics",
     *     description="Returns statistics about units",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_units", type="integer", example=100),
     *             @OA\Property(property="available_units", type="integer", example=45),
     *             @OA\Property(property="occupied_units", type="integer", example=40),
     *             @OA\Property(property="maintenance_units", type="integer", example=10),
     *             @OA\Property(property="reserved_units", type="integer", example=5),
     *             @OA\Property(property="average_rent", type="number", format="float", example=1350.75),
     *             @OA\Property(
     *                 property="units_by_bedrooms",
     *                 type="object",
     *                 @OA\Property(property="0", type="integer", example=15),
     *                 @OA\Property(property="1", type="integer", example=35),
     *                 @OA\Property(property="2", type="integer", example=30),
     *                 @OA\Property(property="3", type="integer", example=15),
     *                 @OA\Property(property="4+", type="integer", example=5)
     *             ),
     *             @OA\Property(
     *                 property="units_by_property",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="address", type="string"),
     *                     @OA\Property(property="units_count", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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