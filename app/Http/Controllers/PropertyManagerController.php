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
     * @param  \App\Http\Requests\PropertyManagerRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PropertyManagerRequest $request)
    {
        $validated = $request->validated();

        // Check if property exists
        $property = Property::findOrFail($validated['property_id']);
        
        // Check if user exists
        $user = User::findOrFail($validated['user_id']);

        // If this is a primary manager, update any existing primary managers for this property
        if ($validated['is_primary']) {
            PropertyManager::where('property_id', $validated['property_id'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $propertyManager = PropertyManager::create($validated);

        // Load relationships for the response
        $propertyManager->load(['property', 'user']);

        return response()->json($propertyManager, 201);
    }

    /**
     * Display the specified property manager.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $propertyManager = PropertyManager::with(['property', 'user'])->findOrFail($id);
        return response()->json($propertyManager);
    }

    /**
     * Update the specified property manager in storage.
     *
     * @param  \App\Http\Requests\PropertyManagerRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PropertyManagerRequest $request, $id)
    {
        $propertyManager = PropertyManager::findOrFail($id);
        $validated = $request->validated();

        // If this is being set as primary, update any existing primary managers for this property
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            $propertyId = $validated['property_id'] ?? $propertyManager->property_id;
            PropertyManager::where('property_id', $propertyId)
                ->where('id', '!=', $id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $propertyManager->update($validated);

        // Load relationships for the response
        $propertyManager->load(['property', 'user']);

        return response()->json($propertyManager);
    }

    /**
     * Remove the specified property manager from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $propertyManager = PropertyManager::findOrFail($id);
        
        // Check if this is the only manager for the property
        $managersCount = PropertyManager::where('property_id', $propertyManager->property_id)->count();
        if ($managersCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the only property manager. A property must have at least one manager.'
            ], 422);
        }
        
        // Check if this is the primary manager
        if ($propertyManager->is_primary) {
            return response()->json([
                'message' => 'Cannot delete the primary property manager. Assign another manager as primary first.'
            ], 422);
        }
        
        $propertyManager->delete();

        return response()->json(null, 204);
    }

    /**
     * Get property manager statistics.
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