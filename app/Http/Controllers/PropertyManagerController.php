<?php

namespace App\Http\Controllers;

use App\Models\PropertyManager;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\PropertyManagerRequest;
use Illuminate\Database\Eloquent\Builder;

class PropertyManagerController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of property managers.
     * 
     * @OA\Get(
     *     path="/property-managers",
     *     operationId="getPropertyManagersList",
     *     tags={"Property Managers"},
     *     summary="Get list of property managers",
     *     description="Returns paginated list of property managers with filtering and sorting options",
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
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_primary",
     *         in="query",
     *         description="Filter by primary manager status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by property name, address, user name, or email",
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
     *                 @OA\Property(property="property_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="is_primary", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="property", type="object"),
     *                 @OA\Property(property="user", type="object")
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
            $query = PropertyManager::with(['property', 'user']);

            // Filtering
            if ($request->has('property_id')) {
                $query->where('property_id', $request->property_id);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('is_primary')) {
                $query->where('is_primary', filter_var($request->is_primary, FILTER_VALIDATE_BOOLEAN));
            }

            // Search by property name or user name/email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function (Builder $query) use ($search) {
                    $query->whereHas('property', function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                              ->orWhere('address', 'like', "%{$search}%");
                    })
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
                'id', 'property_id', 'user_id', 'is_primary', 'created_at', 'updated_at'
            ];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $propertyManagers = $query->paginate($perPage);

            return response()->json([
                'data' => $propertyManagers->items(),
                'pagination' => [
                    'total' => $propertyManagers->total(),
                    'per_page' => $propertyManagers->perPage(),
                    'current_page' => $propertyManagers->currentPage(),
                    'last_page' => $propertyManagers->lastPage(),
                    'from' => $propertyManagers->firstItem() ?? 0,
                    'to' => $propertyManagers->lastItem() ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching property managers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created property manager in storage.
     *
     * @OA\Post(
     *     path="/property-managers",
     *     operationId="storePropertyManager",
     *     tags={"Property Managers"},
     *     summary="Store new property manager",
     *     description="Creates a new property manager relationship and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"property_id", "user_id", "is_primary"},
     *             @OA\Property(property="property_id", type="integer", description="ID of the property"),
     *             @OA\Property(property="user_id", type="integer", description="ID of the user"),
     *             @OA\Property(property="is_primary", type="boolean", description="Whether this user is the primary manager for the property")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Property manager created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="property_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="property", type="object"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\PropertyManagerRequest  $request
     * @return \Illuminate\Http\Response
     */

    /**
     * Display the specified property manager.
     *
     * @OA\Get(
     *     path="/property-managers/{id}",
     *     operationId="getPropertyManagerById",
     *     tags={"Property Managers"},
     *     summary="Get property manager information",
     *     description="Returns property manager details by ID",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Property Manager ID",
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
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="property", type="object"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Property manager not found"
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

    /**
     * Update the specified property manager in storage.
     *
     * @OA\Put(
     *     path="/property-managers/{id}",
     *     operationId="updatePropertyManager",
     *     tags={"Property Managers"},
     *     summary="Update property manager",
     *     description="Updates an existing property manager relationship and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Property Manager ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="property_id", type="integer", description="ID of the property"),
     *             @OA\Property(property="user_id", type="integer", description="ID of the user"),
     *             @OA\Property(property="is_primary", type="boolean", description="Whether this user is the primary manager for the property")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Property manager updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="property_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="property", type="object"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Property manager not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\PropertyManagerRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified property manager from storage.
     *
     * @OA\Delete(
     *     path="/property-managers/{id}",
     *     operationId="deletePropertyManager",
     *     tags={"Property Managers"},
     *     summary="Delete property manager",
     *     description="Deletes a property manager relationship",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Property Manager ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Property manager deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Property manager not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete property manager",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete the only property manager. A property must have at least one manager.")
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

    /**
     * Get property manager statistics.
     *
     * @OA\Get(
     *     path="/property-managers/statistics",
     *     operationId="getPropertyManagerStatistics",
     *     tags={"Property Managers"},
     *     summary="Get property manager statistics",
     *     description="Returns statistics about property managers including counts by primary status",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_property_managers", type="integer"),
     *             @OA\Property(property="primary_managers", type="integer"),
     *             @OA\Property(property="secondary_managers", type="integer"),
     *             @OA\Property(property="properties_with_multiple_managers", type="integer"),
     *             @OA\Property(property="top_managers", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="properties_managed", type="integer")
     *             ))
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
            'total_property_managers' => PropertyManager::count(),
            'primary_managers' => PropertyManager::where('is_primary', true)->count(),
            'secondary_managers' => PropertyManager::where('is_primary', false)->count(),
            'properties_with_multiple_managers' => Property::whereHas('property_managers', function($query) {
                $query->selectRaw('property_id, count(*) as manager_count')
                      ->groupBy('property_id')
                      ->havingRaw('count(*) > 1');
            })->count(),
            'top_managers' => User::withCount('property_managers')
                ->orderByDesc('property_managers_count')
                ->limit(5)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'properties_managed' => $user->property_managers_count
                    ];
                }),
        ];

        return response()->json($stats);
    }
}