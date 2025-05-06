<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\PropertyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class PropertyController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of properties.
     * 
     * @OA\Get(
     *     path="/properties",
     *     operationId="getPropertiesList",
     *     tags={"Properties"},
     *     summary="Get list of properties",
     *     description="Returns paginated list of properties with filtering and sorting options",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID (owner)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="property_type",
     *         in="query",
     *         description="Filter by property type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Filter by city",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="Filter by state",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search across name, address, description, city, and user information",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         @OA\Schema(type="integer", format="int32")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="city", type="string"),
     *                 @OA\Property(property="state", type="string"),
     *                 @OA\Property(property="zip_code", type="string"),
     *                 @OA\Property(property="property_type", type="string"),
     *                 @OA\Property(property="year_built", type="integer"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="pagination", type="object",
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
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Generate a unique cache key based on all request parameters
            $cacheKey = 'properties_' . md5(json_encode($request->all()));
            
            // Try to get from cache first, or execute the query and cache the result
            return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request) {
                $query = Property::with(['user', 'property_managers', 'units']);

                // Filtering
                if ($request->has('user_id')) {
                    $query->where('user_id', $request->user_id);
                }

                if ($request->has('property_type')) {
                    $query->where('property_type', $request->property_type);
                }

                if ($request->has('status')) {
                    $query->where('status', $request->status);
                }

                if ($request->has('city')) {
                    $query->where('city', 'like', "%{$request->city}%");
                }

                if ($request->has('state')) {
                    $query->where('state', $request->state);
                }

                if ($request->has('zip_code')) {
                    $query->where('zip_code', $request->zip_code);
                }

                if ($request->has('year_built_from')) {
                    $query->whereDate('year_built', '>=', $request->year_built_from);
                }

                if ($request->has('year_built_to')) {
                    $query->whereDate('year_built', '<=', $request->year_built_to);
                }

                // Search by name, address, or description
                if ($request->has('search')) {
                    $search = $request->search;
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhereHas('user', function (Builder $query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                }

                // Sorting
                $sortField = $request->input('sort_by', 'created_at');
                $sortDirection = $request->input('sort_direction', 'desc');
                
                // Validate sort field to prevent SQL injection
                $allowedSortFields = [
                    'id', 'user_id', 'name', 'address', 'city', 'state', 'zip_code', 
                    'property_type', 'year_built', 'status', 'created_at', 'updated_at'
                ];
                
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
                } else {
                    $query->orderBy('created_at', 'desc');
                }

                // Pagination
                $perPage = (int) $request->input('per_page', 15);
                $properties = $query->paginate($perPage);

                return response()->json([
                    'data' => $properties->items(),
                    'pagination' => [
                        'total' => $properties->total(),
                        'per_page' => $properties->perPage(),
                        'current_page' => $properties->currentPage(),
                        'last_page' => $properties->lastPage(),
                        'from' => $properties->firstItem() ?? 0,
                        'to' => $properties->lastItem() ?? 0,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created property in storage.
     *
     * @OA\Post(
     *     path="/properties",
     *     operationId="storeProperty",
     *     tags={"Properties"},
     *     summary="Store new property",
     *     description="Creates a new property and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "name", "address", "city", "state", "zip_code", "property_type", "status"},
     *             @OA\Property(property="user_id", type="integer", description="ID of the property owner"),
     *             @OA\Property(property="name", type="string", description="Name of the property"),
     *             @OA\Property(property="address", type="string", description="Street address of the property"),
     *             @OA\Property(property="city", type="string", description="City where property is located"),
     *             @OA\Property(property="state", type="string", description="State where property is located"),
     *             @OA\Property(property="zip_code", type="string", description="ZIP code of the property"),
     *             @OA\Property(property="property_type", type="string", description="Type of property (apartment, house, condo, etc.)"),
     *             @OA\Property(property="year_built", type="integer", description="Year the property was built"),
     *             @OA\Property(property="description", type="string", description="Description of the property", nullable=true),
     *             @OA\Property(property="status", type="string", description="Status of the property (active, inactive, maintenance)"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Property created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="zip_code", type="string"),
     *             @OA\Property(property="property_type", type="string"),
     *             @OA\Property(property="year_built", type="integer"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\PropertyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PropertyRequest $request)
    {
        $validated = $request->validated();

        // Check if user exists
        $user = User::findOrFail($validated['user_id']);

        $property = Property::create($validated);

        // Load relationships for the response
        $property->load(['user', 'property_managers', 'units']);

        // Clear properties cache
        Cache::flush('properties_*');

        return response()->json($property, 201);
    }

    /**
     * Display the specified property.
     *
     * @OA\Get(
     *     path="/properties/{id}",
     *     operationId="getPropertyById",
     *     tags={"Properties"},
     *     summary="Get property information",
     *     description="Returns property details by ID",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Property ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="zip_code", type="string"),
     *             @OA\Property(property="property_type", type="string"),
     *             @OA\Property(property="year_built", type="integer"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="property_managers", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="units", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Property not found"
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
        $property = Property::with(['user', 'property_managers', 'units'])->findOrFail($id);
        return response()->json($property);
    }

    /**
     * Update the specified property in storage.
     *
     * @OA\Put(
     *     path="/properties/{id}",
     *     operationId="updateProperty",
     *     tags={"Properties"},
     *     summary="Update property",
     *     description="Updates an existing property and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Property ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", description="ID of the property owner"),
     *             @OA\Property(property="name", type="string", description="Name of the property"),
     *             @OA\Property(property="address", type="string", description="Street address of the property"),
     *             @OA\Property(property="city", type="string", description="City where property is located"),
     *             @OA\Property(property="state", type="string", description="State where property is located"),
     *             @OA\Property(property="zip_code", type="string", description="ZIP code of the property"),
     *             @OA\Property(property="property_type", type="string", description="Type of property (apartment, house, condo, etc.)"),
     *             @OA\Property(property="year_built", type="integer", description="Year the property was built"),
     *             @OA\Property(property="description", type="string", description="Description of the property", nullable=true),
     *             @OA\Property(property="status", type="string", description="Status of the property (active, inactive, maintenance)"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Property updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="zip_code", type="string"),
     *             @OA\Property(property="property_type", type="string"),
     *             @OA\Property(property="year_built", type="integer"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Property not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\PropertyRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PropertyRequest $request, $id)
    {
        $property = Property::findOrFail($id);
        $validated = $request->validated();

        $property->update($validated);

        // Load relationships for the response
        $property->load(['user', 'property_managers', 'units']);

        // Clear properties cache
        Cache::flush('properties_*');

        return response()->json($property);
    }

    /**
     * Remove the specified property from storage.
     *
     * @OA\Delete(
     *     path="/properties/{id}",
     *     operationId="deleteProperty",
     *     tags={"Properties"},
     *     summary="Delete property",
     *     description="Deletes a property if it has no associated units or property managers",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Property ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Property deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Property not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete property with dependencies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete property with associated units. Remove all units first or change the property status to inactive.")
     *         )
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
        $property = Property::findOrFail($id);
        
        // Check if property has units
        if ($property->units()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete property with associated units. Remove all units first or change the property status to inactive.'
            ], 422);
        }
        
        // Check if property has property managers
        if ($property->property_managers()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete property with associated property managers. Remove all property managers first.'
            ], 422);
        }
        
        $property->delete();

        // Clear properties cache
        Cache::flush('properties_*');

        return response()->json(null, 204);
    }

    /**
     * Get property statistics.
     *
     * @OA\Get(
     *     path="/properties/statistics",
     *     operationId="getPropertyStatistics",
     *     tags={"Properties"},
     *     summary="Get property statistics",
     *     description="Returns statistics about properties including counts by status and type",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_properties", type="integer"),
     *             @OA\Property(property="active_properties", type="integer"),
     *             @OA\Property(property="inactive_properties", type="integer"),
     *             @OA\Property(property="maintenance_properties", type="integer"),
     *             @OA\Property(property="property_types", type="object",
     *                 @OA\Property(property="apartment", type="integer"),
     *                 @OA\Property(property="house", type="integer"),
     *                 @OA\Property(property="condo", type="integer"),
     *                 @OA\Property(property="townhouse", type="integer"),
     *                 @OA\Property(property="commercial", type="integer")
     *             ),
     *             @OA\Property(property="properties_by_state", type="object")
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
            'total_properties' => Property::count(),
            'active_properties' => Property::where('status', 'active')->count(),
            'inactive_properties' => Property::where('status', 'inactive')->count(),
            'maintenance_properties' => Property::where('status', 'maintenance')->count(),
            'property_types' => [
                'apartment' => Property::where('property_type', 'apartment')->count(),
                'house' => Property::where('property_type', 'house')->count(),
                'condo' => Property::where('property_type', 'condo')->count(),
                'townhouse' => Property::where('property_type', 'townhouse')->count(),
                'commercial' => Property::where('property_type', 'commercial')->count(),
            ],
            'properties_by_state' => Property::selectRaw('state, count(*) as count')
                ->groupBy('state')
                ->orderByDesc('count')
                ->get()
                ->pluck('count', 'state'),
        ];

        return response()->json($stats);
    }
}