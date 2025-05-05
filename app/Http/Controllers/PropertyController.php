<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\PropertyRequest;
use Illuminate\Database\Eloquent\Builder;

class PropertyController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of properties.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
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

        return response()->json($property, 201);
    }

    /**
     * Display the specified property.
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

        return response()->json($property);
    }

    /**
     * Remove the specified property from storage.
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

        return response()->json(null, 204);
    }

    /**
     * Get property statistics.
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